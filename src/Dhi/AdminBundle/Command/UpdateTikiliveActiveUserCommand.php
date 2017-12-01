<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class UpdateTikiliveActiveUserCommand extends ContainerAwareCommand {

    private $output;
    private $con;
    private $tikiliveCon;
    private $promoCodeService;

    protected function configure() {
        $this->setName('dhi:update-tikilive-active-user')->setDescription('Update last login time and last IP Address of Tikilive users.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $this->output           = $output;
        $this->output->writeln("\n####### Start Update Tikilive Active User Cron at " . date('Y M j H:i') . " #######\n");
        $currentDate            = new \DateTime();
        $this->promoCodeService = $this->getContainer()->get('PromoCodeService');
        $em                     = $this->getContainer()->get('doctrine')->getManager();
        $this->con              = $em->getConnection();

        $tikiliveEm        = $this->getContainer()->get('doctrine')->getManager('tikilive');
        $this->tikiliveCon = $tikiliveEm->getConnection();

        // Import new tikilive users
        $this->importTikiliveUsers();

        // Update expired plans
        $sql                  = "UPDATE tikilive_active_user SET is_promo_code_expired = true WHERE promo_code_expiry_date < :expiryDate";
        $stmp                 = $this->con->prepare($sql);
        $stmp->execute(array('expiryDate' => $currentDate->format('Y-m-d H:i:s')));

        $sql                  = "SELECT distinct t.tikilive_user_id  FROM tikilive_active_user t WHERE t.is_active = 1 AND t.is_promo_code_expired = 0";
        $stmp                 = $this->con->prepare($sql);
        $stmp->execute();
        $tikiliveActiveUsers  = $stmp->fetchAll();

        $this->updateTikiliveActiveUserInfo($tikiliveActiveUsers);
        $this->tikiliveCon = null;
        $this->promoCodeService->unsetTikiliveConnection();
        $this->output->writeln("\n####### End Cron #######\n");
    }

    private function importTikiliveUsers(){

        $sql = "SELECT distinct tu.promo_code FROM tikilive_active_user tu";
        $statement = $this->con->prepare($sql);
        $statement->execute();
        $promoCodes = $statement->fetchAll();
        $promoCodes = array_column($promoCodes, 'promo_code');

        $sql = "SELECT c.coupon_code, uc.redeem_date, uc.user_id, u.user_username, MAX(log.user_log_time) as LastLoginTime, MAX(log.user_log_ip) as LastLoginIP, log.user_log_country 
        FROM coupon c 
            INNER JOIN users_coupon uc on c.coupon_id = uc.coupon_id
            INNER JOIN user u ON uc.user_id = u.user_id 
            LEFT JOIN user_profile up ON u.user_id = up.user_id 
            LEFT JOIN usergroup ug ON u.usergroup_id = ug.usergroup_id 
            LEFT JOIN user_log log ON u.user_id = log.user_id 
        WHERE u.user_confirmed = 'Yes' AND u.user_deleted = '0' AND u.user_status = '1'";

        if (!empty($promoCodes)) {
            $promoCodes    = implode($promoCodes, ',');
            $strPromoCodes = "'".str_replace(',', "','", $promoCodes)."'";
            $sql           .= " AND c.coupon_code NOT IN (". $strPromoCodes .")";
        }
        $sql .= " GROUP BY c.coupon_code, uc.user_id";

        $tikiliveStmp       = $this->tikiliveCon->prepare($sql);
        $tikiliveStmp->execute();
        $tikilivePromoCodes = $tikiliveStmp->fetchAll();
        $count              = 0;

        $tikiliveStmp = null;
        if (!empty($tikilivePromoCodes)) {
            foreach ($tikilivePromoCodes as $key => $tikilivePromoCode) {

                if (!empty($tikilivePromoCode['redeem_date'])) {
                    $redeemDate = new \DateTime();
                    $redeemDate->setTimestamp($tikilivePromoCode['redeem_date']);
                }

                $arrParams = array(
                    'operation'        => 'insert',
                    'tikilive_user_id' => $tikilivePromoCode['user_id'],
                    'coupon_code'      => $tikilivePromoCode['coupon_code'],
                    'redemptionDate'   => (!empty($redeemDate) ? $redeemDate : NULL),
                    'lastLoginTime'    => $tikilivePromoCode['LastLoginTime'],
                    'userId'           => $tikilivePromoCode['user_id'],
                    'userUsername'     => $tikilivePromoCode['user_username'],
                    'userLogCountry'   => $tikilivePromoCode['user_log_country'],
                    'lastLoginIP'      => $tikilivePromoCode['LastLoginIP'],
                );
                $returnFlag = $this->promoCodeService->tikiliveActiveUser($arrParams);

                if($returnFlag == true){
                    $count ++;
                }
            }
        }

        $this->output->writeln("\n####### Total $count New Tikilive Active User(s) Inserted Successfully #######\n");
    }

    private function updateTikiliveActiveUserInfo($tikiliveActiveUsers){
        $count = 0;
        if (!empty($tikiliveActiveUsers)) {

            $activeUsers = $this->getActiveUsers($tikiliveActiveUsers);
            if (!empty($activeUsers)) {
                foreach ($activeUsers as $key => $user) {
                    if (!empty($user['tikilive_user_id'])) {

                        $arrParams = array(
                            'operation'        => 'update',
                            'tikilive_user_id' => $user['tikilive_user_id'],
                            'lastLoginTime'    => $user['LastLoginTime'],
                            'userId'           => $user['user_id'],
                            'userUsername'     => $user['user_username'],
                            'userLogCountry'   => $user['user_log_country'],
                            'lastLoginIP'      => $user['LastLoginIP']
                        );

                        if (strtolower($user['user_confirmed']) != 'Yes' || $user['user_deleted'] != '0' || $user['user_status'] != '1' ) {
                            $arrParams['userId'] = '';
                        }

                        $this->promoCodeService->tikiliveActiveUser($arrParams);
                        $count++;
                    }
                }
            }
        }

        $this->output->writeln("\n####### Total $count Tikilive Active User(s) Updated Successfully #######\n");
    }

    private function getActiveUsers($activeUsers){
        $activeUsers = array_column($activeUsers, 'tikilive_user_id');
        $userIds    = implode($activeUsers, ',');

        $activeUserSql = "SELECT u.user_id, u.user_username, u.user_id as tikilive_user_id, MAX(log.user_log_time) as LastLoginTime, MAX(log.user_log_ip) as LastLoginIP, log.user_log_country , u.user_confirmed, u.user_deleted, u.user_status FROM user u 
                LEFT JOIN user_profile up ON u.user_id = up.user_id 
                LEFT JOIN usergroup ug ON u.usergroup_id = ug.usergroup_id 
                LEFT JOIN user_log log ON u.user_id = log.user_id 
                WHERE u.user_id IN ('".str_replace(',', "','", $userIds)."')
                GROUP BY u.user_id, u.user_username";

        $statement = $this->tikiliveCon->prepare($activeUserSql);
        $statement->execute();
        return $statement->fetchAll();
    }}
