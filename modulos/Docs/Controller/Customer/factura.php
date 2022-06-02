<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\Docs\Controller\Customer;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use SoapHeader;

class Factura implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Json
     */
    protected $serializer;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Http
     */
    protected $http;

    protected $request; 

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Http $http
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Json $json,
        LoggerInterface $logger,
        Http $http,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $json;
        $this->logger = $logger;
        $this->http = $http;
        $this->request = $request;
    }

    public function other_execute()
    {
        try {
            $data = "";
            $get = $this->request->getParams();
            if(key_exists("folio",$get)){
                
                if($get['folio']){
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => 'http://alerce.docele.cl:80/DoceleOL_Auth/DoceleOLService',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:doc="http://www.facele.cl/DoceleOL/">
                    <soapenv:Header/>
                    <soapenv:Body>
                        <doc:obtieneDTE>
                            <rutEmisor>85423000-K</rutEmisor>
                            <tipoDTE>33</tipoDTE>
                            <folioDTE>'.$get['folio'].'</folioDTE>
                            <formato>URL_PDF</formato>
                        </doc:obtieneDTE>
                    </soapenv:Body>
                    </soapenv:Envelope>',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: text/xml; charset=utf-8',
                        'SOAPAction: http://www.facele.cl/DoceleOL/obtieneDTE',
                        'facele.user: 0cc713a13',
                        'facele.pass: mWUX8WQOfuexV9CLWaNFSA=='
                    ),
                    ));

                    $data = curl_exec($curl);

                    curl_close($curl);

                    $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $data);
                    $xml = new \SimpleXMLElement($response);
                    $array = json_decode(json_encode((array)$xml), TRUE);
                    $is_data = false;
                    if(key_exists("SBody",$array)){
                        if(key_exists("ns2obtieneDTEResponse", $array["SBody"])){
                            if(key_exists("URL", $array["SBody"]["ns2obtieneDTEResponse"])){
                                $is_data = true;
                            }
                        }
                    }
                    if($is_data){
                        $data = $array["SBody"]["ns2obtieneDTEResponse"]["URL"];
                    }else{
                        $data = false;
                    }
                }
            }
            return $this->jsonResponse($data);
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $data = "";
            $get = $this->request->getParams();
            if(key_exists("folio",$get)){
                
                if($get['folio']){
                    $client = new \SoapClient("http://201.238.200.3:8080/DoceleOL/DoceleOLService?wsdl");
                    //$client = new \SoapClient("http://alerce.docele.cl/DoceleOL_Auth/DoceleOLService?wsdl");
                    
                    $params = array(
                        "rutEmisor" => "85423000-K",
                        "tipoDTE" => "33",
                        "folioDTE" => $get['folio'],
                        "formato" => "URL_PDF"
                    );
                    $headers = array();

                    $headers[] = new SoapHeader('http://www.facele.cl/DoceleOL/obtieneDTE','facele.user: 0cc713a13' );
                    $headers[] = new SoapHeader('http://www.facele.cl/DoceleOL/obtieneDTE','facele.pass: mWUX8WQOfuexV9CLWaNFSA==' );

                    //$client->__setSoapHeaders($headers);

                    $response = $client->__soapCall("obtieneDTE", array($params));
                    $data = $response->URL;
                }
            }
            return $this->jsonResponse($data);
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return ResultInterface
     */
    public function jsonResponse($response = '')
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'application/json');
        return $this->http->setBody(
            $this->serializer->serialize($response)
        );
    }
}