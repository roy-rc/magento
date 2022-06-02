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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;

class Login extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var \Customcode\LoginByEmail\Helper\LoginByEmail
     */
    private $loginHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Customcode\LoginByEmail\Helper\LoginByEmail $loginHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
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

        if ($this->getRequest()->isPost() && $this->formKeyValidator->validate($this->getRequest())) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username'])) {
                if (!$this->loginHelper->customerExists($login['username'])) {
                    $this->messageManager->addErrorMessage('El email ingresado no está registrado.');
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/login');
                    return $resultRedirect;
                }
                if (!$this->loginHelper->canSendEmail($login['username'])) {
                    $intervalMinutes = $this->loginHelper->getIntervalMinutes();
                    $this->messageManager->addErrorMessage(
                        "Debe esperar {$intervalMinutes} minutos para volver a solicitar acceso."
                        . " Si ya solicitó acceso en los últimos minutos, revise su email."
                    );
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/login');
                    return $resultRedirect;
                }
                if (!$this->loginHelper->generateAccess($login['username'])) {
                    $this->messageManager->addErrorMessage(
                        "Ocurrió un error al enviar tu código de acceso. Intenta de nuevo más tarde."
                        . " Si el problema persiste, comunícate con tu ejecutivo."
                    );
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/login');
                    return $resultRedirect;
                } else {
                    $this->messageManager->addSuccessMessage(
                        "Hemos enviado el código de acceso a tu email."
                    );
                    $customerId = $this->loginHelper->getCustomerId($login['username']);
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/entercode/id/'.$customerId);
                    return $resultRedirect;
                }

            }
        } else {
            return $this->resultPageFactory->create();
        }

    }
}
