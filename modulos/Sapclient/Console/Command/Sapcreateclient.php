<?php


namespace Customcode\Sapclient\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Customcode\Logger\Model\Logger;

class Sapcreateclient extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        //$name = $input->getArgument(self::NAME_ARGUMENT);
        //$option = $input->getOption(self::NAME_OPTION);
        //$output->writeln("Hello " . $name);
        
        /* $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/create-customer.log');
        $logger = new \Laminas\Log\Logger();
        $logger->addWriter($writer); */

        // Get Website ID
        $logger = new Logger("createClient");
        $logger->info("Init Create Client");

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeId = $storeManager->getStore()->getId();
        $state = $objectManager->get('\Magento\Framework\App\State');
        $state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $websiteId = $storeManager->getStore($storeId)->getWebsiteId();

        $allCustomer = $this->getAllCustomers($objectManager);
        $customers = $this->getAllCustomersB2B();
        $i = count($allCustomer);
        echo "cant-customer:". count($allCustomer)."\n";

        /* $create_client = array(
                "76849252-2C",
                "77365420-4C",
                "78399110-1C",
        ); */
        $cant = 0;
        foreach($customers["Response"] as $customerSap){
            $error_mail = false;
            $logger->info("RUT: ".$customerSap["Rut"]);
            $logger->info($customerSap["CodigoCliente"]);
            /* if( !in_array($customerSap["CodigoCliente"], $create_client) ):
                continue;
            endif; */
            foreach($customerSap["Direcciones"] as $direccion){ 
                if(strtolower(utf8_decode($direccion["TipoDireccion"])) == 'facturación'){
                    if (!filter_var($direccion["EmailB2B"], FILTER_VALIDATE_EMAIL)) {
                        $logger->info("Error EMAIL:" . $customerSap["CodigoCliente"]." >>> " .$direccion["EmailB2B"]);
                        $error_mail = true;
                    }
                }
            }
            if($error_mail){
                continue;
            }
            
            $rut = $customerSap["Rut"];
            $codigo_cliente = $customerSap["CodigoCliente"];
            //por cada direccion de facturacion crear un usuario y asignar esa direccion
            $envio = array();
            $facturacion = array();
            foreach($customerSap["Direcciones"] as $direccion){ 

                $region_sap = str_replace("región del ","",strtolower(utf8_decode($direccion["NombreRegion"])));
                $region_sap = str_replace("región de ","",$region_sap);
                $region_sap = str_replace("región ","",$region_sap);

                if(strtolower(utf8_decode($direccion["TipoDireccion"])) == 'envío'){
                    if(substr(strtolower(utf8_decode($direccion["NombreDireccion"])), -1) == "o" or substr(strtolower(utf8_decode($direccion["NombreDireccion"])), -1) == "n"){
                        $dir_idx = 0;
                    }elseif(substr(strtolower(utf8_decode($direccion["NombreDireccion"])), -1) == '1'){
                        $dir_idx = 1;
                    }else{
                        $dir_idx = substr(strtolower(utf8_decode($direccion["NombreDireccion"])), -1);
                    }
                    $envio[$dir_idx] = array(
                        "prefix" => utf8_decode($direccion["NombreDireccion"]),
                        "region" => $region_sap,
                        "comuna" => utf8_decode($direccion["Comuna"]),
                        "calle" => utf8_decode($direccion["Calle"]),
                        "cliente" => utf8_decode($customerSap["NombreCliente"]),
                        "NombreDireccion" => utf8_decode($direccion["NombreDireccion"]),
                    );
                }
                if(strtolower(utf8_decode($direccion["TipoDireccion"])) == 'facturación'){
                    if(substr(strtolower(utf8_decode($direccion["NombreDireccion"])), -1) == "n" or substr(strtolower(utf8_decode($direccion["NombreDireccion"])), -1) == "o"){
                        $dir_idx = 0;
                    }elseif(substr(strtolower(utf8_decode($direccion["NombreDireccion"])), -1) == '1'){
                        $dir_idx = 1;
                    }else{
                        $dir_idx = substr(strtolower(utf8_decode($direccion["NombreDireccion"])), -1);
                    }
                    $facturacion[$dir_idx] = array(
                        "prefix" => utf8_decode($direccion["NombreDireccion"]),
                        "region" => $region_sap,
                        "comuna" => utf8_decode($direccion["Comuna"]),
                        "calle" => utf8_decode($direccion["Calle"]),
                        "cliente" => utf8_decode($customerSap["NombreCliente"]),
                        "NombreDireccion" => utf8_decode($direccion["NombreDireccion"]),
                        "email" => utf8_decode($direccion["EmailB2B"]),
                        "nombre" => utf8_decode($direccion["NameUserB2B"]),
                        "username" => utf8_decode($direccion["NameUserB2B"]),
                        "userpass" => utf8_decode($direccion["PassUserB2B"]),
                        "typeuser" => strtolower(utf8_decode($direccion["TipoUserB2B"])),

                    );
                }
            }
            if(count($envio) != count($facturacion)){
                $logger->info("ERROR count direccion envio != facturacion" . $customerSap["CodigoCliente"]);
                continue; 
            }
            $supervisor_id = "";
            $subcuenta = array();
            
            foreach($facturacion as $key => $direccion){
                
                if(strtolower($direccion["email"]) != "null"):
                    
                    echo "KEY: ".$key."\n";
                    //var_dump($facturacion);
                    //var_dump($envio);
                    echo "EmailB2B: ".$direccion["email"]."\n";
                    //var_dump($facturacion);
                    if(strtolower($direccion["email"]) != "null"){
                        $email = strtolower($direccion["email"]);
                        $password = $direccion["userpass"];
                        $tipo_usuario = $direccion["typeuser"];
                    }else{
                        $email = 'cliente_'.$i++.'@nipon.cl'; //Solo para actualizacion de pruebas
                        //$email = $allCustomer_by_rut[$customerSap["Rut"]]["email"]; //Solo para actualizacion de pruebas
                        $password = "pass1234";
                        $tipo_usuario = $direccion["typeuser"];
                    }
                    
                    $logger->info("email pass:" . $email. " " .$password);
                    if (!key_exists($email,$allCustomer)) {
                        try {
                            //$customer = $objectManager->get('\Magento\Customer\Api\Data\CustomerInterfaceFactory')->create();
                            $customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                            $customer->setWebsiteId($websiteId);
                            
                            $customer->setEmail( $email );
                            $logger->info("first name: ".$customerSap["NombreCliente"]." -- ".utf8_decode($customerSap["NombreCliente"]));
                            echo "First NAME --- ".utf8_decode(str_replace("&","-",$direccion["username"]));
                            $customer->setFirstname(str_replace("–","-",utf8_decode(str_replace("&","-",$direccion["username"]))));
                            echo "1. ".str_replace("–","-",utf8_decode(str_replace("&","-",$customerSap["NombreCliente"]) )) ;
                            $customer->setLastname(str_replace("–","-",utf8_decode(str_replace("&","-",$customerSap["NombreCliente"]) )) );
                            $customer->setRut($rut);
                            $customer->setCodigoCliente($codigo_cliente);
                            $customer->setEjecutivoName($customerSap["Ejecutivo"]);
                            $customer->setEjecutivoEmail($customerSap["Mail"]);
                            $customer->setEjecutivoPhone($customerSap["Telefono"]);
                            $customer->setEjecutivoCode($customerSap["CodigoEjecutivo"]);
                            $customer->setEjecutivoTelefonicoCode($customerSap["CodigoEjecutivoTel"]);

                            $customer->setEjecutivoTelefonicoName($customerSap["Ejecutivo Telefonico"]);
                            $customer->setEjecutivoTelefonicoEmail($customerSap["Mail Ejecutivo tel"]);
                            $customer->setEjecutivoTelefonicoPhone($customerSap["Telefono Ejecutivo Tel"]);

                            $customer->setCondicionPago($customerSap["Condicion Pago"]);

                            $customer->setTipoUsuario($tipo_usuario);
                            
                            switch ($customerSap["Segmento"]) {
                                case "Potencial":
                                    $customer->setGroupId(9);
                                    break;
                                case "Target A":
                                    $customer->setGroupId(10);
                                    break;
                                case "Target B":
                                    $customer->setGroupId(11);
                                    break;
                                case "Preferentes":
                                    $customer->setGroupId(12);
                                    break;
                                case "Vip":
                                    $customer->setGroupId(13);
                                    break;
                                case "Premium":
                                    $customer->setGroupId(14);
                                    break;
                                case "Especialistas":
                                    $customer->setGroupId(15);
                                    break;
                                case "Lubricentros":
                                    $customer->setGroupId(16);
                                    break;
                                case "Concesionarios":
                                    $customer->setGroupId(17);
                                    break;
                                case "Servitecas":
                                    $customer->setGroupId(18);
                                    break;
                                case "Baterias":
                                    $customer->setGroupId(19);
                                    break;
                                case "Talleres":
                                    $customer->setGroupId(20);
                                    break;
                                default:
                                    $customer->setGroupId(9);
                            }

                            //$hashedPassword = $objectManager->get('\Magento\Framework\Encryption\EncryptorInterface')->getHash($password, true);
                            //$objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface')->save($customer, $hashedPassword);

                            $customer->setPassword($password); 
                            $customer->setForceConfirmed(true);
                            $customer->setWebsiteId($websiteId);
                            $customer->save();
                            echo "\n>>>>> ".$cant++." - ".$codigo_cliente."\n";

                            //consultar cliente creado para asignar direccion de despacho y facturacion
                            $customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                            $customer->setWebsiteId($websiteId)->loadByEmail($email);
                            $logger->info("Customer ID:".$customer->getId());
                            $logger->info($facturacion[$key]["typeuser"]);
                            $isSupervisor = false;
                            if($facturacion[$key]["typeuser"] == "supervisor"){
                                $supervisor_id = $customer->getId();
                                $isSupervisor = true;
                            }else{
                                $subcuenta[] = $customer->getId();
                            }
                            //$logger->info("Customer create - CustomerId: ".$customer->getId());
                            //Crear todas las direcciones para el usuario supervisor
                            if($isSupervisor){
                                echo "supervisor\n";
                                foreach($envio as $key_envio => $dir){
                                    $this->setAddress($customer->getId(),$customerSap, $envio[$key_envio], $facturacion[$key_envio], $objectManager, $isSupervisor);
                                }
                            }else{
                                $this->setAddress($customer->getId(),$customerSap, $envio[$key], $facturacion[$key], $objectManager, $isSupervisor);
                            }
                            
                            //Notificar cliente
                            /* 
                            $customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                            $customer->setWebsiteId($websiteId)->loadByEmail($email);
                            $customer->sendNewAccountEmail();
                            */
                            $logger->info("Customer create:");
                        } catch (Exception $e) {
                            $logger->info("Error: " . $e->getMessage());
                            //$logger->info("Customer create - ERROR: " . $e->getMessage()); 
                        }
                    }else{
                        echo "Update Email:".$email."\n\n";
                        $customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                        $customer->setWebsiteId($websiteId)->loadByEmail($email);

                        $customer->setFirstname( str_replace("–","-",utf8_decode(str_replace("&","-",$direccion["username"]))) );
                        echo "2. ".utf8_decode(str_replace("&","-",$customerSap["NombreCliente"]) );
                        $customer->setLastname( str_replace("–","-",utf8_decode(str_replace("&","-",$customerSap["NombreCliente"]) ))  );
                        $customer->setRut($rut);
                        $customer->setCodigoCliente($codigo_cliente);
                        $customer->setEjecutivoName($customerSap["Ejecutivo"]);
                        $customer->setEjecutivoEmail($customerSap["Mail"]);
                        $customer->setEjecutivoPhone($customerSap["Telefono"]);
                        $customer->setEjecutivoCode($customerSap["CodigoEjecutivo"]);
                        $customer->setEjecutivoTelefonicoCode($customerSap["CodigoEjecutivoTel"]);

                        $customer->setEjecutivoTelefonicoName($customerSap["Ejecutivo Telefonico"]);
                        $customer->setEjecutivoTelefonicoEmail($customerSap["Mail Ejecutivo tel"]);
                        $customer->setEjecutivoTelefonicoPhone($customerSap["Telefono Ejecutivo Tel"]);
                        
                        $customer->setCondicionPago($customerSap["Condicion Pago"]);
                        $customer->setTipoUsuario($tipo_usuario);

                        switch ($customerSap["Segmento"]) {
                            case "Potencial":
                                $customer->setGroupId(9);
                                break;
                            case "Target A":
                                $customer->setGroupId(10);
                                break;
                            case "Target B":
                                $customer->setGroupId(11);
                                break;
                            case "Preferentes":
                                $customer->setGroupId(12);
                                break;
                            case "Vip":
                                $customer->setGroupId(13);
                                break;
                            case "Premium":
                                $customer->setGroupId(14);
                                break;
                            case "Especialistas":
                                $customer->setGroupId(15);
                                break;
                            case "Lubricentros":
                                $customer->setGroupId(16);
                                break;
                            case "Concesionarios":
                                $customer->setGroupId(17);
                                break;
                            case "Servitecas":
                                $customer->setGroupId(18);
                                break;
                            case "Baterias":
                                $customer->setGroupId(19);
                                break;
                            case "Talleres":
                                $customer->setGroupId(20);
                                break;
                            default:
                                $customer->setGroupId(9);
                        }

                        $customer->setPassword($password); 
                        $customer->setForceConfirmed(true);
                        $customer->setWebsiteId($websiteId);
                        $customer->save();
                        echo  "\n>>>>> ".$cant++." -  UPDATE -". $codigo_cliente."\n";
                        /* -------------------------------- */
                        /* -------------------------------- */

                        $isSupervisor = false;
                        if($facturacion[$key]["typeuser"] == "supervisor"){
                            $supervisor_id = $customer->getId();
                            $isSupervisor = true;
                        }else{
                            $subcuenta[] = $customer->getId();
                        }
                        if($isSupervisor){
                            echo "supervisor\n";
                            foreach ($customer->getAddresses() as $address) {
                                echo "Eliminar address: ".$address->getId()."\n";
                                $address->delete();
                            }
                            foreach($envio as $key_envio => $dir){
                                $this->setAddress($customer->getId(),$customerSap, $envio[$key_envio], $facturacion[$key_envio], $objectManager, $isSupervisor);
                            }
                        }else{
                            foreach ($customer->getAddresses() as $address) {
                                $mg_calle = $address->getStreet();
                                $address_no_exist = true;
                                $region_mg = $this->getRegionId($address->getRegionId(),"id");
                                $sap_envio = $envio[$key];
                                echo strtolower(utf8_decode($sap_envio["calle"])) ."------" .strtolower(utf8_decode($mg_calle[0]))."\n";
                                echo strtolower($this->replaceCharacter($sap_envio["region"])) ."------" . $this->replaceCharacter(strtolower($region_mg))."\n";
                                echo strtolower(utf8_decode($sap_envio["comuna"])) ."------" . strtolower(utf8_decode($address->getCity()))."\n";
                                
                                if( strtolower(utf8_decode($sap_envio["calle"])) == strtolower(utf8_decode($mg_calle[0])) AND 
                                    strtolower($this->replaceCharacter($sap_envio["region"])) == $this->replaceCharacter(strtolower($region_mg)) AND
                                    strtolower(utf8_decode($sap_envio["comuna"])) == strtolower(utf8_decode($address->getCity())) ){
                                    $address_no_exist = false;
                                }
                                $address_no_exist = true;
                                if($address_no_exist){
                                    
                                    $region_id = "";
                                    $region_mg = $this->getRegionId(0,"array");
                                    foreach($region_mg as $key_region => $region){
                                        $pos = strpos( strtolower($this->replaceCharacter($sap_envio["region"])) , strtolower($this->replaceCharacter($region)) );
                                        if ($pos !== false) {
                                            $region_id = $key_region;
                                            break;
                                        }
                                    }
                                    echo $customer->getId()." ". $address->getId()." ".$region_id."\n";
                                    $ship_to_code=$sap_envio["NombreDireccion"];
                                    $pay_to_code =$facturacion[$key]["NombreDireccion"];
                                    $this->updateAddress($customer->getId(), $address->getId(), $customerSap, $sap_envio, $ship_to_code, $pay_to_code, $region_id, true, true, $objectManager);
                                }
                                
                            }
                        }
                    }
                endif;
            }
            
            //Asignar cuenta principal y sibcuentas
            /* $logger->info("supervisor_id".$supervisor_id);
            var_dump($subcuenta);
            if($supervisor_id){
                if(count($subcuenta) > 1){
                    foreach($subcuenta as $customer_id){
                        $this->insertSubAccounts($supervisor_id, $customer_id);
                    }
                }else{
                    $logger->info("Error SIN SUBCUENTAS ".$customerSap["CodigoCliente"]);
                }
            }else{
                $logger->info("Error NO SUPERVISOR ".$customerSap["CodigoCliente"]);
            } */
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("customcode:sap-create-client");
        $this->setDescription("Create client frmo WS SAP");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }

    public function getAllCustomersB2B(){
        $url = "http://201.238.200.3:8000/WS/services/item/getSCN_B2B.xsjs";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
        
    }

    public function setAddress($customerId, $customerData, $envio, $facturacion, $objectManager, $isSUpervisor=false){
        $logger = new Logger("createClient");
        $region_mg = $this->getRegionId(0,"array");
        if($isSUpervisor){
            //creando direccioes de supervisor
            if($facturacion["typeuser"] == "supervisor"){
                //direccion actual es de supervisor
                $IsDefaultBilling = $IsDefaultShipping = true;
            }else{
                $IsDefaultBilling = $IsDefaultShipping = false;
            }
        }else{
            //creando direccion de cliente comprador
            $IsDefaultBilling = $IsDefaultShipping = true;
        }
        if($envio["region"] == $facturacion["region"] AND $envio["comuna"] == $facturacion["comuna"] AND $envio["calle"] == $facturacion["calle"]){
            //direccion de envio y facturacion iguales
            $envio_facturacion = true;
        }else{
            //direccion de envio y facturacion diferentes
            $envio_facturacion = false;
            $IsDefaultBilling = false;
        }
        $region_id = "";
        foreach($region_mg as $key => $region){
            $pos = strpos( strtolower($this->replaceCharacter($envio["region"])) , strtolower($this->replaceCharacter($region)) );
            if ($pos !== false) {
                $region_id = $key;
                break;
            }
        }
        if($region_id){
            $logger->info("tipo: ".$envio["NombreDireccion"]);
            $ship_to_code = "";
            $pay_to_code = "";
            if($envio_facturacion){
                $ship_to_code = $envio["NombreDireccion"];
                $pay_to_code = $facturacion["NombreDireccion"];
            }else{
                $ship_to_code = $envio["NombreDireccion"];
            }
            $this->createAddress($customerId, $customerData, $envio, $ship_to_code, $pay_to_code, $region_id, $IsDefaultBilling, $IsDefaultShipping, $objectManager);
        }else{
            $logger->info("ERROR ENVIO Region No encontrada" . $customerData["CodigoCliente"]);
        }
        if(!$envio_facturacion){
            if(!$isSUpervisor){
                $IsDefaultBilling = true;
            }
            if($isSUpervisor AND $facturacion["typeuser"] == "supervisor"){
                $IsDefaultShipping = false;
                $IsDefaultBilling = true;
            }
            $region_id = "";
            foreach($region_mg as $key => $region){
                $pos = strpos( strtolower($this->replaceCharacter($facturacion["region"])) , strtolower($this->replaceCharacter($region)) );
                if ($pos !== false) {
                    $region_id = $key;
                    break;
                }
            }
            if($region_id){
                $logger->info("tipo: ".$envio["NombreDireccion"]);
                $ship_to_code = "";
                $pay_to_code = $facturacion["NombreDireccion"];
                $this->createAddress($customerId, $customerData, $facturacion, $ship_to_code, $pay_to_code, $region_id, $IsDefaultBilling, $IsDefaultShipping, $objectManager);
            }else{
                $logger->info("ERROR FACTURACION Region No encontrada" . $customerData["CodigoCliente"]);
            }
        }

    }
    public function setAddressOLD($customerId, $customerData, $envio, $facturacion, $objectManager, $isSUpervisor=false){
        $logger = new Logger("createClient");
        $region_mg = $this->getRegionId(0,"array");
    
        //envio ===  despacho 
        if($facturacion["typeuser"] == "supervisor"){
            $IsDefaultBilling = $IsDefaultShipping = true;
        }else{
            $envio_facturacion = false;
            $IsDefaultBilling = $IsDefaultShipping = false;
            if($envio["region"] == $facturacion["region"] AND $envio["comuna"] == $facturacion["comuna"] AND $envio["calle"] == $facturacion["calle"]){
                //envio y facturacion son las mismas
                $envio_facturacion = true;
                $IsDefaultBilling = $IsDefaultShipping = true;
            }
        }
        //if($envio["calle"] == $facturacion["calle"] AND $envio["comuna"] == $facturacion["comuna"] AND $envio["region"] == $facturacion["region"]){
        //    $region_id = "";
        //    foreach($region_mg as $key => $region){
        //        $pos = strpos( strtolower($this->replaceCharacter($envio["region"])) , strtolower($this->replaceCharacter($region)) );
        //        if ($pos !== false) {
        //            $region_id = $key;
        //            break;
        //        }
        //    }
        //    if($region_id){
        //        $this->createAddress($customerId, $customerData, $envio,"","", $region_id, $IsDefaultBilling, $IsDefaultShipping, $objectManager);
        //    }else{
        //        $logger->info("ERROR Region No encontrada" . $customerData["CodigoCliente"]);
        //    }
        //}else{
            $region_id = "";
            foreach($region_mg as $key => $region){
                $pos = strpos( strtolower($this->replaceCharacter($envio["region"])) , strtolower($this->replaceCharacter($region)) );
                if ($pos !== false) {
                    $region_id = $key;
                    break;
                }
            }
            if( $envio_facturacion ){
                //envio y facturacion son iguales solo secrea 1 direccion y se asigna shipping y billing 
                if($region_id){
                    $logger->info("tipo: ".$envio["NombreDireccion"]);
                    $this->createAddress($customerId, $customerData, $envio,"","", $region_id, $IsDefaultBilling, $IsDefaultShipping, $objectManager);
                }else{
                    $logger->info("ERROR ENVIO Region No encontrada" . $customerData["CodigoCliente"]);
                }
            }else{
                if($region_id){
                    $logger->info("tipo: ".$envio["NombreDireccion"]);
                    $this->createAddress($customerId, $customerData, $envio,"","", $region_id, false, $IsDefaultShipping, $objectManager);
                }else{
                    $logger->info("ERROR ENVIO Region No encontrada" . $customerData["CodigoCliente"]);
                }
    
                $region_id = "";
                foreach($region_mg as $key => $region){
                    $pos = strpos( strtolower($this->replaceCharacter($facturacion["region"])) , strtolower($this->replaceCharacter($region)) );
                    if ($pos !== false) {
                        $region_id = $key;
                        break;
                    }
                }
                if($region_id){
                    $logger->info("tipo: ".$envio["NombreDireccion"]);
                    $this->createAddress($customerId, $customerData, $facturacion,"","" ,$region_id, $IsDefaultBilling, false, $objectManager);
                }else{
                    $logger->info("ERROR FACTURACION Region No encontrada" . $customerData["CodigoCliente"]);
                }
            }
            
        //}
    }

    public function createAddress($customerId, $customerData, $direccion, $ship_to_code, $pay_to_code, $region_id, $IsDefaultBilling, $IsDefaultShipping, $objectManager){
        $addresss = $objectManager->get('\Magento\Customer\Model\AddressFactory');
        $address = $addresss->create();
        echo "3. >>>> -";
        $address->setCustomerId($customerId)
                //->setPrefix($direccion["prefix"])
                ->setFirstname(utf8_decode($customerData["NombreCliente"]))
                ->setLastname(".")
                ->setCountryId("CL")
                //->setPostcode("00000")
                ->setCity($direccion["comuna"])
                ->setRegionId($region_id)
                ->setTelephone($customerData["TelefonoCliente"])
                ->setCompany("")
                ->setStreet($direccion["calle"])
                ->setIsDefaultBilling($IsDefaultBilling)
                ->setIsDefaultShipping($IsDefaultShipping)
                ->setSaveInAddressBook('1')
                ->setship_to_code($ship_to_code)
                ->setpay_to_code($pay_to_code);
        $address->save();
        echo "Addres Creada:".$address->getId()."\n";
    }

    public function updateAddress($customerId, $address_id, $customerData, $direccion,$ship_to_code, $pay_to_code, $region_id, $IsDefaultBilling, $IsDefaultShipping, $objectManager){
        $address_rep = $objectManager->get('\Magento\Customer\Api\AddressRepositoryInterface');
        
        $customerObj = $objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
        echo "4. >>>> -";
        foreach ($customerObj->getAddresses() as $address_customer)
        {
            if($address_id == $address_customer->getId()){
                $address_customer->setPrefix("");
                $address_customer->setFirstname(utf8_decode($customerData["NombreCliente"]));
                $address_customer->setLastname(".");
                $address_customer->setCountryId("CL");
                //$address_customer->setPostcode("00000");
                $address_customer->setCity($direccion["comuna"]);
                $address_customer->setRegionId($region_id);
                $address_customer->setTelephone($customerData["TelefonoCliente"]);
                $address_customer->setCompany("");
                $address_customer->setStreet(array($direccion["calle"]));
                $address_customer->setIsDefaultBilling($IsDefaultBilling);
                $address_customer->setIsDefaultShipping($IsDefaultShipping);
                $address_customer->setship_to_code($ship_to_code);
                $address_customer->setpay_to_code($pay_to_code);
                $address_customer->save();

            }
        }
        $address = $address_rep->getById($address_id);
        
        /* $address->setCustomerId($customerId)
                ->setPrefix("")
                ->setFirstname(utf8_decode($customerData["NombreCliente"]))
                ->setLastname(".")
                ->setCountryId("CL")
                //->setPostcode("00000")
                ->setCity($direccion["comuna"])
                ->setRegionId($region_id)
                ->setTelephone("-")
                ->setCompany("")
                ->setStreet(array($direccion["calle"]))
                ->setIsDefaultBilling($IsDefaultBilling)
                ->setIsDefaultShipping($IsDefaultShipping)
                ->setship_to_code($ship_to_code)
                ->setpay_to_code($pay_to_code);

        $addressExtension = $address->getExtensionAttributes();
        if(null === $addressExtension){
            // $addressExtensionFactory is instance of \Magento\Customer\Api\Data\AddressExtensionFactory
            $addressExtensionFactory = $objectManager->get('\Magento\Customer\Api\Data\AddressExtensionFactory');
            $addressExtension =$addressExtensionFactory->create(); 
        }
        
        $addressExtension->setShipToCode($direccion["prefix"]);
        $address->setExtensionAttributes($addressExtension);

        $address_rep->save($address); */
    }

    public function getAllCustomers($objectManager){
        $list_customer = array();
        $customerFactory = $objectManager->create('Magento\Customer\Model\CustomerFactory')->create();
        
        //Get customer collection
        $customerCollection = $customerFactory->getCollection()
                ->addAttributeToSelect("*")
                ->load();
        foreach ($customerCollection AS $customer) {
            $list_customer[$customer->getEmail()] = array(
                "firstname"=> $customer->getFirstname(), 
                "lastname" => $customer->getLastname(),
                "rut" => $customer->getRut()
            );
        }
        return $list_customer;
    }

    public function getAllCustomersByRut($objectManager){
        $list_customer = array();
        $customerFactory = $objectManager->create('Magento\Customer\Model\CustomerFactory')->create();
        
        //Get customer collection
        $customerCollection = $customerFactory->getCollection()
                ->addAttributeToSelect("*")
                ->load();
        foreach ($customerCollection AS $customer) {
            $list_customer[$customer->getRut()] = array(
                "firstname"=> $customer->getFirstname(), 
                "lastname" => $customer->getLastname(),
                "rut" => $customer->getRut(),
                "email" => $customer->getEmail(),
            );
        }
        return $list_customer;
    }

    public function replaceCharacter($str){
        $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                                'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                                'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                                'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                                'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y','Ã³'=>'o' );
        return  strtr( $str, $unwanted_array );
    }

    public function insertSubAccounts($supervisor_id, $customer_id){
        $_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection= $_resources->getConnection();
    
        $themeTable = $_resources->getTableName('wkcs_subaccounts');
        $sql = "INSERT INTO " . $themeTable . "(customer_id, main_account_id, parent_account_id, available_permissions)
                VALUES (".$customer_id.",".$supervisor_id.",".$supervisor_id.",'cart-approval-required,can-merge-own-cart-to-main-cart,can-approve-carts,can-place-order,can-view-main-wishlist,can-add-to-main-wishlist,can-remove-from-main-wishlist,can-view-main-account-order-list,can-view-main-account-order-details,will-get-notified-on-order-place-by-main-account');";
        $connection->query($sql);
    }

    public function getSubAccounts($supervisor_id){
        $_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection= $_resources->getConnection();
    
        $themeTable = $_resources->getTableName('wkcs_subaccounts');
        $sql = "select * from {$themeTable} where main_account_id = {$supervisor_id}";

        $collection = $connection->fetchAll($sql);
        return $collection;
    }

    public function getRegionId($region_id = 0, $return_type = "array"){
        
        $region_mg = array(
            "661"=>"Aisén",//Aisén del General Carlos Ibañez del Campo
            "662"=>"Antofagasta",
            "663"=>"Arica y Parinacota",//Arica y Parinacota
            "665"=>"Atacama",
            "666"=>"Biobío",
            "667"=>"Coquimbo",
            "664"=>"Araucanía",//La Araucanía
            "668"=>"Libertador",//Libertador General Bernardo O'Higgins
            "669"=>"Lagos",//Los Lagos
            "670"=>"Ríos",//Los Ríos
            "671"=>"Magallanes",
            "672"=>"Maule",
            "673"=>"Ñuble",
            "674"=>"Metropolitana",//Región Metropolitana de Santiago
            "675"=>"Tarapacá",
            "676"=>"Valparaíso",
        );

        if($return_type == "array"){
            return $region_mg;
        }else{
            return $region_mg[$region_id];
        }
        
    }
    
}