<?php
namespace Customcode\Saporder\Observer;
use Customcode\Logger\Model\Logger;

class Order implements \Magento\Framework\Event\ObserverInterface
{
    protected $_helperSwis;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,        
        \Swissup\CheckoutFields\Helper\Data $helperSwis,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,        
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ){
        $this->_customerSession = $customerSession;            
        $this->_helperSwis = $helperSwis;
        $this->_order = $order;
        $this->_orderRepository = $orderRepository;        
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->timezone = $timezone;  
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $logger = new Logger("observerOrder");
        $logger->info(" -- Init Observer Order --");

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $order = $observer->getEvent()->getOrder();
        $logger->info("order status:".$order->getIncrementId()." - ".$order->getStatus());
        $datosCustom = $this->_helperSwis->getOrderFieldsValues($order, array('n_oc_customer', 'fecha_oc_customer','tipo_despacho_checkout','direccion_oficina_checkout'));
        $fecha_oc = '';
        $numero_oc = '';
        $tipo_despacho = '';
        $direccion_despacho = '';
        foreach ($datosCustom as $field){
            $logger->info("datosCustom:" . $field->getLabel());
            if('Fecha de Orden de Compra' == $field->getLabel()){
                $fecha_oc =  date ( 'Y-m-d' , strtotime ( $field->getValue() ) ); 
            }                        
            if('Numero de Orden de Compra' == $field->getLabel()){ 
                $numero_oc = $field->getValue(); 
            }   
            if('Tipo de despacho' == $field->getLabel()){
                $tipo_despacho = implode(",", $field->getValue()) ; 

                if($tipo_despacho == "Oficina"){
                    $tipo_despacho = 2;
                }elseif($tipo_despacho == "Domicilio"){
                    $tipo_despacho = 1;
                }else{
                    $tipo_despacho = 99;
                }
            } 
            if('Dirección de Oficina' == $field->getLabel()){
                $direccion_despacho = $field->getValue(); 
            }                     
        }

        $order->setOcCliente($numero_oc);
        $order->setFechaOcCliente($fecha_oc);
        $order->setTipoDespacho($tipo_despacho);
        $order->setDireccionOficina($direccion_despacho);

        $order->addStatusHistoryComment("nro oc y fecha de oc customer");

        $detalleOrden = $order->getData();
        
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeId = $storeManager->getStore()->getId();
        $state = $objectManager->get('\Magento\Framework\App\State');
        //$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $websiteId = $storeManager->getStore($storeId)->getWebsiteId();

        $CustomerModel = $objectManager->create('Magento\Customer\Model\Customer');
        $CustomerModel->setWebsiteId($websiteId);
        $CustomerModel->loadByEmail($detalleOrden['customer_email']);
        
        $is_valid_order = $this->validOrderSap($CustomerModel->getRut(),round($detalleOrden['grand_total']), $logger);
        $msg_valid_sap = "";
        if($is_valid_order["is_valid"]){
            //$msg_valid_sap = "";
            $msg_valid_sap = "Cupo Aprobado para esta operacion, en breves momentos se le eviara un email con el detalle de la compra y la fecha de despacho estimada.";
        }else{
            if($is_valid_order["CupoAprobado"] <= 0){
                $msg_valid_sap = "Su cupo ha sido superado, favor contactar a su vendedor o nuestra area de finanzas para regularizar su situacion.";                
            }
            if(strtolower($is_valid_order["ErrorMensaje1"]) != "null" OR $is_valid_order["ErrorMensaje1"] != "" OR $is_valid_order["ErrorMensaje1"]!= 0 OR $is_valid_order["ErrorMensaje1"] != '0'){
                $logger->info("ErrorMensaje1:".$is_valid_order["ErrorMensaje1"]);
                $msg_valid_sap = $msg_valid_sap ."  <br>". $is_valid_order["ErrorMensaje1"];
            }
            if(strtolower($is_valid_order["ErrorMensaje2"]) != "null" OR $is_valid_order["ErrorMensaje2"] != "" OR $is_valid_order["ErrorMensaje2"]!= 0 OR $is_valid_order["ErrorMensaje2"] != '0'){
                $logger->info("ErrorMensaje2:".$is_valid_order["ErrorMensaje2"]);
                $msg_valid_sap = $msg_valid_sap ."  <br>". $is_valid_order["ErrorMensaje2"];
            }
        }
        $pos = strpos($msg_valid_sap, "El Cliente tiene vencidos");
        $msg_valid_sap_adicional_text = "";
        if ($pos !== false) {
            $msg_valid_sap_adicional_text = "  <br>". " Favor ponerse en contacto con nuestra area de cobranzas para regularizar sus pagos.";
        }
        $msg_valid_sap = str_replace("El Cliente tiene vencidos","Usted tiene un total de facturas vencidas de", $msg_valid_sap);
        
        $msg_valid_sap = $msg_valid_sap . $msg_valid_sap_adicional_text;

        $logger->info("msg_valid_sap:".utf8_decode($msg_valid_sap));

        $order->addStatusHistoryComment("msg_valid_sap: ". utf8_decode($msg_valid_sap));

        $order->setIsValidSap(utf8_decode($msg_valid_sap));

        $order->save();
        $logger->info("-- End Observer Order --");
    }



    public function old_execute(\Magento\Framework\Event\Observer $observer)
    {
        $logger = new Logger("sendOrder");
        $order = $observer->getEvent()->getOrder();
        $logger->info(" -- Init Send Order to SAP --");
        $logger->info("order status:".$order->getIncrementId()." - ".$order->getStatus());

        $payment = $order->getPayment(); 
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getCode();

        $nombre_cliente = $order->getBillingAddress()->getFirstname().' '.$order->getBillingAddress()->getLastname();
        $dir = $order->getBillingAddress()->getStreet();
        $detalleOrden = $order->getData();

        $logger->info(json_encode($detalleOrden));
        
        $U_IdTransaccionWeb = $order->getIncrementId();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeId = $storeManager->getStore()->getId();
        $state = $objectManager->get('\Magento\Framework\App\State');
        //$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $websiteId = $storeManager->getStore($storeId)->getWebsiteId();

        $CustomerModel = $objectManager->create('Magento\Customer\Model\Customer');
        $CustomerModel->setWebsiteId($websiteId);
        $CustomerModel->loadByEmail($detalleOrden['customer_email']);
        
        $is_valid_order = $this->validOrder($CustomerModel->getRut(),round($detalleOrden['grand_total']), $logger);
        $order->addStatusHistoryComment("Validating CupoAprobado in SAP:".$is_valid_order);
        $nuevaOrden = array();
        $nuevaOrden['DocType'] = 'dDocument_Items';
        $nuevaOrden['DocDate'] = date("Ymd", strtotime($detalleOrden['created_at']));
        $nuevaOrden['DocDueDate'] = date("Ymd", strtotime($detalleOrden['created_at']));
        
        //para crear la orden como draft
        if(!$is_valid_order){
            $nuevaOrden["DocObjectCode"] = "17";
        }

        $datosCustom = $this->_helperSwis->getOrderFieldsValues($order, array('n_oc_customer', 'fecha_oc_customer'));
        $fecha_oc = '';
        $numero_oc = '';
        foreach ($datosCustom as $field){
            if('Fecha de Orden de Compra' == $field->getLabel()){
                $fecha_oc =  date ( 'Y-m-d' , strtotime ( $field->getValue() ) ); 
            }                        
            if('Numero de Orden de Compra' == $field->getLabel()){ 
                $numero_oc = $field->getValue(); 
            }                        
        }
        $logger->info("Fecha OC Cliente:".$fecha_oc);
        $logger->info("Numero OC Cliente:".$numero_oc);

        $order->setOcCliente($numero_oc);
        $order->setFechaOcCliente($fecha_oc);

        $ejecutivo_code = 0;
        if($CustomerModel->getEjecutivoCode() != '' OR $CustomerModel->getEjecutivoCode() != "null"){
            $ejecutivo_code = $CustomerModel->getEjecutivoCode();
        }elseif($CustomerModel->getEjecutivoTelefonicoCode() != '' OR $CustomerModel->getEjecutivoTelefonicoCode() != "null"){
            $ejecutivo_code = $CustomerModel->getEjecutivoTelefonicoCode();
        }
        
        //$nuevaOrden["CardCode"] = $CustomerModel->getRut()."C";
        $nuevaOrden["CardCode"] = $CustomerModel->getCodigoCliente();
        $nuevaOrden["SalesPersonCode"] = $ejecutivo_code;
        $nuevaOrden["U_WebStore"] = 'Magento';
        $nuevaOrden["U_RutWeb"] =  $CustomerModel->getRut();
        $nuevaOrden["U_NombreWeb"] = $nombre_cliente;
        $nuevaOrden["U_EMailWeb"] = $detalleOrden['customer_email'];
        $nuevaOrden["U_ComunaWeb"] =  $order->getBillingAddress()->getCity();
        $nuevaOrden["U_CiudadWeb"] = $order->getBillingAddress()->getRegion();
        $nuevaOrden["U_FonoWeb"] = $order->getBillingAddress()->getTelephone();
        $nuevaOrden["U_DireccionWeb"] = $dir[0];
        $nuevaOrden["U_MetodoPagoWeb"] = $methodTitle;
        $nuevaOrden["U_IdTransaccionWeb"] = $U_IdTransaccionWeb;
        $nuevaOrden["U_NumeroWeb"] = $order->getIncrementId();
        $nuevaOrden["U_Id_Transporte"] = "";
        $nuevaOrden["U_URLEtiqueta"] = "";
        $nuevaOrden["U_OficinaTransporte"] = $detalleOrden['shipping_description'];
        $nuevaOrden["U_CanalOrigen"] = 19;
        $nuevaOrden["U_tipo_mediopago"] = "";
        $nuevaOrden["U_numero_tarjeta"] = "";
        $nuevaOrden["U_valido_hasta"] = "";
        $nuevaOrden["ShipToCode"] = $order->getShippingAddress()->getPrefix();
        $nuevaOrden["PayToCode"] = $order->getBillingAddress()->getPrefix();
        
        $nuevaOrden["U_FolioRef"] = $numero_oc; //Numero de orden de compra
        $nuevaOrden["U_FchRef"] = $fecha_oc; //fecha de orden de compra
        $nuevaOrden["U_TpoDocRef"] = "801";        

        $select_shipping = 0;
        $created_at = $this->timezone->date(new \DateTime($detalleOrden['created_at']));
        $today = $created_at->format('Y-m-d');

        foreach ($order->getAllItems() as $item){
            $product = $item->getProduct();
            $sku = $product->getSku();
            $logger->info(round($item->getPrice()) ." -- ".round($item->getOriginalPrice()));

            $discount = ( round($item->getOriginalPrice()) - round($item->getPrice()) ) / round($item->getOriginalPrice());
            $discount =  round($discount * 100); //porcentaje de descuento aplicado
            $logger->info("discount:".$discount);
            $logger->info("discount:".round($discount));
            $elementos[] = array( 'ItemCode' => $sku, 'Quantity' => $item->getQtyOrdered(), 'WarehouseCode' => '00CD', 'TaxCode' => 'IVA', 'PriceAfterVAT' => round($item->getPrice()*1.19), 'PriceAfVAT' => round($item->getPrice()*1.19));
            //$elementos[] = array( 'ItemCode' => $sku, 'Quantity' => $item->getQtyOrdered(), 'WarehouseCode' => '00CD', 'TaxCode' => 'IVA', 'DiscountPercent'=>round($discount), 'PriceAfterVAT' => round($item->getPrice()));
            //$elementos[] = array( 'ItemCode' => $sku, 'Quantity' => $item->getQtyOrdered(), 'WarehouseCode' => '00CD', 'TaxCode' => 'IVA', 'SalesPersonCode' => 86);                 
        
            //check shipping
            $type_shipping = $this->defineShipping($product->getOem(), $product->getSku(), $item->getQtyOrdered(), $created_at, $order, $logger);
            if($type_shipping > $select_shipping){
                $select_shipping = $type_shipping;
            }
        }
        //set shipping text
        
        $weekend = false;
        if(date('N', strtotime($today)) >= 6){ //weekend
            $weekend = true;
            $logger->info("Is weekend");
        }
        switch ($select_shipping) {
            case 1:
                if($weekend){
                    $text_shipping = "Tu pedido sera despachado durante el dia de hoy.";
                    $fecha_promesa = date ( 'Y-m-d' );
                }else{
                    $text_shipping = "Tu pedido sera despachado el proximo dia habil.";
                    $fecha_promesa = date ( 'Y-m-d' , strtotime ( '1 weekdays' ) );
                }
                break;
            case 2:
                if($weekend){
                    $text_shipping = "Tu pedido sera despachado en 1 día hábil a contar de la fecha de tu compra.";
                    $fecha_promesa = date ( 'Y-m-d' , strtotime ( '1 weekdays' ) );
                }else{
                    $text_shipping = "Tu pedido sera despachado el proximo dia habil.";
                    $fecha_promesa = date ( 'Y-m-d' , strtotime ( '1 weekdays' ) );
                }
                break;
            case 3:
                    $text_shipping = "Tu pedido sera despachado en 2 días hábiles a contar de la fecha de tu compra.";
                    $fecha_promesa = date ( 'Y-m-d' , strtotime ( '2 weekdays' ) );
                break;
        }

        $order->setCompromisoDespacho($text_shipping);
        $logger->info($order->getCompromisoDespacho());
        $logger->info($order->getCompromisoDespacho());
        $logger->info("Actualizando Orden con compromiso de despacho");
        
        $nuevaOrden["U_FechaPromesa"] = $fecha_promesa;
        $nuevaOrden["U_HoraPromesa"] = $created_at->format('H:i:s');
        // 1 = despacho transporte, 2 despacho santiago, 3 retiro cliente
        if($detalleOrden['shipping_description'] == "Retira cliente"){
            $despacho_trasnporte = 3;
        }else{
            if($order->getBillingAddress()->getRegion() == "Región Metropolitana de Santiago"){
                $despacho_trasnporte = 2;
            }else{
                $despacho_trasnporte = 1;
            }
        }
        $nuevaOrden["U_TipoDespacho"] = $despacho_trasnporte;

        if($elementos){ 
            $nuevaOrden['DocumentLines'] = $elementos; 
        }
        $valorDespacho = (float) $order->getShippingAmount();
        $extraExpensive = array('JurisdictionCode' => 'IVA', 'JurisdictionType' => 1, 'TaxAmount' => 0, 'TaxRate' => 19 );
        $extraExpensive2 = array('ExpenseCode' => 2, 'TaxSum' => 0, 'TaxCode' => 'IVA', 'LineTotal' => number_format($valorDespacho / 1.19,0,'',''), 'DocExpenseTaxJurisdictions' => array($extraExpensive));
    
        $nuevaOrden['DocumentAdditionalExpenses'][] = $extraExpensive2;
    
        //$nuevaOrden['TaxExtension'] = array('CityS' => 'SANTIAGO', 'CountryB' => 'CL');
        //$nuevaOrden['AddressExtension'] = array('ShipToCity' => $order->getBillingAddress()->getRegion(), 'ShipToCountry' => 'CL');        
        
        
        $logger->info("Total de orden:" . $detalleOrden['grand_total']);
        if($is_valid_order){
            $logger->info(json_encode($nuevaOrden));
            $logger->info("Send Order to SAP");
            $order->addStatusHistoryComment("Send order to SAP as NV");
            $responseWS = $this->sendOrderToSap($nuevaOrden);
        }else{
            $nuevaOrden["DocObjectCode"] = "17";
            $logger->info(json_encode($nuevaOrden));
            $logger->info("Send Draft to SAP");
            $order->addStatusHistoryComment("Send order to SAP as DRAFT");
            $responseWS = $this->sendDraftOrderToSap($nuevaOrden);
        }

        $logger->info(json_encode($responseWS));
        $order->addStatusHistoryComment(json_encode($responseWS));
        $order->save();

        if($this->isSuccess($responseWS,$logger)){ //Ingreso exitoso a SAP
            //actualizar ordern a Processing y crear Invoice
            $order->setState("processing")->setStatus("processing");
            $order->addStatusHistoryComment("Order sent to SAP and updated to processing");
            $order->save();
        }

        $logger->info("-- End Send Order to SAP --");
    }

    public function sendOrderToSap($data){
        $url = "http://201.238.200.3:8000/WS/services/documents/addOrders.xsjs?usuario=manager&clave=locales";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $payload = json_encode( $data );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        $result=curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);  
    }

    public function sendDraftOrderToSap($data){
        $url = "http://201.238.200.3:8000/WS/services/documents/addDrafts.xsjs?usuario=manager&clave=locales";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $payload = json_encode( $data );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        $result=curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    public function getCupoAprobado($rut, $total_venta){
        $url = "http://201.238.200.3:8000/WS/services/item/getSCN_B2B.xsjs?rut={$rut}&totalventa={$total_venta}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    public function validOrder($rut, $total_venta, &$logger){
        $data = $this->getCupoAprobado($rut, $total_venta);
        if ($data["ResponseStatus"] == "Success"){
            if(key_exists("Response", $data)){
                if(key_exists("CupoAprobado",$data["Response"][0])){
                    $cupo = $data["Response"][0];
                    if((int)$cupo["CupoAprobado"] > 0 AND ($cupo["ErrorMensaje1"] == "null" OR $cupo["ErrorMensaje1"] == "") AND ($cupo["ErrorMensaje2"] == "null" OR $cupo["ErrorMensaje2"] == "") ){
                        //tiene cupo para comprar
                        $logger->info("Cupo Aprobado - Posee cupo para realizar la compra");
                        return true;
                    }else{
                        $logger->info("Cupo NO Aprobado - No posee cupo o encontro un error");
                        $logger->info(" | Cupo: ".(int)$cupo["CupoAprobado"]);
                        $logger->info(" | ErrorMensaje1: ".$cupo["ErrorMensaje1"]);
                        $logger->info(" | ErrorMensaje2: ".$cupo["ErrorMensaje2"]);
                        return false;
                    }
                }
            }
        }else{
            $logger->info("ERROR: ".json_encode($data));
        }
    }

    public function validOrderSap($rut, $total_venta, &$logger){
        $url = "http://201.238.200.3:8000/WS/services/item/getSCN_B2B.xsjs?rut={$rut}&totalventa={$total_venta}";
        $logger->info($url);
        $data = $this->getCupoAprobado($rut, $total_venta);
        if ($data["ResponseStatus"] == "Success"){
            if(key_exists("Response", $data)){
                if(key_exists("CupoAprobado",$data["Response"][0])){
                    $cupo = $data["Response"][0];
                    if((int)$cupo["CupoAprobado"] > 0 AND ($cupo["ErrorMensaje1"] == "null" OR $cupo["ErrorMensaje1"] == "") AND ($cupo["ErrorMensaje2"] == "null" OR $cupo["ErrorMensaje2"] == "") ){
                        //tiene cupo para comprar
                        $logger->info("Cupo Aprobado - Posee cupo para realizar la compra");
                        return array(
                            "is_valid" => true,
                            "CupoAprobado" => $cupo["CupoAprobado"],
                            "ErrorMensaje1" => "",
                            "ErrorMensaje2" => "",
                        );
                    }else{
                        $logger->info("Cupo NO Aprobado - No posee cupo o encontro un error");
                        $logger->info(" | Cupo: ".(int)$cupo["CupoAprobado"]);
                        $logger->info(" | ErrorMensaje1: ".$cupo["ErrorMensaje1"]);
                        $logger->info(" | ErrorMensaje2: ".$cupo["ErrorMensaje2"]);
                        return array(
                            "is_valid" => false,
                            "CupoAprobado" => $cupo["CupoAprobado"],
                            "ErrorMensaje1" => $cupo["ErrorMensaje1"],
                            "ErrorMensaje2" => $cupo["ErrorMensaje2"],
                        );
                    }
                }
            }
        }else{
            $logger->info("ERROR: ".json_encode($data));
        }
    }

    public function isSuccess($data, &$logger){
        $response =  false;
        if(key_exists("ResponseStatus",$data)){
            $logger->info("isSuccess: ". $data["ResponseStatus"]);
            if ($data["ResponseStatus"] == "Success"){
                $response =  true;
            }
        }
        return $response;
    }

    public function updateOrder($OrderIncrementId){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($OrderIncrementId); 
        $order->setState("complete")->setStatus("complete");
        $order->addStatusHistoryComment("Order sent to sap and updated to completed");
        $order->save();
    }

    public function getProductSap($oem,$logger){
        $date = date("Ymd");
        $url = "http://201.238.200.3:8000/WS/services/item/getArticuloB2CT.xsjs?id={$oem}&fecha={$date}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        if (($result = curl_exec($ch)) === FALSE) {
            $logger->info('connectSAP',"cURL error".curl_error($ch),"error_");
            die();
        } else {
            $logger->info('connectSAP getArticuloB2C',"Done"); 
        }
        curl_close($ch);
        $result = str_replace("\n", '', str_replace("\r", '', $result) );

        $result = json_decode($result, true); 
        if($result["ResponseStatus"] === "Error"){
            $logger->info('connectSAP',"Error:".$result["Response"]["message"]["value"]); 
            return array("MaestroSAP"=>array(), "StockDisponiblePorBodega"=>array());
        }else{
            return $result;
        }
    }

    public function defineShipping($oem, $sku, $qty, $created_at, $order, &$logger){

        $product_data = $this->getProductSap($oem, $logger);
        
        $_same_day = false;
        $_in_24  = false;
        $_in_48  = false;
        $stock_cd = false;

        $_hora = $created_at->format('H:i:s');
        $logger->info("Hora de compra: ".$_hora);

        if($product_data && $product_data["ResponseStatus"] == 'Success'){
            foreach ($product_data["StockDisponiblePorBodega"] as $sap_stock){
                if($sap_stock["SKU"] == $sku){
                    if($sap_stock["CodigoBodega"] == "00CD"){ //stock en CD
                        if($sap_stock["Disponible"] >= $qty){
                            $stock_cd = true;
                        }else{
                            $stock_cd = false;
                        }
                    }
                }
            }
        }
        
        if(strtotime($_hora) < strtotime('13:00:00')) {
            if($stock_cd){
                //$_same_day = true;
                $type_shipping = 1;
                $logger->info("Same day");
                $logger->info("Hora de compra < 13:00 ". $_hora);
                $logger->info("Stock en CD: ". $stock_cd);
                $logger->info("Region: ". $order->getBillingAddress()->getRegion());
            }else{
                //$_in_48  = true;
                $type_shipping = 3;
                $logger->info("In 48");
                $logger->info("Hora de compra < 13:00 ". $_hora);
                $logger->info("Stock en CD: ". $stock_cd);
                $logger->info("Region: ". $order->getBillingAddress()->getRegion());
            }
        }else{
            if($stock_cd){
                //$_in_24  = true;
                $type_shipping = 2;
                $logger->info("In 24");
                $logger->info("Hora de compra > 13:00 ". $_hora);
                $logger->info("Stock en CD: ". $stock_cd);
                $logger->info("Region: ". $order->getBillingAddress()->getRegion());
            }else{
                //$_in_48  = true;
                $type_shipping = 3;
                $logger->info("In 48");
                $logger->info("Hora de compra < 13:00 ". $_hora);
                $logger->info("Stock en CD: ". $stock_cd);
                $logger->info("Region: ". $order->getBillingAddress()->getRegion());
            }
        }
        return $type_shipping;
    }
}