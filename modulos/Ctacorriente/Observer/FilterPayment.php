<?php
namespace Customcode\Ctacorriente\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class FilterPayment implements ObserverInterface
{
    protected $session;
    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->session = $customerSession;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $result          = $observer->getEvent()->getResult();
        $method_instance = $observer->getEvent()->getMethodInstance();
        $quote           = $observer->getEvent()->getQuote();

        $customer_id = $this->session->getCustomer()->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerObj = $objectManager->create('Magento\Customer\Model\Customer')->load($customer_id);

        if($customerObj->getCondicionPago() == "Contado"){
            if ($method_instance->getCode() == 'banktransfer') {
                $result->setData('is_available', true);
            }
            if ($method_instance->getCode() == 'ctacorriente') {
                $result->setData('is_available', false);
            }
        }else{
            if ($method_instance->getCode() == 'banktransfer') {
                $result->setData('is_available', false);
            }
            if ($method_instance->getCode() == 'ctacorriente') {
                $result->setData('is_available', true);
            }
        }

        /* If Cusomer group is match then work */
        /* 
        if (null !== $quote && $quote->getCustomerGroupId() != 4) {
            if ($method_instance->getCode() == 'ctacorriente') {
                $result->setData('is_available', false);
            }
        }
        */
    }
}