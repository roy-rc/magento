<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Model;

use Customcode\LoginByEmail\Api\CustomerLoginRepositoryInterface;
use Customcode\LoginByEmail\Api\Data\CustomerLoginInterfaceFactory;
use Customcode\LoginByEmail\Api\Data\CustomerLoginSearchResultsInterfaceFactory;
use Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin as ResourceCustomerLogin;
use Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin\CollectionFactory as CustomerLoginCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class CustomerLoginRepository implements CustomerLoginRepositoryInterface
{

    protected $searchResultsFactory;

    protected $dataCustomerLoginFactory;

    protected $customerLoginCollectionFactory;

    private $storeManager;

    protected $customerLoginFactory;

    protected $dataObjectProcessor;

    protected $extensionAttributesJoinProcessor;

    private $collectionProcessor;

    protected $dataObjectHelper;

    protected $extensibleDataObjectConverter;
    protected $resource;


    /**
     * @param ResourceCustomerLogin $resource
     * @param CustomerLoginFactory $customerLoginFactory
     * @param CustomerLoginInterfaceFactory $dataCustomerLoginFactory
     * @param CustomerLoginCollectionFactory $customerLoginCollectionFactory
     * @param CustomerLoginSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceCustomerLogin $resource,
        CustomerLoginFactory $customerLoginFactory,
        CustomerLoginInterfaceFactory $dataCustomerLoginFactory,
        CustomerLoginCollectionFactory $customerLoginCollectionFactory,
        CustomerLoginSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->customerLoginFactory = $customerLoginFactory;
        $this->customerLoginCollectionFactory = $customerLoginCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCustomerLoginFactory = $dataCustomerLoginFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface $customerLogin
    ) {
        /* if (empty($customerLogin->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $customerLogin->setStoreId($storeId);
        } */
        
        $customerLoginData = $this->extensibleDataObjectConverter->toNestedArray(
            $customerLogin,
            [],
            \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface::class
        );
        
        $customerLoginModel = $this->customerLoginFactory->create()->setData($customerLoginData);
        
        try {
            $this->resource->save($customerLoginModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the customerLogin: %1',
                $exception->getMessage()
            ));
        }
        return $customerLoginModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($customerLoginId)
    {
        $customerLogin = $this->customerLoginFactory->create();
        $this->resource->load($customerLogin, $customerLoginId);
        if (!$customerLogin->getId()) {
            throw new NoSuchEntityException(__('CustomerLogin with id "%1" does not exist.', $customerLoginId));
        }
        return $customerLogin->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->customerLoginCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface::class
        );
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface $customerLogin
    ) {
        try {
            $customerLoginModel = $this->customerLoginFactory->create();
            $this->resource->load($customerLoginModel, $customerLogin->getCustomerloginId());
            $this->resource->delete($customerLoginModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the CustomerLogin: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($customerLoginId)
    {
        return $this->delete($this->get($customerLoginId));
    }
}

