<?php
use Magento\Framework\App\Bootstrap;
require '../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);


$objectManager = $bootstrap->getObjectManager();
$appState = $objectManager->get('\Magento\Framework\App\State');
//$appState->setAreaCode('frontend');
$appState->setAreaCode('adminhtml');
$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');


$customerFactory = $objectManager->create('Magento\Customer\Model\CustomerFactory')->create();
        

$fp = fopen('customer.csv', 'w');
$line = array("email","Firstname","Lastname","Rut","Codigo Cliente","Tipo Usuario");
fputcsv($fp, $line);
//Get customer collection
$customerCollection = $customerFactory->getCollection()
        ->addAttributeToSelect("*")
        ->load();
foreach ($customerCollection AS $customer) {
    $list_customer = array(
        "email" => $customer->getEmail(),
        "firstname"=> $customer->getFirstname(), 
        "lastname" => $customer->getLastname(),
        "rut" => $customer->getRut(),
        "codigo_cliente" => $customer->getCodigoCliente(),
        "tipo_usuario" => $customer->getTipoUsuario(),
    );
    fputcsv($fp, $list_customer);
}

exit();
$customerId = 1;
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$customerObj = $objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
$customerAddress = array();

foreach ($customerObj->getAddresses() as $address)
{
    $customerAddress[] = $address->toArray();
    echo "ID:".$address->getId()."\n";
    echo "street:".$address->getStreet()[0]."\n";
    echo "ship:".$address->getship_to_code()."\n";
    echo "pay:".$address->getpay_to_code()."\n";

    $address->setpay_to_code("Code 002");
    $address->setship_to_code("Code 001");
    $address->setPrefix("Direccion");
    $address->setFirstname("Roiman ");
    $address->setLastname("Ramos");
    $address->setCountryId("CL");
    $address->setCity("Santiago");
    $address->setRegionId(674);
    $address->setTelephone("987654321");
    $address->setCompany("");
    $address->setStreet(array("Vicuña Mackenna 1725"));
    $address->setIsDefaultBilling(true);
    $address->setIsDefaultShipping(true);
    $address->save();
}




foreach ($customerAddress as $customerAddres) {

    echo $customerAddres['street']."\n";
    echo $customerAddres['city']."\n";
    echo $customerAddres['ship_to_code']."\n";
    echo $customerAddres['pay_to_code']."\n";
}
?>