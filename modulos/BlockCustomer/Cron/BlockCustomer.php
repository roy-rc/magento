<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\BlockCustomer\Cron;

use Customcode\Logger\Model\Logger;

class BlockCustomer
{

    protected $logger;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function executeOrig()
    {
        $this->logger->addInfo("Cronjob BlockCustomer is executed.");
    }
    
    public function execute(){
        if(false):
        $logger = new Logger("BlockCustomer");
        $logger->info(" -- Init --");
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        //*****************loading Customer session *****************//
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');

        //******** Checking whether customer is logged in or not ********//
        $safe_customer = array(
            "rramos@nipon.cl",
            "jhon.gold@yopmail.com",
            "jhon.silver@yopmail.com",
            "nizum1@nipon.cl",
            "nizum2@nipon.cl",
            "nizum3@nipon.cl",
           "subaccount1@yopmail.com",
            "alfredolopeznunes@gmail.com",
            "ramosroiman@gmail.com",
            "niponb2b@nipon.cl",
            "niponb2b_silver@nipon.cl",
            "niponb2b_bronze@nipon.cl",
            "niponb2b_potencial@nipon.cl",
            "adity@nipon.cl",
            "cristobal_cabezas@nipon.cl",
            "iduvauchelle@nipon.cl", 
        );
        $customerFactory = $objectManager->create('Magento\Customer\Model\CustomerFactory')->create();
        $customerCollection = $customerFactory->getCollection()
            ->addAttributeToSelect("*")
            ->load();
        foreach ($customerCollection AS $customer) {
            $customer_email = $customer->getEmail();
            if(in_array($customer_email,$safe_customer)){
                $order_collection = $objectManager->create('Magento\Sales\Model\Order')->getCollection()
                    ->addAttributeToFilter('customer_email', $customer_email );
                if(count($order_collection)){
                    $last_order = $objectManager->create('Magento\Sales\Model\Order')->getCollection()
                        ->addAttributeToFilter('customer_email', $customer_email )
                        ->setOrder('created_at','DESC')->getFirstItem();

                    $block_user = false;
                    $logger->info("Customer Email: ".$customer_email);
                    $logger->info("Order ID: ".$last_order->getEntityId());
                
                    $order = $last_order;

                    $logger->info("Order Id: ".$order->getEntityId());
                    $logger->info("Customer Email: ".$order->getCustomerEmail());
                    $date1 = $order->getCreatedAt();
                    $date2 = date("Y-m-d H:i:s");
                    $timestamp1 = strtotime($date1);    
                    $timestamp2 = strtotime($date2);
                    $days = abs($timestamp2 - $timestamp1)/(60 * 60 * 24);
                    if(round($days) >= 90){
                        $logger->info("Days: ".round($days));
                        $logger->info("Order date: ".$order->getCreatedAt());
                        $logger->info("Block customer: ".$customer_email);
                        $logger->info("LoginStatus: ".$customer->getLoginStatus());
                        $block_user = true;
                    }
                }else{
                    $block_user = true;
                }
                if($block_user){
                    $logger->info("customer will be blockded: ".$customer_email);
                    /* 
                    $customer->setLoginStatus(0);
                    $customer->save();
                    */
                }

            }
        }
        $logger->info(" -- End CheckOrder --");
        endif;
    }
}

