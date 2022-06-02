<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class LoginByEmail extends AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin\Collection
     */
    protected $customerLoginCollection;

    /**
     * @var \Customcode\LoginByEmail\Api\Data\CustomerLoginInterfaceFactory
     */
    protected $loginInterfaceFactory;

    /**
     * @var \Customcode\LoginByEmail\Api\CustomerLoginRepositoryInterface
     */
    protected $loginRepositoryInterface;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin\Collection $customerLoginCollection,
        \Customcode\LoginByEmail\Api\Data\CustomerLoginInterfaceFactory $loginInterfaceFactory,
        \Customcode\LoginByEmail\Api\CustomerLoginRepositoryInterface $loginRepositoryInterface
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->transportBuilder = $transportBuilder;
        $this->customerLoginCollection = $customerLoginCollection;
        $this->loginInterfaceFactory = $loginInterfaceFactory;
        $this->loginRepositoryInterface = $loginRepositoryInterface;
        parent::__construct($context);
    }

    /**
     * Get scope config
     *
     * @return ScopeConfigInterface
     * @deprecated 100.0.10
     */
    private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\App\Config\ScopeConfigInterface::class
            ); 
        } else {
            return $this->scopeConfig;
        }
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getScopeConfig()->getValue(
            'login_by_email/configuration/module_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getIntervalMinutes()
    {
        $defaultInterval = 10;
        $intervalMinutes = $this->getScopeConfig()->getValue(
            'login_by_email/configuration/minutes_between_request',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (is_numeric($intervalMinutes)) {
            return (int)$intervalMinutes;
        }
        return $defaultInterval;
    }

    /**
     * @return bool|int
     */
    public function getCustomerId($customerEmail) {
        try {
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            $customer = $this->customerRepository->get($customerEmail, $websiteId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
        return $customer->getId();
    }

    /**
     * @return bool|int
     */
    public function getCustomerName($customerEmail) {
        try {
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            $customer = $this->customerRepository->get($customerEmail, $websiteId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
        return $customer->getFirstname();
    }

    /**
     * @return bool
     */
    public function customerExists($customerEmail) {
        if ($this->getCustomerId($customerEmail)) {
            return true;
        }
        return false;
    }

    public function canSendEmail($customerEmail) {
        $intervalMinutes = $this->getIntervalMinutes();
        $dateTimeMinutesAgo = new \DateTime("{$intervalMinutes} minutes ago");
        $customerLoginCollection = $this->customerLoginCollection
            ->addFieldToFilter('customer_email', $customerEmail)
            ->addFieldToFilter('customer_login_datetime',[
                'gteq' => $dateTimeMinutesAgo->format('Y-m-d H:i:s')
            ]);
        return (count($customerLoginCollection) === 0);
    }

    private function saveLoginAttempt($customerId, $customerEmail, $loginHash) {
        // save customerId, email, loginHash, loginAttempts.
        $loginAttempt = $this->loginInterfaceFactory->create();
        $loginAttempt->setCustomerId($customerId);
        $loginAttempt->setCustomerEmail($customerEmail);
        $loginAttempt->setCustomerLoginHash($loginHash);
        $this->loginRepositoryInterface->save($loginAttempt);
    }

    private function sendEmail($customerId, $customerName, $customerEmail, $loginHash) {
        $senderEmail = $this->getScopeConfig()->getValue(
            'trans_email/ident_support/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $senderName  = $this->getScopeConfig()->getValue(
            'trans_email/ident_support/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $template = 'customer_login_email';
        $fromEmail = [
            'name' => $senderName,
            'email' => $senderEmail
        ];
        $loginUrlPath = "customcodelogin/customer/entercode/";
        $loginUrl = $this->storeManager->getStore()->getUrl($loginUrlPath) . "?accesscode={$loginHash}&id={$customerId}";
        $vars = [
            'login_code' => $loginHash,
            'login_url' => $loginUrl
        ];
        $storeId = $this->storeManager->getStore()->getId();

        $this->transportBuilder->setTemplateIdentifier($template)
            ->setTemplateOptions([
                'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId,
            ])
            ->setTemplateVars($vars)
            ->setFrom($fromEmail)
            ->addTo($customerEmail, $customerName);

        if (!isset($transport)) {
            $transport = $this->transportBuilder->getTransport();
        }

        try {
            $transport->sendMessage();
        } catch (\Exception $exception) {
            die($exception->getMessage());
        }

    }

    public function generateAccess($customerEmail) {
        $loginData = 'nipon-' . $customerEmail . time();
        $loginHash = hash('crc32', $loginData);
        $customerId = $this->getCustomerId($customerEmail);
        $customerName = $this->getCustomerName($customerEmail);

        $this->saveLoginAttempt($customerId, $customerEmail, $loginHash);
        $this->sendEmail($customerId, $customerName, $customerEmail, $loginHash);
        return true;
    }

    /**
     * @return bool
     */
    private function loginUser($customerId) {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $this->customerSession->setCustomerDataAsLoggedIn($customer);
            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
        return true;
    }

    /**
     * @return bool|array
     */
    public function validateLogin($customerId, $loginHash) {
        $intervalMinutes = $this->getIntervalMinutes() + 1; // added 1 minute of tolerance
        $dateTimeMinutesAgo = new \DateTime("{$intervalMinutes} minutes ago");
        $customerLoginCollection = $this->customerLoginCollection
            ->addFieldToFilter('customer_id', $customerId)
            //->addFieldToFilter('customer_login_hash', $loginHash)
            ->addFieldToFilter('customer_login_datetime',[
                'gteq' => $dateTimeMinutesAgo->format('Y-m-d H:i:s')
            ]);
        if (count($customerLoginCollection) > 0) {
            $loginData = $customerLoginCollection->getFirstItem();
            $customerLoginTrials = $loginData->getCustomerLoginTrials();
            if ($customerLoginTrials >= 3) {
                return [
                    'message' => 'Has alcanzado el máximo de intentos disponibles.'
                                . ' Solicita un nuevo código de acceso.',
                    'path' => '*/*/login'
                ];
            }
            if ($loginData->getCustomerLoginHash() == $loginHash) {
                return true;
            } else {
                $loginData->setCustomerLoginTrials($customerLoginTrials + 1);
                $loginData->save($loginData);
                return [
                    'message' => 'El código de acceso ingresado no es válido. '
                                . 'Debes ingresar el código te enviamos por email.',
                    'path' => '*/*/entercode/id/'.$customerId
                ];
            }
            return $this->loginUser($customerId);
        } else {
            return [
                'message' => 'Tiempo de espera agotado. Solicita un nuevo código de acceso.',
                'path' => '*/*/login'
            ];
        }
    }
}
