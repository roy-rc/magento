<?php

namespace Customcode\Ctacorriente\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;

class Data extends AbstractHelper
{
    /**
     *
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        Context $context,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;
        $this->context = $context;
        $this->jsonHelper = $jsonHelper;

        parent::__construct($context);
    }

    /**
     * Get Ctacorriente data from REST service
     *      
     * @param string $token
     * @param string $userId
     * @param string $agreementId
     * @return Zend_Http_Response
     */
    public function getCtacorrienteCustomers()
    {
        $response = null;

        try {
            $endpoint = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/endpoint_ctacorriente_data/listadoUsuarios',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            // Ticket
            $storeIdTicket = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/store_ctacorriente_data/idTienda',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            if (!empty($endpoint) && !empty($storeIdTicket)) {
                $httpHeaders = [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ];

                $endpoint .= '?ticket=' . $storeIdTicket;
                $client = $this->httpClientFactory->create();
                $client->setUri($endpoint);
                $client->setConfig(
                    ['maxredirects' => 0,
                    'timeout' => 60]
                );
                $client->setHeaders($httpHeaders);            
                $response = $client->request(\Zend_Http_Client::GET)->getBody();
                //$response = '{"Cantidad":10218,"FechaCreacion":"2019-10-01T15:49:36.730","Version":"V1","Mensaje":"Servicio para Usuarios Con Direcciones de facturación y Correos Validos.","UsuariosCtacorriente":[{"Codigo":"1355265_557974","Nombres":"CAROLINA","Apellidos":"VENEGAS","Rut":"13.544.592-4","Correo":"CVENEGAS@CMQ.CL","Telefono":"56-32-3140443","Celular":"","Organismo":" CORPORACIÓN MUNICIPAL DE EDUCACION, SALUD Y ATENCION AL MENOR DE QUILPUE","Unidad":"AREA DE SALUD","RutUnidad":"70.878.900-3","Direccion":" BAQUEDANO 960","citName":"Quilpué","disName":"Región de Valparaíso"},{"Codigo":"1067917_558257","Nombres":"ALEX","Apellidos":"JERIA CASTILLO","Rut":"9.628.457-8","Correo":"adquisiciones@cmq.cl","Telefono":"56-32-3140400","Celular":"56-9-","Organismo":" CORPORACIÓN MUNICIPAL DE EDUCACION, SALUD Y ATENCION AL MENOR DE QUILPUE","Unidad":"CENTRAL","RutUnidad":"70.878.900-3","Direccion":"BAQUEDANO 960","citName":"Quilpué","disName":"Región de Valparaíso"},{"Codigo":"1072869_558257","Nombres":"MARIA ANGELICA","Apellidos":"LUCO ASANCHEZ","Rut":"7.730.284-0","Correo":"MLUCO@CMQ.CL","Telefono":"032-3140420","Celular":"056-9-88390328","Organismo":" CORPORACIÓN MUNICIPAL DE EDUCACION, SALUD Y ATENCION AL MENOR DE QUILPUE","Unidad":"CENTRAL","RutUnidad":"70.878.900-3","Direccion":"BAQUEDANO 960","citName":"Quilpué","disName":"Región de Valparaíso"},{"Codigo":"1360737_623663","Nombres":"David","Apellidos":"Perez Aravena","Rut":"16.280.302-6","Correo":"dperez@acee.cl","Telefono":"56-02-25712200-213","Celular":"56-9-87635589","Organismo":"Agencia Chilena de Eficiencia Energética","Unidad":"Depto. Administración y Finanzas","RutUnidad":"65.030.848-4","Direccion":"Nuncio Monseñor Sótero Sanz 221","citName":"Providencia","disName":"Región Metropolitana de Santiago"}]}';

                //$this->logger->info('Store ID: ' . $storeIdTicket . ' Service Response: ' . $response);
                $response = json_decode($response, true);
            } else {
                throw new LocalizedException(__('Define endpoint and store ticket parameters'));
            }
        } catch(LocalizedException $e) {
            $this->logger->warning('Store ID: ' . $storeIdTicket . ' Error message: ' . $e->getMessage());
        } catch(\Exception $e) {
            $this->logger->critical('Store ID: ' . $storeIdTicket . ' Error message: ' . $e->getMessage());
        }
        
        $this->logger->info('RESPONSE: ' . print_r(array_keys($response),true));
        return $response;
    }

    /**
     * Send new OC to Cuenta Corriente
     *      
     * @param string $token
     * @param string $userId
     * @param string $agreementId
     * @return Zend_Http_Response
     */
    public function postPurchaseOrder($bodyParams)
    {
        $writer_exception = new \Zend\Log\Writer\Stream(BP . '/var/log/ctacorriente_exception.log');
        $logger_exception = new \Zend\Log\Logger();
        $logger_exception->addWriter($writer_exception);

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ctacorriente.log');
        $_logger = new \Zend\Log\Logger();
        $_logger->addWriter($writer);
        $response = null;
        try {
            $endpoint = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/endpoint_ctacorriente_data/registroCompra',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            // Ticket
            $storeIdTicket = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/store_ctacorriente_data/idTienda',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            $rut = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/store_ctacorriente_data/rut',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            $razonSocial = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/store_ctacorriente_data/razonSocial',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            $direccion = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/store_ctacorriente_data/direccion',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            $comuna = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/store_ctacorriente_data/comuna',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            $region = rtrim($this->scopeConfig->getValue(
                'ctacorriente_parameters/store_ctacorriente_data/region',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ), '/');

            if (!empty($endpoint) && !empty($storeIdTicket) && !empty($rut) && !empty($razonSocial) && !empty($direccion) && !empty($bodyParams)) {
                $httpHeaders = [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ];

                $bodyParams['idTienda'] = $storeIdTicket;
                $bodyParams['RutProveedor'] = $rut;
                $bodyParams['DireccionProveedor'] = "$direccion || $comuna || $region || Chile";
                $bodyParams['RazonSocialProveedor'] = $razonSocial;
                $bodyParams['MonedaCompra'] = "CLP";

                $data = $this->jsonHelper->jsonEncode($bodyParams);
                $_logger->info('postPurchaseOrder REQUEST: '. $data);
                $client = $this->httpClientFactory->create();
                $client->setUri($endpoint);
                $client->setConfig(
                    ['maxredirects' => 0,
                    'timeout' => 30]
                );
                $client->setHeaders($httpHeaders);
                $client->setRawData($data);
                $response = $client->request(\Zend_Http_Client::POST)->getBody();
                
                $_logger->info('(postPurchaseOrder) Store ID: ' . $storeIdTicket . ' Service Response: ' . $response);
                $response = $this->jsonHelper->jsonDecode($response);
                if($response["CodigoRespuesta"] != 1){
                    $logger_exception->info('Order:' .$bodyParams['CodigoCompra'].' || Response - CodigoRespuesta:'. $response["CodigoRespuesta"].' || Response - Respuesta:'.$response["Respuesta"]);
                }
            } else {
                $logger_exception->info('Order: '.$bodyParams['CodigoCompra'].'(postPurchaseOrder) Define endpoint and store ticket parameters');
                throw new LocalizedException(__('(postPurchaseOrder) Define endpoint and store ticket parameters'));
            }
        } catch(LocalizedException $e) {
            $logger_exception->info('Order: '.$bodyParams['CodigoCompra'].'(postPurchaseOrder) Store ID: ' . $storeIdTicket . ' Error message: ' . $e->getMessage());
        } catch(\Exception $e) {
            $logger_exception->info('Order: '.$bodyParams['CodigoCompra'].'(postPurchaseOrder) Seller ID: ' . $userId . ' Agreement ID: ' . $agreementId . ' Error message: ' . $e->getMessage());
        }

        return $response;
    }
}
