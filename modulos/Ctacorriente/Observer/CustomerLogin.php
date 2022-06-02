<?php

namespace Customcode\Ctacorriente\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
    const CUSTOMER_GROUP_ID = 4;

    protected $_customerRepositoryInterface;
    protected $messageManager;
    protected $customerSession;
    private $responseFactory;
    private $url;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->resourceConnection = $resourceConnection;
        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	if(false){
        $customerId = $this->customerSession->getId();
        $event = $observer->getEvent();
        $customer = $observer->getEvent()->getCustomer();
        if($customer->getGroupId() != self::CUSTOMER_GROUP_ID) {
            $message = "Tu perfil no esta asociado a Ctacorriente, contacta al administrador del sitio para mas información";
            $this->messageManager->addError($message);
            $this->customerSession->logout();
        }

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer_login.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info('Customer name: '.$customer->getEmail());
        $logger->info('Customer group: '.$customer->getGroupId());
	$redirectionUrl = $this->url->getUrl('customer/account/login');
	$this->customerSession->setBeforeAuthUrl($redirectionUrl);
	return $this;
	}
    }
}
