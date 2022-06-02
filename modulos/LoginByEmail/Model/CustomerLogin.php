<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Model;

use Customcode\LoginByEmail\Api\Data\CustomerLoginInterface;
use Customcode\LoginByEmail\Api\Data\CustomerLoginInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class CustomerLogin extends \Magento\Framework\Model\AbstractModel
{

    protected $customerloginDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'customcode_loginbyemail_customerlogin';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CustomerLoginInterfaceFactory $customerloginDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin $resource
     * @param \Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        CustomerLoginInterfaceFactory $customerloginDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin $resource,
        \Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin\Collection $resourceCollection,
        array $data = []
    ) {
        $this->customerloginDataFactory = $customerloginDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve customerlogin model with customerlogin data
     * @return CustomerLoginInterface
     */
    public function getDataModel()
    {
        $customerloginData = $this->getData();
        
        $customerloginDataObject = $this->customerloginDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $customerloginDataObject,
            $customerloginData,
            CustomerLoginInterface::class
        );
        
        return $customerloginDataObject;
    }
}

