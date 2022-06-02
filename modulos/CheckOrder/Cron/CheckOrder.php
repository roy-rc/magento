<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\CheckOrder\Cron;

use Customcode\Logger\Model\Logger;


class CheckOrder
{

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    )
    {
        $this->orderFactory = $orderFactory;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $logger = new Logger("checkOrder");
        $orderModel = $this->orderFactory->create()->addFieldToFilter('main_table.status', ['in' => "holded"]);
        $logger->info(" -- Init --");
        if (count($orderModel)) {
            $logger->info(" -- Init CheckOrder --");
            $logger->info("Cant holded orders:".count($orderModel));
            foreach ($orderModel as $order) {
                $logger->info("Order IncrementId: ".$order->getIncrementId());
                $nv_exist = $this->checkOrderSap($order->getIncrementId(), $logger);
                //$nv_exist = "Existe";
                if($nv_exist == "Existe"){
                    if($order->canUnhold()) {
                        $order->unhold()->save();
                    }
                    $this->updateOrder($order->getIncrementId(), $logger);
                }else{
                    $date1 = $order->getCreatedAt();
                    $date2 = date("Y-m-d H:i:s");
                    $timestamp1 = strtotime($date1);
                    $timestamp2 = strtotime($date2);
                    $hour = abs($timestamp2 - $timestamp1)/(60*60);
                    if(round($hour) >= 120){
                        if($order->canUnhold()) {
                            $order->unhold()->save();
                        }
                        $this->cancelOrder($order->getIncrementId(), $logger);
                    }
                }
            }
            $logger->info(" -- End CheckOrder --");
        }
        
    }

    public function updateOrder($OrderIncrementId, &$logger){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($OrderIncrementId); 
        $order->setState("processing")->setStatus("processing");
        $order->addStatusHistoryComment("Order updated from holded to processing");
        $order->save();
        $logger->info("updateOrder | ".$OrderIncrementId." Order updated from holded to processing");
    }

    public function cancelOrder($OrderIncrementId, &$logger){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($OrderIncrementId); 
        $order->cancel();
        $order->addStatusHistoryComment("Your order has been cancelled successfully.");
        $order->save();
        $logger->info("updateOrder | ".$OrderIncrementId." Order updated from holded to cancel");
    }

    public function checkOrderSap($incrementId, &$logger){
        $order_id_sap = (int)$incrementId;
        $url = "http://201.238.200.3:8000/WS/services/item/getNV.xsjs?pedido={$order_id_sap}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        if (($result = curl_exec($ch)) === FALSE) {
            $logger->info("checkOrderSap | cURL error".curl_error($ch));
            die();
        } else {
            $logger->info("checkOrderSap | connectSAP Done | ".$incrementId);
        }
        curl_close($ch);
        $result = str_replace('"NV: ','"NV": "',$result);
        $result = json_decode($result, true);
        $nv_exists = "";
        if($result["ResponseStatus"] === "Error"){
            $logger->info("checkOrderSap | "."Error:".$result["Response"]["message"]["value"]);
        }else{
            $nv_exists = $result["Response"]["NV"];
            $logger->info("checkOrderSap | Result".$incrementId . " - ". $result["Response"]["NV"]);
        }
        return $nv_exists;
    }
}

