<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\Sugerencia\Block\Product;

class Sugerencia extends \Magento\Framework\View\Element\Template
{

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    protected $_registry;
    protected $customerSession;
    protected $_customerRepositoryInterface;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        array $data = []
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * @return string
     */
    public function getProduct()
    {
        //$productId = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Model\Session')->getData('last_viewed_product_id');
        //$product = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Model\Product')->load($productId);
        $product =  $this->_registry->registry('current_product');
        return $product;
    }

    public function getCustomer()
    {
        if($this->customerSession->getCustomer()->getId()){
            $customer =  $this->_customerRepositoryInterface->getById($this->customerSession->getCustomer()->getId());
            return $customer;
        }else{
            return false;
        }
    }
}

