<?php
namespace Customcode\Ctacorriente\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class OrderPlacebefore implements ObserverInterface
{
    protected $messageManager;
    protected $helper;
    protected $_responseFactory;
    protected $_url;
    protected $jsonHelper;
    protected $resourceConnection;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Customcode\Ctacorriente\Helper\Data $helper,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->messageManager = $messageManager;
        $this->helper = $helper;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->jsonHelper = $jsonHelper;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $orderObserverData = $observer->getEvent()->getOrder()->getData();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        $order = $observer->getEvent()->getOrder();
        $customerObj = $objectManager->create('Magento\Customer\Model\Customer')->load($order->getCustomerId());

        $payment = $observer->getEvent()->getOrder()->getPayment();
        $method = $payment->getMethodInstance();
        $paymentMethod = $method->getCode();
        
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ctacorriente.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        
        $logger->info('PAYMENT: '. $paymentMethod);
        if ($paymentMethod == 'ctacorriente') {
            //validar total de OC
            if($this->validateMaxValueOrder($order->getGrandTotal(),$this->getUtmValue())){
                $address = $this->getCtacorrienteCustomerData($order->getCustomerEmail());
                $logger->info("Address CtacorrienteCustomer: ".json_encode($address));
                $direccion = $address["direccion"].' || '.$address["cit_name"].' || '.$address["dis_name"] .' || Chile';
                // Preparando parametros
                $bodyParams = array();
                $bodyParams["CodigoCompra"] = $order->getIncrementId(); 
                $bodyParams["RutComprador"] = $customerObj->getTaxvat();
                $bodyParams["CodigoUsuario"] = $customerObj->getPrefix(); // $customer->setPrefix($mcData['codigo']);
                $bodyParams["CodigoUsuarioTienda"] = $customerObj->getId(); //customer_id
                $bodyParams["FechaCompra"] = date("Y-m-d H:i:s");
                $bodyParams["MontoTotal"] = $order->getGrandTotal();
                $bodyParams["DireccionDespacho"] = $direccion;
                $bodyParams["FechaEntrega"] = date("Y-m-d H:i:s");
                foreach($order->getAllItems() as $item){
                    $product = array(
                        "TipoCodigoProducto" => "SKU", 
                        "CodigoProducto" => $item->getSku(),
                        "NombreProducto" => $item->getName(),
                        "CantidadProducto"=>$item->getQtyOrdered(),
                        "PrecioUnitario"=>$item->getPrice(),
                        "Informacion"=> $item->getTitulo(),
                    );
                    $bodyParams["productos"][] = $product;
                }
                //consumiendo servicio
                $response = $this->helper->postPurchaseOrder($bodyParams);
                if ($response["CodigoRespuesta"] == 1){
                    //actualiar direccion desde ctacorriente_customer
                    $this->updateShippingAddress($order,$address);
                    $this->updateBillingAddress($order,$address);
                    
                    $orderCtacorriente = $objectManager->create('Customcode\Ctacorriente\Model\OrderCtacorriente');
                    $orderCtacorriente->setIncrementId($order->getIncrementId());
                    $orderCtacorriente->setUrl($response["Url"]);
                    $orderCtacorriente->save();
                    $logger->info('Save Ctacorriente URL: '. $order->getIncrementId());
                    $this->messageManager->addSuccess(__("Orden de compra generada con éxito en Cuenta Corriente"));
                }else{
                    $logger->info('Error sending OC:'. $order->getIncrementId());
                    $this->messageManager->addError(__("Ocurrio un error al tratar de crear una orden de compra en Cuenta Corriente"));
                    $cartUrl = $this->_url->getUrl('checkout');
                    $this->_responseFactory->create()->setRedirect($cartUrl)->sendResponse();            
                    exit;
                }
            }else{
                $logger->info('Error sending OC - supera el valor maximo permitido:'. $order->getIncrementId().':'.$order->getGrandTotal());
                $this->messageManager->addError(__("Ocurrio un error, tu orden supera el maximo permitido por Cuenta Corriente"));
                $cartUrl = $this->_url->getUrl('checkout');
                $this->_responseFactory->create()->setRedirect($cartUrl)->sendResponse();            
                exit;
            }
            
            
        }else{
            return $this;
        }
    }

    public function getUtmValue(){
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ctacorriente.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $apiUrl = 'https://mindicador.cl/api';
        $valor_utm = 49723; // 1utm al 2020-02-12
        $error = false;
        //Es necesario tener habilitada la directiva allow_url_fopen para usar file_get_contents
        if ( ini_get('allow_url_fopen') ) {
            $json = file_get_contents($apiUrl);
            if($json === FALSE){
                $error = true;
                $logger->info('Error consultando indicadores '.$apiUrl.' (file_get_contents): usando valor default utm'. $valor_utm);
            }
        } else {
            //De otra forma utilizamos cURL
            $curl = curl_init($apiUrl);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $json = curl_exec($curl);
            curl_close($curl);
            if($errno = curl_errno($curl)) {
                $error = true;
                $logger->info('Error consultando indicadores '.$apiUrl.' (curl): usando valor default utm'. $valor_utm);
            }
        }
        if(!$error){
            $dailyIndicators = $this->jsonHelper->jsonDecode($json);
            $logger->info('indicadores: '. $json);
            $valor_utm = $dailyIndicators["utm"]["valor"];
        }
        return $valor_utm;
    }

    public function validateMaxValueOrder($total_oc,$utm_value){
        $valid = false;
        if($total_oc < (10 * $utm_value)){
            $valid = true;
        }
        return $valid;
    }

    public function updateShippingAddress($order,$address){
        $shippingAddress = $order->getShippingAddress();
        $shippingAddress->setTelephone($address["telefono"]);
        $shippingAddress->setStreet(array($address["direccion"]));
        $shippingAddress->setCity($address["cit_name"]);
        $shippingAddress->setRegion($address["dis_name"]);
        $shippingAddress->save();
    }

    public function updateBillingAddress($order,$address){
        $billingAddress = $order->getBillingAddress();
        $billingAddress->setTelephone($address["telefono"]);
        $billingAddress->setStreet(array($address["direccion"]));
        $billingAddress->setCity($address["cit_name"]);
        $billingAddress->setRegion($address["dis_name"]);
        $billingAddress->save();
    }

    public function getCtacorrienteCustomerData($email){
        $connection  = $this->resourceConnection->getConnection();
        $tableName   = $connection->getTableName('ctacorriente_customers');
        $query = "SELECT * FROM $tableName WHERE correo = '" . $email . "' LIMIT 1";
        $result = $connection->fetchAll($query);
        $mcData = array();
        if (count($result) > 0) {
            $mcData = $result[0];
        }
        return $mcData;  
    }

}
