<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\UserBundle\Entity\UserService;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\UserBundle\Entity\Compensation;
use Dhi\ServiceBundle\Controller\SelevisionController;
use Dhi\UserBundle\Entity\CustomerCompensationLog;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\CompensationUserService;

class CompensationCommand extends ContainerAwareCommand{

    private $output;
    private $handle;
    private $startTime;
    private $toEmail;
    private $objPaymentMethod;
    private $con;
    private $totalImportedServices;

    protected function configure(){

        $this->setName('dhi:compensation')->setDescription('Get compensation of services');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $cronStartTime = date('Y M j H:i');
        $output->writeln("\n####### Start Compensation Cron at ". $cronStartTime ." #######\n");
        $this->startTime             = new \DateTime("now");
        $em                          = $this->getContainer()->get('doctrine')->getManager();
        $setting                     = $em->getRepository("DhiAdminBundle:Setting")->findOneByName('compensation_to_email');
        $this->con                   = $em->getConnection();
        $this->totalImportedServices = 0;

        if ($setting) {
            if (!!filter_var($setting->getValue(), FILTER_VALIDATE_EMAIL)) {
                $this->toEmail = $setting->getValue();
            }
        }

        $this->objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneByCode('Compensation');
        if (!$this->objPaymentMethod) {
            $output->writeln(" Internal server error! Payment method 'compensation' does not exist");
            return;
        }

        $objCompensation = $em->getRepository('DhiUserBundle:Compensation')->findOneBy(array('status' => 'Inprogress', 'isActive' => true));
        if($objCompensation) {

            $output->writeln(" Cron already in progress");

        } else {

            // get compansation recrod
            // $records = $em->getRepository('DhiUserBundle:Compensation')->findBy(array('status' => 'Queued', 'isActive' => true, 'isInstance' => 0));
            $records = $em->getRepository('DhiUserBundle:Compensation')->findBy(
                array('status' => 'Queued', 'isActive' => true, 'isInstance' => 0),
                array('id' => 'ASC'),
                2,
                0
            );

            if($records) {

                $insCondition = array('comps' => $records);
                $em->getRepository('DhiUserBundle:Compensation')->updateInstance($insCondition);
				$isToFlush = 0;
                foreach($records as $record) {
                    if ($this->totalImportedServices <= 300) {

                        $serviceNameArr = $compensationService = array();
                        if($record->getServices()){
                            foreach ($record->getServices() as $service){
                                $serviceNameArr[] = strtoupper($service->getName());
                            }

                            //ServiceLocation wise compensations
                            if($record->getType() == 'ServiceLocation'){
                                $output->writeln("####### Service Location wise compensations process Comp Id: " . $record->getId() . "\n");
                                $this->serviceLocationWiseCompensation($record,$serviceNameArr,$output);
                            }
                        }
                    } else {
                        $record->setIsInstance(0);
                        $em->persist($record);
                        $isToFlush = 1;
                    }
                }

                if($isToFlush == 1){
                	$em->flush();
                }
            }
        }

        $output->writeln("\n####### Cron (". $cronStartTime .") End at " . date('Y M j H:i') . " #######");
    }

    public function serviceLocationWiseCompensation($record,$serviceNameArr,$output){
        $em                 = $this->getContainer()->get('doctrine')->getManager();
        $isFirstExecution   = $record->getIsStarted();

        if ($isFirstExecution == 0) {
            $numTotalPlans      = $em->getRepository("DhiUserBundle:UserService")->getNumberOfTotalPlans($record->getServiceLocations(), $serviceNameArr, $record->getCreatedAt());
        } else {
            $condition = array(
                'compensation' => $record->getId()
            );
            $numTotalPlans = $em->getRepository("DhiUserBundle:CustomerCompensationLog")->getCompServices($condition);
        }

        $numPlansComped     = $em->getRepository("DhiUserBundle:CompensationUserService")->getNumberOfCompedPlans($record->getId());
        $numRemainingToComp = $numTotalPlans - $numPlansComped;
        $startedAt          = new \DateTime("now");
   		
        $condition = array(
			'compensation' => $record->getId(),
			'status'       => 'Pending'
        );
        $pendingComps = $em->getRepository("DhiUserBundle:CustomerCompensationLog")->getCompServices($condition);

        if (($numRemainingToComp == 0 || $numTotalPlans == 0) || ($numRemainingToComp < 0 && $pendingComps == 0)) {
            $record->setStatus('Completed');
            $em->persist($record);
            $em->flush();

            return;
        }

        $executedAt = $record->getExecutedAt();

        if ($record->getStatus() == "Queued" && empty($executedAt)) {
            $record->setExecutedAt($startedAt);
        }

        $record->setStatus('Inprogress');
        $record->setIsStarted(true);
        $em->persist($record);
        $em->flush();

        if ($numPlansComped == 0) {
            if (!empty($this->toEmail)) {
                $admin = $em->getRepository("DhiUserBundle:User")->find($record->getAdminId());
                $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                $emailBody = $this->getContainer()->get('templating')->render('DhiAdminBundle:Emails:compensationEmailNotification.html.twig', array('compensation' => $record, 'admin' => $admin, 'isCompleted' => false));

                $duration_mail = \Swift_Message::newInstance()
                    ->setSubject($record->getTitle()." (Started on ".$startedAt->format("Y-m-d H:i:s").")")
                    ->setFrom($fromEmail)
                    ->setTo($this->toEmail)
                    ->setBody($emailBody)
                    ->setContentType('text/html');
                $this->getContainer()->get('mailer')->send($duration_mail);
            }
        }

        if($record->getServiceLocations()) {

            if ($isFirstExecution == 0) {
                
                $activeUserServices = $em->getRepository("DhiUserBundle:UserService")->getUserActiveServiceForCompensation($serviceNameArr, 0, false, true, $record->getServiceLocations(), $record);

                if ($activeUserServices) {
                    $params = array(
                        'activeService' => $activeUserServices,
                        'compensation'  => $record
                    );
                    $res                         = $this->importActiveServices($em, $params);
                    $this->totalImportedServices = $res['insertedRecords'];
                    if ($res['isStarted']) {
                        $record->setIsStarted(0);
                    }
                }
            }

            if ($this->totalImportedServices <= 300 && empty($res['isStarted'])) {
                
                $condition = array(
                    "status"       => "Pending",
                    'compensation' => $record->getId()
                );

                $activeServices = $em->getRepository("DhiUserBundle:CustomerCompensationLog")->getPendingUserServices($condition);

                if (!empty($activeServices)) {
                    $compLogs = array(
                        "failure" => array(),
                        "success" => array()
                    );

                    foreach ($activeServices as $key => $activeUserService) {
                        $id = $activeUserService->getId();
                        $condition = array(
                            'id'            => $id,
                            'activeService' => 1
                        );
                        $isServiceActive = $em->getRepository("DhiUserBundle:CustomerCompensationLog")->getCompServices($condition);

                        if ($isServiceActive > 0) {
                            $condition = array(
                                'userService' => $activeUserService->getUserService()->getId(),
                                'compensation'  => $record->getId()
                            );
                            $isServiceAlreadyComped = $em->getRepository("DhiUserBundle:CompensationUserService")->findBy($condition);
                        }

                        $customer             = $activeUserService->getUser();
                        $objUserActiveService = $activeUserService->getUserService();
                        $objService           = $activeUserService->getServices();
                        $service              = $objService->getName();
                        if (empty($isServiceAlreadyComped) && $isServiceActive > 0) {
                            $bonus = $activeUserService->getBonus();
                            if(strtoupper($service) == 'IPTV'){

                                if($this->processCompensationOnIPTV($record, $customer, $objUserActiveService, $bonus, $id)){
                                    $output->writeln(date('Y-m-d H:i:s') . " - IPTV service extended successfully of ".$customer->getUserName()." for Comp Id: ". $record->getId());
                                }else{
                                    $output->writeln(date('Y-m-d H:i:s') . " - User ".$customer->getUserName()." IPTV service extend failed for Comp Id: ". $record->getId());
                                }
                            }else if(strtoupper($service) == 'ISP'){

                                if($this->processCompensationOnISP($record, $customer, $objUserActiveService, $bonus, $id)){
                                    $output->writeln(date('Y-m-d H:i:s') . " - ISP service extended successfully of ".$customer->getUserName(). " for Comp Id: ". $record->getId());
                                }else{
                                    $output->writeln(date('Y-m-d H:i:s') . " - User ".$customer->getUserName()." ISP service extend failed for Comp Id: ". $record->getId());
                                }
                            }

                        }else{
                            $compLogs["failure"][] = $id;

                            $output->writeln(date('Y-m-d H:i:s') . " - User ".$customer->getUserName()." " . $service . " service extend failed for Comp Id: ". $record->getId());

                            if ($isServiceActive > 0 && !empty($isServiceAlreadyComped)) {
                                $this->storeCompensationLog("Failure", "Plan is already comped", $record, $customer, $objUserActiveService, null, $id);

                            } else {
                                $this->storeCompensationLog("Failure", "Plan is expired or User is deleted", $record, $customer, $objUserActiveService, null, $id);
                            }
                        }
                    }
                }
            }
        }

        if ($this->totalImportedServices <= 300 && empty($res['isStarted'])) {
            $numPlansComped = $em->getRepository("DhiUserBundle:CompensationUserService")->getNumberOfCompedPlans($record->getId());
            $numRemainingToComp = $numTotalPlans - $numPlansComped;

            if ($numRemainingToComp == 0) {
                $record->setStatus('Completed');

                if (!file_exists('./compensation')) {
                    mkdir('./compensation', 0777, true);
                }

                $csvFile = './compensation/compensation-failure-'.date("Y-m-d H:i").'.csv';
                $this->handle = fopen($csvFile, 'w+');
                fputcsv($this->handle, array("User Name", "Service", "Status", "Error If Fails"), ',');

                $compLogs = $em->getRepository("DhiUserBundle:CustomerCompensationLog")->findBy(array("compensation" => $record));
                $isFailedCompsFound = false;
                if ($compLogs) {
                    foreach ($compLogs as $compLog) {
                        if($compLog->getStatus() == 'Failure') {
                            $isFailedCompsFound = true;
                            fputcsv($this->handle, array($compLog->getUser()->getUsername(), $compLog->getServices()->getName(), $compLog->getStatus(), $compLog->getApiError()), ',');
                        }
                    }
                }

                fclose($this->handle);

                $endTime = new \DateTime("now");

                // Send Email
                if (!empty($this->toEmail)) {
                    $admin = $em->getRepository("DhiUserBundle:User")->find($record->getAdminId());
                    $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                    $emailBody = $this->getContainer()->get('templating')->render('DhiAdminBundle:Emails:compensationEmailNotification.html.twig', array('compensation' => $record, 'admin' => $admin, 'isCompleted' => true, 'numPlansComped' => $numPlansComped));

                    $compensation_status_email = \Swift_Message::newInstance()
                        ->setSubject($record->getTitle()." (Completed on ".$endTime->format("Y-m-d H:i:s").")")
                        ->setFrom($fromEmail)
                        ->setTo($this->toEmail)
                        ->setBody($emailBody)
                        ->setContentType('text/html');

                    if ($isFailedCompsFound) {
                        $compensation_status_email->attach(\Swift_Attachment::fromPath($csvFile));
                    }
                    $this->getContainer()->get('mailer')->send($compensation_status_email);
                }
            }else{
                $record->setStatus('Queued');
            }
        } else {
            $record->setStatus('Queued');
        }

        $record->setIsInstance(0);
        $em->persist($record);
        $em->flush();
    }

    private function importActiveServices($em, $parameters){

        $objCompensation = $parameters['compensation'];
        $activeServices  = $parameters['activeService'];
        $finalTotal = $count = 0;
        $isStarted = false;
        $parameters = array();
        $this->con->beginTransaction();
        try {
            foreach ($activeServices as $key => $userActiveService){
                if (empty($userActiveService['data'])) {
                    continue;
                }
                foreach ($userActiveService['data'] as $key => $objUserActiveService) {
                    $customer = $objUserActiveService->getUser();
                    $service  = $objUserActiveService->getService();

                    if ($service->getName() == 'IPTV') {

                        $extendedHours = 0;
                        if($objCompensation->getIptvDays()){
                            $extendedHours = $objCompensation->getIptvDays();
                        }else{
                            if($activeUserService['autoExtendService'] == 'IPTV'){
                                $extendedHours = $objCompensation->getIspHours();
                            }
                        }
                    }else if ($service->getName() == 'ISP') {

                        $extendedHours = 0;
                        if($objCompensation->getIspHours()){
                            $extendedHours = $objCompensation->getIspHours();
                        }else{
                            if($activeUserService['autoExtendService'] == 'ISP'){
                                $extendedHours = $objCompensation->getIptvDays();
                            }
                        }

                    }

                    if($extendedHours){
                        $date = new \DateTime();

                        if ($count == 0) {
                            $sql = "INSERT INTO customer_compensation_log (`user_id`, `service_id`, `compensation_id`, `status`, `bonus`, `user_service_id`, `created_at`, `updated_at`) VALUES ";
                        }
                        $count++;

                        $sql .= "(".$customer->getId().", ".$service->getId().", ".$objCompensation->getId().", 'Pending', ". $extendedHours .", ".$objUserActiveService->getId().", '". $date->format("Y-m-d H:i:s") ."', '". $date->format("Y-m-d H:i:s") ."'), ";

                        if ($count == 200) {
                            $this->executeQuery($this->con, $sql);
                            $count = 0;
                        }
                        $finalTotal++;
                    }
                }
            }
            $this->con->commit();
        } catch(\Exception $e) {
            $this->con->rollback();
            $isStarted = true;
        }

        if ($isStarted == false && !empty($sql) && $count > 0) {
            $this->con->beginTransaction();
            try {
                $this->executeQuery($this->con, $sql);
                $this->con->commit();
            } catch(\Exception $e) {
                $this->con->rollback();
                $isStarted = true;
            }
        }

        return array('insertedRecords' => $finalTotal, 'isStarted' => $isStarted);
    }

    private function executeQuery($con, $sql){
        $sql  = rtrim($sql, ', ').';';
        $stmt = $con->prepare($sql);

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function processCompensationOnIPTV($objCompensation,$objCustomer,$objUserActiveService, $bonus, $cclId){
        $em = $this->getContainer()->get('doctrine')->getManager();
        if($objCompensation && $objCustomer && $objUserActiveService){
            $totalIPTVExtendHours = $bonus;

            if(!empty($totalIPTVExtendHours)){
                $compensationStatus = 'Failure';
                $apiError = '';
                /*
                * [Selevision package extend date]
                *
                * $wsParam = array();
                * $wsParam['cuLogin'] = $objCustomer->getUsername();
                * $wsParam['offer']   = $objUserActiveService->getPackageId();
                * $wsParam['bonus']   = $objCompensation->getIptvDays();
                *
                *  // call selevision services giveCustomerBonusTime
                * $selevisionService = $this->getContainer()->get('selevisionService');
                * $wsResponse = $selevisionService->callWSAction('giveCustomerBonusTime', $wsParam);
                */
                $wsResponse = array('status' => 1);
                if($wsResponse['status'] == 1){
                    $compensationStatus = 'Success';

                    //add compensation of expiry date code start
                    $expiryDt = $objUserActiveService->getExpiryDate();
                    if($expiryDt == ''){
                        return false;
                    }

                    $expiryDate = $expiryDt->format('Y-m-d H:i:s');
                    $currentExpiryDate = new \DateTime($expiryDate);
                    $newExpiryDate  = $currentExpiryDate->modify('+'.$totalIPTVExtendHours.' HOURS');
                    $objUserActiveService->setExpiryDate($newExpiryDate);
                    $activationDate = $objUserActiveService->getActivationDate();
                    $intervalRemainDays = $activationDate->diff($newExpiryDate);

                    $servicePurchase = $objUserActiveService->getServicePurchase();
                    $validityType = 'DAYS';
                    if ($servicePurchase) {
                        $validityType = $servicePurchase->getValidityType();
                    }

                    if ($validityType == "HOURS") {
                        $validityDays =  $intervalRemainDays->format('%h');
                    }else{
                        $validityDays =  $intervalRemainDays->format('%a');
                    }

                    $objUserActiveService->setValidity($validityDays);
                    $em->persist($objUserActiveService);
                    $em->flush();
                    //end code of compensation
                }else{
                    if(isset($wsResponse['detail']) && !empty($wsResponse['detail'])){
                        $apiError = $wsResponse['detail'];
                    }
                }

                $purchaseOrder = null;
                if($compensationStatus == 'Success'){
                    $purchaseOrder = $this->addInPurchaseHistory($objCompensation, $objCustomer,$objUserActiveService);
                }

                //Add Customer Compensation Log
                $this->storeCompensationLog($compensationStatus, $apiError, $objCompensation,$objCustomer,$objUserActiveService, $purchaseOrder, $cclId);
                if($compensationStatus == 'Success'){
                    return true;
                }
            }
        }
        return false;
    }

    public function processCompensationOnISP($objCompensation, $objCustomer, $objUserActiveService, $bonus, $cclId){
        $em = $this->getContainer()->get('doctrine')->getManager();
        if($objCompensation && $objCustomer && $objUserActiveService){
            $totalISPExtendHours = $bonus;

            //add hours into expiry date
            $expiryDate                = $objUserActiveService->getExpiryDate()->format('Y-m-d H:i:s');
            $currentExpiryDate         = new \DateTime($expiryDate);
            $newExpiryDate             = $currentExpiryDate->modify('+'.$totalISPExtendHours.' HOURS');
            $aradialResponse['status'] = 0;
            $compensationStatus        = 'Failure';
            $apiError                  = '';
            $userName                  = $objCustomer->getUsername();

            // Update offer expiry date into user's aradial account
            $responseUserExits = $this->getContainer()->get('aradial')->checkUserExistsInAradial($userName);
            if($responseUserExits['status'] == 1) {
                $wsParam                               = array();
                $wsParam['Page']                       = "UserEdit";
                $wsParam['Modify']                     = 1;
                $wsParam['UserID']                     = $userName;
                $wsParam['db_$D$Users.UserExpiryDate'] = $newExpiryDate->format('m/d/Y H:i:s');
                $aradialResponse = $this->getContainer()->get('aradial')->callWSAction('updateUser',$wsParam);
            }

            if($aradialResponse['status'] == 1){
                //Get date diffreance of Active nad Expiry date
                $activationDate = $objUserActiveService->getActivationDate();
                $interval       = $activationDate->diff($newExpiryDate);
                $days           = $interval->format('%a');
                //End here
                $objUserActiveService->setExpiryDate($newExpiryDate);
                $objUserActiveService->setValidity($days);
                $em->persist($objUserActiveService);
                $em->flush();
                //end code of compensation
                $compensationStatus = 'Success';
            }

            $purchaseOrder = null;
            if($compensationStatus == 'Success'){
                $purchaseOrder = $this->addInPurchaseHistory($objCompensation, $objCustomer,$objUserActiveService);
            }

            //Add Customer Compensation Log
            $this->storeCompensationLog($compensationStatus, $apiError, $objCompensation, $objCustomer, $objUserActiveService, $purchaseOrder, $cclId);

            if($compensationStatus == 'Success'){
                return true;
            }
        }
        return false;
    }

    public function storeCompensationLog($compensationStatus, $apiError, $objCompensation, $objCustomer, $objUserActiveService, $purchaseOrder = null, $cclId){
        $em = $this->getContainer()->get('doctrine')->getManager();

        if($objCompensation && $objCustomer && $objUserActiveService->getService()){
            $objService = $objUserActiveService->getService();
            $bonus = '';
            if(strtoupper($objService->getName()) == 'IPTV'){
                $bonus = $objCompensation->getIptvDays();
            }
            if(strtoupper($objService->getName()) == 'ISP'){
                $bonus = $objCompensation->getIspHours();
            }

            $objCustomerCompensationLog = $em->getRepository("DhiUserBundle:CustomerCompensationLog")->find($cclId);

            if ($objCustomerCompensationLog) {
                $objCustomerCompensationLog->setUser($objCustomer);
                $objCustomerCompensationLog->setBonus($bonus);
                $objCustomerCompensationLog->setServices($objService);
                $objCustomerCompensationLog->setStatus($compensationStatus);
                $objCustomerCompensationLog->setCompensation($objCompensation);
                $objCustomerCompensationLog->setApiError($apiError);
                $em->persist($objCustomerCompensationLog);
                // $em->flush();
            }

            // Compensation user service log
            $compensationUserService = new CompensationUserService();
            $compensationUserService->setCompensation($objCompensation);
            $compensationUserService->setUserService($objUserActiveService);
            if (!empty($purchaseOrder) && is_object($purchaseOrder)) {
                $compensationUserService->setPurchaseOrder($purchaseOrder);
                $compensationUserService->setStatus(1);
            }else{
                $compensationUserService->setStatus(0);
            }
            $em->persist($compensationUserService);
            $em->flush();

            return true;
        }

        return false;
    }

    public function addInPurchaseHistory($objCompensation, $objCustomer,$objUserActiveService){
        $em          = $this->getContainer()->get('doctrine')->getManager();
        if($objCompensation && $objCustomer && $objUserActiveService){

            $existingOrderNumber = $em->getRepository("DhiUserBundle:CompensationUserService")->getExistingCompedPurchaseOrder($objCompensation->getId(), $objCustomer->getId());

            if (!empty($existingOrderNumber)) {
                $orderNumber = $existingOrderNumber;
            }else{
                $orderNumber = $this->generateOrderNumber();
            }

            $compensationValidity = '';
            if($objUserActiveService->getService()){
                if(strtoupper($objUserActiveService->getService()->getName()) == 'IPTV'){
                    $compensationValidity = $objCompensation->getIptvDays();
                }
                if(strtoupper($objUserActiveService->getService()->getName()) == 'ISP'){
                    $compensationValidity = $objCompensation->getIspHours();
                }
            }

            $objActivityLog = new UserActivityLog();
            $objActivityLog->SetAdmin("Cron");
            $objActivityLog->SetUser($objCustomer);
            $objActivityLog->setActivity('Add Compensation for user');
            $objActivityLog->setDescription("Service location ".$compensationValidity." Hour(s) compensation has been added for user. Username: " . $objCustomer->getUsername());
            $objActivityLog->setIp("N/A");
            $objActivityLog->setSessionId("N/A");
            $objActivityLog->setVisitedUrl("N/A");
            $em->persist($objActivityLog);
            $em->flush();

            //Save paypal response in PaypalExpressCheckOutCustomer table
            $objPurchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->findOneByOrderNumber($orderNumber);
            if(!$objPurchaseOrder){
                $objPurchaseOrder = new PurchaseOrder();
            } 

            $objPurchaseOrder->setPaymentMethod($this->objPaymentMethod);
            $objPurchaseOrder->setSessionId('');
            $objPurchaseOrder->setOrderNumber($orderNumber);
            $objPurchaseOrder->setUser($objCustomer);
            $objPurchaseOrder->setTotalAmount(0);
            $objPurchaseOrder->setPaymentStatus('Completed');
            $objPurchaseOrder->setCompensationValidity($compensationValidity);

            $em->persist($objPurchaseOrder);
            $em->flush();
            $insertIdPurchaseOrder = $objPurchaseOrder->getId();

            if($insertIdPurchaseOrder){

                $objServicePurchase = new ServicePurchase();

                $objServicePurchase->setService($objUserActiveService->getService());
                $objServicePurchase->setUser($objCustomer);
                $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                $objServicePurchase->setPackageId($objUserActiveService->getPackageId());
                $objServicePurchase->setPackageName($objUserActiveService->getPackageName());
                $objServicePurchase->setActualAmount(0);
                $objServicePurchase->setPayableAmount(0);
                $objServicePurchase->setPaymentStatus('Completed');
                $objServicePurchase->setRechargeStatus(1);
                $objServicePurchase->setSessionId('');
                $objServicePurchase->setIsUpgrade(0);
                $objServicePurchase->setIsAddon(0);
                $objServicePurchase->setTermsUse(1);
                $objServicePurchase->setIsCompensation(1);

                $em->persist($objServicePurchase);
                $em->flush();

                return $objPurchaseOrder;
            }
        }

        return false;
    }

    private function generateUniqueString($length = 20) {
        $chars = array_merge(range(0,9), range('A', 'Z'), range('a', 'z'));

        $key = '';
        for($i=0; $i < $length; $i++) {
            $key .= $chars[mt_rand(0, count($chars) - 1)];
        }

        return $key;
    }

    private function generateOrderNumber() {
        $now = time();
        $rand  = strtoupper($this->generateUniqueString(8));
        return $now . $rand;
    }

    public function convertDaysIntoHours($days){
        $totalHours = 0;
        if($days){
            $totalHours = ceil($days * 24);
        }
        return $totalHours;
    }

    public function convertHoursIntoDays($hours){
        $totalDays = 0;
        if($hours){
            $totalDays = ceil($hours / 24);
        }
        return $totalDays;
    }
}
