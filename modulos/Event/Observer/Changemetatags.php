<?php

namespace Customcode\Event\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

use Customcode\Logger\Model\Logger;

class Changemetatags implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $_actionFlag;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;


    private $_pageConfig;

    /**
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     */
    public function __construct(
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\View\Page\Config $pageConfig     
    ) {
        $this->_actionFlag = $actionFlag;
        $this->messageManager = $messageManager;
        $this->redirect = $redirect;
        $this->_pageConfig = $pageConfig; 
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\Action\Action $controller */
        $full_action_name = $observer->getFullActionName();

        $layout = $observer->getEvent()->getLayout();

       /*  $logger = new Logger("changeMetatags");
        $logger->info("full_action_name :".$full_action_name); */
        
        if($full_action_name == 'catalog_category_view'){
            $uri = $_SERVER['REQUEST_URI'];

            if(stristr($uri,"?"))
            {
                //print_r($layout->getBlock('head')->getRobots());
                $this->_pageConfig->setRobots('NOINDEX,FOLLOW');
            }
        }

    }
}