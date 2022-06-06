<?php 
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();
$appState = $objectManager->get('\Magento\Framework\App\State');
$appState->setAreaCode('adminhtml');

$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
$storeId = $storeManager->getStore()->getId();
$state = $objectManager->get('\Magento\Framework\App\State');

$websiteId = $storeManager->getStore($storeId)->getWebsiteId();



$users = array();
$users[0]["email"] = "niponb2b@nipon.cl";
$users[0]["password"] = "Nipon.5498";
$users[0]["firstname"] = "Nipon Gold";
$users[0]["lastname"] = "Importadora de repuestos";
$users[0]["rut"] = "12312312-1";
$users[0]["ejecutivo_name"] = "Ejecutivo Nipon";
$users[0]["ejecuitivo_email"] = "ejecutivo_nipon@nipon.cl";
$users[0]["ejecutivo_phone"] = "+56 98765 4321";
$users[0]["ejecutivo_code"] = 0;
$users[0]["tipo_usuario"] = "comprador";
$users[0]["condicion_pago"] = "Contado";
$users[0]["segmento"] = "Gold";

$users[1]["email"] = "niponb2b_silver@nipon.cl";
$users[1]["password"] = "Nipon.5498";
$users[1]["firstname"] = "Nipon Silver";
$users[1]["lastname"] = "Importadora de repuestos";
$users[1]["rut"] = "12312312-2";
$users[1]["ejecutivo_name"] = "Ejecutivo Nipon";
$users[1]["ejecuitivo_email"] = "ejecutivo_nipon@nipon.cl";
$users[1]["ejecutivo_phone"] = "+56 98765 4321";
$users[1]["ejecutivo_code"] = 0;
$users[1]["tipo_usuario"] = "comprador";
$users[1]["condicion_pago"] = "Contado";
$users[1]["segmento"] = "Silver";

$users[2]["email"] = "niponb2b_bronze@nipon.cl";
$users[2]["password"] = "Nipon.5498";
$users[2]["firstname"] = "Nipon Bronze";
$users[2]["lastname"] = "Importadora de repuestos";
$users[2]["rut"] = "12312312-3";
$users[2]["ejecutivo_name"] = "Ejecutivo Nipon";
$users[2]["ejecuitivo_email"] = "ejecutivo_nipon@nipon.cl";
$users[2]["ejecutivo_phone"] = "+56 98765 4321";
$users[2]["ejecutivo_code"] = 0;
$users[2]["tipo_usuario"] = "comprador";
$users[2]["condicion_pago"] = "Contado";
$users[2]["segmento"] = "Bronze";

$users[3]["email"] = "niponb2b_potencial@nipon.cl";
$users[3]["password"] = "Nipon.5498";
$users[3]["firstname"] = "Nipon Potencial";
$users[3]["lastname"] = "Importadora de repuestos";
$users[3]["rut"] = "12312312-4";
$users[3]["ejecutivo_name"] = "Ejecutivo Nipon";
$users[3]["ejecuitivo_email"] = "ejecutivo_nipon@nipon.cl";
$users[3]["ejecutivo_phone"] = "+56 98765 4321";
$users[3]["ejecutivo_code"] = 0;
$users[3]["tipo_usuario"] = "comprador";
$users[3]["condicion_pago"] = "Contado";
$users[3]["segmento"] = "Potencial";

$users[4]["email"] = "adity@nipon.cl";
$users[4]["password"] = "Adity.1587";
$users[4]["firstname"] = "Adity";
$users[4]["lastname"] = "Agencia";
$users[4]["rut"] = "12312312-5";
$users[4]["ejecutivo_name"] = "Ejecutivo Nipon";
$users[4]["ejecuitivo_email"] = "ejecutivo_nipon@nipon.cl";
$users[4]["ejecutivo_phone"] = "+56 98765 4321";
$users[4]["ejecutivo_code"] = 0;
$users[4]["tipo_usuario"] = "comprador";
$users[4]["condicion_pago"] = "Contado";
$users[4]["segmento"] = "Potencial";

$users[5]["email"] = "cristobal_cabezas@nipon.cl";
$users[5]["password"] = "Cris.97563";
$users[5]["firstname"] = "Cristóbal";
$users[5]["lastname"] = "Cabezas";
$users[5]["ejecutivo_name"] = "Ejecutivo Nipon";
$users[5]["ejecuitivo_email"] = "ejecutivo_nipon@nipon.cl";
$users[5]["ejecutivo_phone"] = "+56 98765 4321";
$users[5]["ejecutivo_code"] = 0;
$users[5]["tipo_usuario"] = "comprador";
$users[5]["condicion_pago"] = "Contado";
$users[5]["rut"] = "12312312-6";
$users[5]["segmento"] = "Gold";

foreach($users as $user){
    $customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
    $customer->setWebsiteId($websiteId);
    $customer->setEmail( $user["email"] );
    $customer->setFirstname( $user["firstname"] );
    $customer->setLastname( $user["lastname"] );
    $customer->setRut( $user["rut"] );

    $customer->setEjecutivoName( $user["ejecutivo_name"] );
    $customer->setEjecutivoEmail( $user["ejecuitivo_email"] );
    $customer->setEjecutivoPhone( $user["ejecutivo_phone"] );
    $customer->setEjecutivoCode( $user["ejecutivo_code"] );
    $customer->setEjecutivoTelefonicoCode( $user["ejecutivo_code"] );

    $customer->setEjecutivoTelefonicoName();
    $customer->setEjecutivoTelefonicoEmail( $user["ejecuitivo_email"] );
    $customer->setEjecutivoTelefonicoPhone( $user["ejecutivo_phone"] );

    $customer->setCondicionPago( $user["condicion_pago"] );

    $customer->setTipoUsuario( $user["tipo_usuario"] );
    //Platinum = 4
    //Gold = 6
    //Silver = 7
    //Bronze
    //Potencial
    //null
    switch ( $user["segmento"] ) {
        case "Gold":
            $customer->setGroupId(4);
            break;
        case "Silver":
            $customer->setGroupId(6);
            break;
        case "Bronze":
            $customer->setGroupId(7);
            break;
        case "Potencial":
            $customer->setGroupId(9);
            break;
        default:
            $customer->setGroupId(9);
    }
    //$hashedPassword = $objectManager->get('\Magento\Framework\Encryption\EncryptorInterface')->getHash($password, true);
    //$objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface')->save($customer, $hashedPassword);

    $customer->setPassword($user["password"]); 
    $customer->setForceConfirmed(true);
    $customer->setWebsiteId($websiteId);
    $customer->save();
}
?>