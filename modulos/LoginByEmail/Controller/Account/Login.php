<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Customcode\LoginByEmail\Controller\Account;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Controller\AbstractAccount;

/**
 * Login form page. Accepts POST for backward compatibility reasons.
 */
class Login extends AbstractAccount implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Customcode\LoginByEmail\Helper\LoginByEmail
     */
    protected $loginHelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Customcode\LoginByEmail\Helper\LoginByEmail $loginHelper
    ) {
        $this->session = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->loginHelper = $loginHelper;
        parent::__construct($context);
    }

    /**
     * Customer login form page
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->loginHelper->isEnabled()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customcodelogin/customer/login');
            return $resultRedirect;
        }

        if ($this->session->isLoggedIn()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setHeader('Login-Required', 'true');
        return $resultPage;
    }
}
