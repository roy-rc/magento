<?php
namespace Customcode\WebsiteRestriction\Observer;

use Magento\Customer\Model\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session;

use Customcode\Logger\Model\Logger;

class Restrictwebsite implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var Session
     */
    protected $session;

    /**
     * RestrictWebsite constructor.
     */    
    public function __construct(
      \Magento\Framework\Event\ManagerInterface $eventManager,
      \Magento\Framework\App\Response\Http $response,
      \Magento\Framework\UrlFactory $urlFactory,
      \Magento\Framework\App\Http\Context $context,
      \Magento\Framework\App\ActionFlag $actionFlag,
      Session $customerSession
  )
  {
      $this->session = $customerSession;
      $this->_response = $response;
      $this->_urlFactory = $urlFactory;
      $this->_context = $context;
      $this->_actionFlag = $actionFlag;
  }

  /**
   * @param Observer $observer
   * @return void
   */
  public function execute(Observer $observer)
  {
      $allowedRoutes = [
          'customer_account_login',
          'customer_account_loginpost',
          'customer_account_createpost',
          'customer_account_logoutsuccess',
          'customer_account_confirm',
          'customer_account_confirmation',
          'customer_account_forgotpassword',
          'customer_account_forgotpasswordpost',
          'customer_account_createpassword',
          'customer_account_resetpasswordpost',
          'customer_section_load',
          'customer_account_index',
          '_index_index',
          'blog_index_index',
          'cms_page_view',
          'contact_index_index',
          'elasticsuite_tracker_hit',
          '__',
      ];
      //$logger = new Logger("restrictwebsite");
      $request = $observer->getEvent()->getRequest();
      $isCustomerLoggedIn = $this->_context->getValue(Context::CONTEXT_AUTH);
      $actionFullName = strtolower($request->getFullActionName());
      
      //$logger->info("action: "$actionFullName);
      //$logger->info("isLogin: " . $this->session->isLoggedIn());

      if (!$this->session->isLoggedIn() AND !in_array($actionFullName, $allowedRoutes)) {
          //logger->info("redirect to login...");

          $this->_response->setRedirect($this->_urlFactory->create()->getUrl('customer/account/login'));
      }
      //else{
      //    $logger->info("redirect to page: ". $actionFullName);
      //}

  }
}