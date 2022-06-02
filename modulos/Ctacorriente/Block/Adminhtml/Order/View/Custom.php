<?php
namespace Customcode\Ctacorriente\Block\Adminhtml\Order\View;
class Custom extends \Magento\Backend\Block\Template
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }


    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    public function getCtacorrienteUrl(){
        /* 
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $maxQuoteCollection = $objectManager->get('\Customcode\Ctacorriente\Model\OrderCtacorrienteFactory');
        $data = $maxQuoteCollection->create()->getCollection()->addFieldToFilter('increment_id',$this->getOrderIncrementId())->getFirstItem();
        return $data;  
        */
        return array();
    }

}
