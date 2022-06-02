<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\Tracking\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $request; 
    protected $session; 

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Request\Http $request
        )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->session = $customerSession;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $get = $this->request->getParams();
        if(key_exists("o_id",$get)){
            $increment_id = (int)$get['o_id'];
        }

        $customer_id = $this->session->getCustomer()->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerObj = $objectManager->create('Magento\Customer\Model\Customer')->load($customer_id);

        $orderObj = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($get['o_id']);
        
        $return = false;
        if($orderObj->getCustomerId() !=  $customer_id){
            $return = true;
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Tracking'));

        $block = $resultPage->getLayout()->getBlock('index.index');

        $data = $this->getEstado($increment_id);
        $block->setData('estado', $data["Response"]);
        $block->setData('order_id', $get['o_id']);
        $block->setData('order', $orderObj);
        $block->setData('return', $return);

        return $resultPage;
    }

    public function getEstado($increment_id){
        $url = "http://201.238.200.3:8000/WS/services/item/getestado.xsjs?pedido={$increment_id}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        if (($result = curl_exec($ch)) === FALSE) {
            die();
        }
        curl_close($ch);
        $result = str_replace("\n", '', str_replace("\r", '', $result) );

        $result = json_decode($result, true); 
        if($result["ResponseStatus"] === "Error"){
            return array("Response"=>array());
        }else{
            return $result;
        } 
    }
}

