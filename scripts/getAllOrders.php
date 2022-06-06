<?php

use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();
$appState = $objectManager->get('\Magento\Framework\App\State');
$appState->setAreaCode('adminhtml');
$orderCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');

$now = new \DateTime();
$collection = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
$orderCollection = $collection->create()->addFieldToSelect(array('*'));
$orderCollection->addFieldToFilter('created_at', ['lteq' => $now->format('Y-m-d H:i:s')])->addFieldToFilter('created_at', ['gteq' => $now->format('2021-08-01 00:00:00')]);
$cant = 0;

echo $now->format('Y-m-d H:i:s'). " ----------- ". $now->format('2020-08-01 00:00:00');

echo count($orderCollection);
foreach ($orderCollection as $order){
    
    echo "Order Id ". $order->getId()."\n";
    echo "Order Id ". $order->getIncrementId()."\n";
    echo "Create at " . $order->getCreatedAt()."\n";

    $date1 = $order->getCreatedAt();
    $date2 = date("Y-m-d H:i:s");
    echo "Now " . $date2."\n";

    $timestamp1 = strtotime($date1);
    $timestamp2 = strtotime($date2);
    echo "Difference between two dates is " . $hour = abs($timestamp2 - $timestamp1)/(60*60) . " hour(s) \n";
    echo "Hours ". round($hour)."\n";
    echo "-----------------------------------------\n";

    /* $shipping = $order->getShippingAddress()->getData();
    $billing =  $order->getBillingAddress()->getData();
    var_dump($shipping);
    echo "ship: ".$shipping["ship_to_code"]."\n";
    echo "bill: ".$billing["pay_to_code"]."\n"; */
    
}