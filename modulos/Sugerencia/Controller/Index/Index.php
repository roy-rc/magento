<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\Sugerencia\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Index implements HttpPostActionInterface
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
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder

    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $json;
        $this->logger = $logger;
        $this->http = $http;
        $this->request = $request; 
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $post = $this->request->getPost();
        try {
            //return $this->jsonResponse('your response');
            return $this->jsonResponse(json_encode($this->sendMail($post)));
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

    public function sendMail($post){
        try{
            if($post['campo_falso'] == ''){
                $this->_inlineTranslation->suspend();

                $sender = [ 'name' => 'Nipon - Sugerencias', 'email' => $post['correo'] ];
            
                if($post["ejecutivo_email"]){
                    $sentToEmail = $post["ejecutivo_email"];      
                }else{
                    $sentToEmail = 'rramos@nipon.cl';     
                    //$sentToEmail = 'servicioalcliente@nipon.cl'; 
                }
                   
                $sentToName = 'Nipon - Sugerencias';
                
                $email_data = array(
                    'tipo' => 1, 
                    'email'  => $post['correo'], 
                    'url' => $post['url'],
                    'sugerencia'  => $post['sugerencia'],
                    'sku' =>  $post['sku'],
                );
                $transport = $this->_transportBuilder
                    ->setTemplateIdentifier('sugerencia_email_template')
                    ->setTemplateOptions(['area' => 'frontend','store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,])
                    ->setTemplateVars($email_data)->setFrom($sender)->addTo($sentToEmail,$sentToName)->getTransport();
                    
                $transport->sendMessage();                 
                $this->_inlineTranslation->resume();                
                /* $this->_coreRegistry->register('foo', 'Email enviado exitosamente'); */
            }           
            return true;
        } catch(\Exception $e){
            return false;
            /* $this->messageManager->addError($e->getMessage());
            $this->_logLoggerInterface->debug($e->getMessage());
            $this->_coreRegistry->register('foo', 'Ha ocurrido un error al enviar tu contacto. inténtalo más tarde.'); */
        }
    }
}