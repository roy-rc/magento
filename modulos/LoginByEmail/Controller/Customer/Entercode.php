<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Phrase;

class Entercode extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Customcode\LoginByEmail\Helper\LoginByEmail
     */
    protected $loginHelper;
    
    protected $customerFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Customcode\LoginByEmail\Helper\LoginByEmail $loginHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerFactory = $customerFactory;
        $this->session = $customerSession;
        $this->loginHelper = $loginHelper;
        parent::__construct($context);
    }


    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/');

        return new InvalidRequestException(
            $resultRedirect,
            [new Phrase('Invalid Form Key. Please refresh the page.')]
        );
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return null;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->loginHelper->isEnabled() || $this->session->isLoggedIn()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }

        if (!$this->getRequest()->getParam('id')) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/login');
            return $resultRedirect;
        }
        if ($this->getRequest()->getParam('accesscode')) {
            $customerId = $this->getRequest()->getParam('id');
            $loginHash = $this->getRequest()->getParam('accesscode');
            $validateLogin = $this->loginHelper->validateLogin($customerId, $loginHash);
            if ($validateLogin === true) {
                $this->messageManager->addSuccessMessage(
                    "Has iniciado sesión."
                );
                $customer = $this->customerFactory->create()->load($customerId);
                $this->session->setCustomerAsLoggedIn($customer);
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('customer/account');
                return $resultRedirect;
            } else {
                $this->messageManager->addErrorMessage($validateLogin['message']);
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath($validateLogin['path']);
                return $resultRedirect;
            }
        }
        return $this->resultPageFactory->create();
    }
}

