<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\SendOrder\Cron;

use Customcode\Logger\Model\Logger;
class SendOrder
{

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

     /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    protected $productFactory;

     
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone)
    {
        $this->orderFactory = $orderFactory;
        $this->timezone = $timezone; 
        $this->transportBuilder = $transportBuilder;
        $this->productFactory = $productFactory;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $logger = new Logger("sendOrder");
        $logger->info(" -- Init SendOrder --");
        $orderModel = $this->orderFactory->create()->addFieldToFilter('main_table.status', ['in' => "pending"]);

        if (count($orderModel)) {
            $logger->info("Cant pending orders:".count($orderModel));
            foreach ($orderModel as $order) {
                $logger->info("Order: ".$order->getIncrementId());

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
                //$state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
                $websiteId = $storeManager->getStore($storeId)->getWebsiteId();

                $CustomerModel = $objectManager->create('Magento\Customer\Model\Customer');
                $CustomerModel->setWebsiteId(1);
                $CustomerModel->loadByEmail($detalleOrden['customer_email']); 
                
                $is_valid_order = $this->validOrder($CustomerModel->getRut(),round($detalleOrden['grand_total']), $logger);

                $order->addStatusHistoryComment("Validating CupoAprobado in SAP:".$is_valid_order);

                $nuevaOrden = array();
                $nuevaOrden['DocType'] = 'dDocument_Items';
                $nuevaOrden['DocDate'] = date("Ymd", strtotime($detalleOrden['created_at']));
                $nuevaOrden['DocDueDate'] = date("Ymd", strtotime($detalleOrden['created_at']));
                
                //para crear la orden como draft
                if(!$is_valid_order OR $methodTitle =="banktransfer"){
                    $nuevaOrden["DocObjectCode"] = "17";
                }

                $numero_oc = $order->getOcCliente();
                $fecha_oc = $order->getFechaOcCliente();

                $tipo_despacho = $order->getTipoDespacho();
                $direccion_despacho = $order->getDireccionOficina();

                $ejecutivo_code = 0;
                if($CustomerModel->getEjecutivoCode() != '' AND $CustomerModel->getEjecutivoCode() != "null"){
                    $ejecutivo_code = $CustomerModel->getEjecutivoCode();
                }elseif($CustomerModel->getEjecutivoTelefonicoCode() != '' AND $CustomerModel->getEjecutivoTelefonicoCode() != "null"){
                    $ejecutivo_code = $CustomerModel->getEjecutivoTelefonicoCode();
                }
                // 1 = despacho transporte, 2 despacho santiago, 3 retiro cliente, 4 Flex, 5 Vta verde
                $pos = strpos($detalleOrden['shipping_description'], "RETIRO");
                if ($pos !== false) {
                    $despacho_transporte = 3;
                }else{
                    if($order->getBillingAddress()->getRegion() == "Región Metropolitana de Santiago"){
                        $despacho_transporte = 2;
                    }else{
                        $despacho_transporte = 1;
                    }
                }
                
                if($despacho_transporte == 3){
                    $transporte_id = "";
                }else{
                    $transporte_id = $this->getTransporteId(str_replace("Transportista - ", "", $detalleOrden['shipping_description']));
                }
                
                
                //$nuevaOrden["CardCode"] = $CustomerModel->getRut()."C";
                $nuevaOrden["CardCode"] = $CustomerModel->getCodigoCliente();
                //$nuevaOrden["SalesPersonCode"] = $ejecutivo_code ;
                $nuevaOrden["SalesPersonCode"] = "168";
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
                $nuevaOrden["U_Id_Transporte"] = $transporte_id;
                $nuevaOrden["U_Transporte"] = $transporte_id;
                $nuevaOrden["U_URLEtiqueta"] = "";
                //$nuevaOrden["U_OficinaTransporte"] = str_replace("Transportista - ", "", $detalleOrden['shipping_description']) ;
                $nuevaOrden["U_OficinaTransporte"] = "";
                $nuevaOrden["U_CanalOrigen"] = 19;
                $nuevaOrden["U_tipo_mediopago"] = "";
                $nuevaOrden["U_numero_tarjeta"] = "";
                $nuevaOrden["U_valido_hasta"] = "";
                $nuevaOrden["U_NX_Sucursal"] = "00CD";

                $ship_to_code = "";
                $pay_to_code = "";
                $shipping = $order->getShippingAddress()->getData();
                $billing =  $order->getBillingAddress()->getData();
                foreach ($CustomerModel->getAddresses() as $address)
                {
                    if($shipping["customer_address_id"] == $address->getId()){
                        $ship_to_code = $address->getship_to_code();
                    }
                    if($billing["customer_address_id"] == $address->getId()){    
                        $pay_to_code = $address->getpay_to_code();
                    }
                }
                $nuevaOrden["ShipToCode"] = $ship_to_code;
                $nuevaOrden["PayToCode"] = $pay_to_code;
                
                $nuevaOrden["U_FolioRef"] = $numero_oc; //Numero de orden de compra
                $nuevaOrden["U_FchRef"] = $fecha_oc; //fecha de orden de compra

                $nuevaOrden["U_DomicilioOficina"] = $tipo_despacho; //tipo despacho
                $nuevaOrden["U_OficinaTransporte"] = $direccion_despacho; //direccion de oficina de despacho

                $nuevaOrden["U_TpoDocRef"] = "801";        

                $select_shipping = 0;
                $created_at = $this->timezone->date(new \DateTime($detalleOrden['created_at']));
                $today = $created_at->format('Y-m-d');

                $elementos = array();
                foreach ($order->getAllItems() as $item){
                    $logger->info("Product Info");
                    $logger->info(json_encode($item->getData()));
                    $logger->info($item->getPrice());
                    $logger->info($item->getOriginalPrice());

                    $product = $item->getProduct();
                    $sku = $product->getSku();
                    $logger->info(round($item->getPrice()) ." -- ".round($item->getOriginalPrice()));

                    $discount = ( round($item->getOriginalPrice()) - round($item->getPrice()) ) / round($item->getOriginalPrice());
                    $discount =  round($discount * 100); //porcentaje de descuento aplicado
                    $logger->info("discount:".$discount);
                    $logger->info("discount:".round($discount));

                    $elementos[] = array( 'ItemCode' => $sku, 'Quantity' => $item->getQtyOrdered(), 'WarehouseCode' => '00CD', 'TaxCode' => 'IVA', 'UnitPrice'=>round($item->getPrice()));
                    //$elementos[] = array( 'ItemCode' => $sku, 'Quantity' => $item->getQtyOrdered(), 'WarehouseCode' => '00CD', 'TaxCode' => 'IVA', 'PriceAfterVAT' => ($item->getPrice()*1.19),'PriceAfVAT' => ($item->getPrice()*1.19));
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
                $order->setFechaCompromisoDespacho($fecha_promesa);
                $logger->info($order->getCompromisoDespacho());
                $logger->info("Actualizando Orden con compromiso de despacho");
                
                $nuevaOrden["U_FechaPromesa"] = $fecha_promesa;
                $nuevaOrden["U_HoraPromesa"] = $created_at->format('H:i:s');
                
                $nuevaOrden["U_TipoDespacho"] = $despacho_transporte;

                if($elementos){ 
                    $nuevaOrden['DocumentLines'] = $elementos; 
                }
                $valorDespacho = (float) $order->getShippingAmount();
                $extraExpensive = array('JurisdictionCode' => 'IVA', 'JurisdictionType' => 1, 'TaxAmount' => 0, 'TaxRate' => 19 );
                $extraExpensive2 = array('ExpenseCode' => 2, 'TaxSum' => 0, 'TaxCode' => 'IVA', 'LineTotal' => number_format($valorDespacho / 1.19,0,'',''), 'DocExpenseTaxJurisdictions' => array($extraExpensive));
            
                $nuevaOrden['DocumentAdditionalExpenses'][] = $extraExpensive2;
            
                //$nuevaOrden['TaxExtension'] = array('CityS' => 'SANTIAGO', 'CountryB' => 'CL');
                //$nuevaOrden['AddressExtension'] = array('ShipToCity' => $order->getBillingAddress()->getRegion(), 'ShipToCountry' => 'CL');        
                
                //$is_valid_order = false;

                $logger->info("Total de orden:" . $detalleOrden['grand_total']);
                if($is_valid_order){
                    $logger->info(json_encode($nuevaOrden));
                    $logger->info("Send Order to SAP");
                    $order->addStatusHistoryComment("Send order to SAP as NV");
                    $responseWS = $this->sendOrderToSap($nuevaOrden);
                }
                if(!$is_valid_order OR $methodTitle =="banktransfer"){
                    $nuevaOrden["DocObjectCode"] = "17";
                    $logger->info(json_encode($nuevaOrden));
                    $logger->info("Send Draft to SAP");
                    $order->addStatusHistoryComment("Send order to SAP as DRAFT");  
                    $responseWS = $this->sendDraftOrderToSap($nuevaOrden);
                }

                //$responseWS = array("ResponseStatus"=>"Success", "ResponseType" => "Pruebas b2b local");
                //$responseWS = '{"ResponseStatus":"Success","ResponseType":"Pruebas b2b local","ResponseCount":1,"Response":{"code":0,"message":{"lang":"en-us","value":{"DocEntry":"000000"}}}}';
                //$responseWS = json_decode($responseWS, true);

                $logger->info(json_encode($responseWS));
                $order->addStatusHistoryComment(json_encode($responseWS));
                $order->save();
                
                $this->sendMailVendedor($order->getIncrementId(), $CustomerModel, $storeManager->getStore(), $logger);

                if($this->isSuccess($responseWS,$logger)){ //Ingreso exitoso a SAP
                    $logger->info("is_valid_order");
                    if($is_valid_order){
                        $logger->info("Order sent to SAP and updated to processing");
                        //actualizar ordern a Processing y crear Invoice
                        $order->setState("processing")->setStatus("processing");
                        $order->addStatusHistoryComment("Order sent to SAP and updated to processing");
                        $order->save();
                    } 
                    
                    if(!$is_valid_order OR $methodTitle =="banktransfer"){
                        $logger->info("Order sent to SAP and updated to holded");
                        $order->setState("holded")->setStatus("holded");
                        $order->addStatusHistoryComment("Order sent to SAP and updated to holded");
                        $order->save();
                    }
                } 

                $logger->info("-- End Send Order to SAP --");

            }
            $logger->info(" -- End CheckOrder --");
        }
        
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

    public function getCupoAprobado($rut, $total_venta, &$logger){
        $url = "http://201.238.200.3:8000/WS/services/item/getSCN_B2B.xsjs?rut={$rut}&totalventa={$total_venta}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    public function validOrder($rut, $total_venta, &$logger){
        $data = $this->getCupoAprobado($rut, $total_venta, $logger);
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

    public function getProductSap($oem, &$logger){
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

    public function sendMailVendedor($nro_orden, $customer, $store, &$logger){
        if (!$customer) {
            return false;
        }
        
        if($customer->getEjecutivoEmail() !="null" AND $customer->getEjecutivoEmail() !=""){
            $email_vendedor = $customer->getEjecutivoEmail();
        }elseif($customer->getEjecutivoTelefonicoEmail() !="null" AND $customer->getEjecutivoTelefonicoEmail() != ''){
            $email_vendedor = $customer->getEjecutivoTelefonicoEmail();
        }else{
            $email_vendedor = "servicioalcliente@nipon.cl";
        }
        $logger->info("email_vendedor: ".$email_vendedor);
        $receiverInfo = [
            'name' => $email_vendedor, //'rramos@nipon.cl', //$email_vendedor
            'email' => $email_vendedor //'rramos@nipon.cl' //$email_vendedor
        ];

        $templateParams = [
            'store' => $store, 
            'customer' => $customer, 
            'administrator_name' => $receiverInfo['name'],
            'order_id' => $nro_orden,
            
        ];

        $transport = $this->transportBuilder->setTemplateIdentifier(
            'customcode_transactional_email_send_order_email_template'
        )->setTemplateOptions(
            ['area' => 'frontend', 'store' => $store->getId()]
        )->addTo(
            $receiverInfo['email'], $receiverInfo['name']
        )->setTemplateVars(
            $templateParams
        )->setFrom(
            'general'
        )->getTransport();

        try {
            $transport->sendMessage();
            $logger->info("mail a vendedor enviado: ".$email_vendedor);
        } catch (\Exception $e) {
            $logger->info("Error enviando mail a vendedor: ".$email_vendedor." -- ".$e->getMessage());
        }
        return false;
    }

    public function getTipoDespacho(){
        $tipo_despacho = array(
            "1" =>	"Despacho Transporte",
            "2" =>	"Despacho Santiago",
            "3" =>	"Retira Cliente",
            "4" =>	"Flex",
            "5" =>	"Vta verde",
        );
    }

    public function getTransporteId($mg_transporte){
        $sap_transporte = array(
            "1" => "AIR EXPRESS",
            "2" => "AKAM",
            "3" => "ANDIMAR",
            "4" => "ATE",
            "50" => "B2B",
            "5" => "B & V",
            "6" => "BLUE EXPRESS AEREO",
            "7" => "BLUE EXPRESS TERRESTRE",
            "58" => "BULL EXPRESS",
            "8" => "BUS NORTE",
            "9" => "BUSES AHUMADA",
            "10" => "C Y L AEREO",
            "11" => "C Y L TERRESTRE",
            "12" => "CACEM EXPRESS",
            "13" => "CARGO BARRIOS",
            "14" => "CARGO NORTE",
            "15" => "CARMELITA",
            "16" => "CHEVALIER",
            "17" => "CHILEXPRESS",
            "64" => "CONO SUR",
            "62" => "ECOEX",
            "18" => "CRUZ DEL SUR",
            "19" => "DEPRISA",
            "20" => "DIBAMA",
            "66" => "ENVIAS CARGO",
            "63" => "",
            "21" => "ESTAFETA",
            "22" => "EVANS",
            "23" => "EXPRESSO NORTE",
            "43" => "FEDEX (TNT LITCARGO)",
            "53" => "FLORES VIVAR",
            "24" => "JAC",
            "25" => "LA GAVIOTA",
            "26" => "MEMPHIS",
            "60" => "MERCADO ENVIOS",
            "27" => "MERCOSUR AEREO",
            "28" => "MERCOSUR TERRESTRE",
            "29" => "NORAH",
            "30" => "PATAGONIA CARGO AEREO",
            "31" => "PATAGONIA CARGO TERRESTRE",
            "32" => "PDQ AEREO",
            "33" => "PDQ TERRESTRE",
            "34" => "PIERO",
            "35" => "PULLMAN CARGO",
            "36" => "PULLMAN DEL SUR",
            "37" => "RAPA NUI CARGO",
            "38" => "RAPID CARGO",
            "39" => "SAMEX",
            "40" => "STARKEN (TUR BUS)",
            "41" => "STOP CARGO",
            "42" => "TAIRENGA CARGO",
            "44" => "TRANS BUS",
            "65" => "TRANS CARGO TERRESTRE",
            "45" => "TRANSAMERICA (ALTAS CUMBRES)",
            "46" => "TRANSMAX",
            "61" => "TRANSPORTES ERIC LOPEZ",
            "52" => "TRANSPORTES JC",
            "47" => "TRANSPORTES SANTA MARIA",
            "57" => "TRANSPORTES FENIX",
            "48" => "TRINA TRAVEL",
            "54" => "URBANO EXPRESS",
            "49" => "VILLA PRAT",
            "51" => "VAYVE",
            "56" => "TVP CARGO",
            "59" => "WELIVERY",
            ""   => "Flota Nipon",
        );

        $id_transporte = array_search($mg_transporte,$sap_transporte,true);
        return $id_transporte;
    }
}


