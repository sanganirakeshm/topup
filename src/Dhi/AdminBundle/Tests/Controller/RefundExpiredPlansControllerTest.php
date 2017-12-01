<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * 
 */
class RefundExpiredPlansControllerTest extends WebTestCase {
    
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();

        $this->adminUsername = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->adminPasword = static::$kernel->getContainer()->getParameter('test_admin_password');
        $this->con = static::$kernel->getContainer()->get('database_connection');
        $this->userService = $this->getActiveUserService();
    }

    /*public function testIndexAction() {
        $client = static::createClient(
            array(),
            array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            )
        );
        $this->doLogin($this->adminUsername, $this->adminPasword, $client);

        if ($this->userService) {

            $crawler = $client->request('POST', '/admin/customer/plans/expired/'.$this->userService['user_id']);
            $this->assertEquals('Dhi\AdminBundle\Controller\RefundExpiredPlansController::indexAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("Refund Expired Plans")')->count());
        }
    }

    public function testlistJsonAction(){
        $client = static::createClient(
            array(),
            array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            )
        );
        $this->doLogin($this->adminUsername, $this->adminPasword, $client);

        if ($this->userService) {
            $crawler = $client->request('GET', '/admin/customer/plans/expired/list/'.$this->userService['user_id']);

            $this->assertEquals('Dhi\AdminBundle\Controller\RefundExpiredPlansController::listJsonAction',
                    $client->getRequest()->attributes->get('_controller'));

            $this->assertEquals(0, $crawler->filter('html:contains("No Record Found!")')->count());
        }
    }*/

    public function testrefundAction(){
        $client = static::createClient(
            array(),
            array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            )
        );
        $this->doLogin($this->adminUsername, $this->adminPasword, $client);

        $crawler = $client->request(
            'POST',
            '/admin/customer/plans/expired/refund/'.$this->userService['user_id'],
            array(
                "userServiceId"       => $this->userService['id'],
                "submitRefundPayment" => "1",
                "packageType"         => $this->userService['service'],
                "refundAmount"        => $this->userService['payable_amount'],
                "finalRefundAmount"   => $this->userService['payable_amount'],
                "refundServiceId"     => $this->userService['id'],
                "userId"              => $this->userService['user_id'],
                "confirmPage"         => "1",
                "refundServiceId[]"   => $this->userService['id'],
                "processAmount"       => $this->userService['payable_amount'],
                "add"                 => "Proceed This Amount"
            ),
            array(),
            array(
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            )
        );
        $client->enableProfiler();
        $this->assertEquals('Dhi\AdminBundle\Controller\RefundExpiredPlansController::refundAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("has been refunded successfully")')->count());
    }

    /**
     *  General function for Admin login
     */
    private function doLogin($username, $password, $client) {

        $crawler = $client->request('POST', '/admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function getActiveUserService(){
        
        $sql = "SELECT us.id, us.user_id, us.payable_amount, us.purchase_order_id , s.name AS service
        FROM user_services us 
        INNER JOIN service_purchase sp ON us.service_purchase_id = sp.id 
        INNER JOIN service s ON us.service_id = s.id 
        WHERE us.status = 0 AND us.refund = 0 AND sp.payment_status IN ('Completed','Expired') ORDER BY id DESC";

        $query = $this->con->prepare($sql);
        $query->execute();
        $result = $query->fetch();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}