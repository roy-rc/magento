<?php
/**
 * Created by PhpStorm.
 * User: sohelrana
 * Date: 9/16/17
 * Time: 4:47 PM
 */

namespace Customcode\Docs\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Customcode\Logger\Model\Logger;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

    protected $session;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        PageFactory $resultPageFactory
    )
    {
        $this->session = $customerSession;
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
    public function execute()
    {
        
        if (!$this->session->isLoggedIn())
        {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }
        else
        {
            $customer_id = $this->session->getCustomer()->getId();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerObj = $objectManager->create('Magento\Customer\Model\Customer')->load($customer_id);
            $data = $this->getCuentaCorriente($customerObj->getCodigoCliente());
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('Documents'));

            $customer_address = $objectManager->create('\Magento\Customer\Model\AddressFactory');
            $billingAddressId = $customerObj->getDefaultBilling();
            $billingAddress = $customer_address->create()->load($billingAddressId);

            $shippingAddressId = $customerObj->getDefaultShipping();
            $shippingAddress = $customer_address->create()->load($shippingAddressId);

            $block = $resultPage->getLayout()->getBlock('docs');
            $block->setData('custom_parameter', 'Data from the Controller');
            $block->setData('customer_cc', $data);
            $block->setData('customer_obj', $customerObj);
            $block->setData('customer_billing', $billingAddress);
            $block->setData('customer_shipping', $shippingAddress);

            return $resultPage;
        }
    }

    public function getCuentaCorriente($cardcode){
        $logger = new Logger("show_cuenta_corriente");
        $url = "http://201.238.200.3:8000/WS/services/item/getSCN_B2B.xsjs?cardcode={$cardcode}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        if (($result = curl_exec($ch)) === FALSE) {
            $logger->info('connectSAP',"cURL error".curl_error($ch),"error_");
            die();
        } else {
            $logger->info('connectSAP getSCN_B2B',"Done"); 
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
}