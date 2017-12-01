<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class UpdateTikilivePromocodeCommand extends ContainerAwareCommand {

    private $output;
    private $con;
    private $tikiliveCon;

    protected function configure() {
        $this->setName('dhi:update-tikilive-promo-codes')->setDescription('Update redemption date of promo code.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $this->output->writeln("\n####### Start Update Redeemtion Date of Tikilive Promo Code Cron at " . date('Y M j H:i') . " #######\n");

        $em                = $this->getContainer()->get('doctrine')->getManager();
        $this->con         = $em->getConnection();
        
        $tikiliveEm        = $this->getContainer()->get('doctrine')->getManager('tikilive');
        $this->tikiliveCon = $tikiliveEm->getConnection();
        
        $sql                  = "SELECT GROUP_CONCAT(t.promocode) AS codes FROM tikilive_promo_code t WHERE t.is_redeemed = 'Yes' AND t.tikilive_redeemed_date IS NULL";
        $stmp                 = $this->con->prepare($sql);
        $stmp->execute();
        $objRedeemedPromocode = $stmp->fetch();
        $result               = $this->getTikilivePromocodes($objRedeemedPromocode);

        $this->output->writeln("\n####### End Cron #######\n");
    }

    private function getTikilivePromocodes($objRedeemedPromocode){
        if ($objRedeemedPromocode['codes']) {

            $arrCodes = explode(',', $objRedeemedPromocode['codes']);
            $inQuery = implode(',', array_fill(0, count($arrCodes), '?'));

            $sql = "SELECT c.coupon_id, c.coupon_code, uc.coupon_id, uc.redeem_date, uc.user_id  FROM coupon c INNER JOIN users_coupon uc on c.coupon_id = uc.coupon_id WHERE c.coupon_code IN (". $inQuery .");";
            $statement = $this->tikiliveCon->prepare($sql);

            foreach ($arrCodes as $i => $code)
                $statement->bindValue(($i+1), $code);

            $statement->execute();
            $tikilivePromoCodes = $statement->fetchAll();
            $this->updatePromoCodes($tikilivePromoCodes);
        } else {
            $this->output->writeln("\n####### No Tikilive Promo Code Found #######\n");
        }
    }

    private function updatePromoCodes($tikilivePromoCodes){
        $count = 0;
        if (!empty($tikilivePromoCodes)) {
            $promoCodeService = $this->getContainer()->get('PromoCodeService');
            foreach ($tikilivePromoCodes as $key => $promoCode) {
                
                if (!empty($promoCode['redeem_date'])) {

                    $redeemDate = new \DateTime();
                    $redeemDate->setTimestamp($promoCode['redeem_date']);

                    $condition = array(
                        'rDate' => $redeemDate->format("Y-m-d H:i:s"),
                        'code'  => $promoCode['coupon_code']
                    );

                    $sql = "UPDATE tikilive_promo_code SET tikilive_redeemed_date = :rDate WHERE promocode = :code";
                    $statement = $this->con->prepare($sql);
                    if ($statement->execute($condition)) {
                        $count++;
                    }

                    /*
                        if(!empty($promoCode['user_id'])){
                            $arrParams = array(
                                'operation'        => 'insert',
                                'tikilive_user_id' => $promoCode['user_id'],
                                'coupon_code'      => $promoCode['coupon_code'],
                                'redemptionDate'   => $redeemDate
                            );
                            $promoCodeService->tikiliveActiveUser($arrParams);
                        }
                    */
                }
            }
        }

        $this->output->writeln("\n####### Total $count Tikilive Promo Code(s) Updated #######\n");
    }
}
