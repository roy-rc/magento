<?php
namespace Customcode\Ctacorriente\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class OrderSaveafter implements ObserverInterface
{
    protected $messageManager;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //el observer que se ejecuta es checkout_onepage_controller_success_action. en este momento ya la orden esta creada y esta asociado al payment
	if($observer->getEvent()->getOrder()){    
	    $payment = $observer->getEvent()->getOrder()->getPayment();
            $method = $payment->getMethodInstance();
            $paymentMethod = $method->getCode();

            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ctacorriente.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            if ($paymentMethod == 'ctacorriente') {
                $logger->info("Update Status and Address.");
                $order_event = $observer->getEvent()->getOrder();
                $logger->info('before validate - Order State: '. $order_event->getIncrementId() .'||'. $observer->getEvent()->getOrder()->getStatus());
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('\Magento\Sales\Model\Order')->load($order_event->getId());
                if ($this->getValidCtacorriente($order_event->getIncrementId())){
                    $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $order->setState($orderState)->setStatus($orderState);
                    $order->save();
                }else{
                    $order->cancel()->save();
                }
                $logger->info('After validate - Order State : '. $order->getIncrementId() .'||'. $order->getStatus());
 	    }else{
                return $this;
            }	
    	}
    }

    public function getValidCtacorriente($increment_id){
        $is_valid = false;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderCtacorriente = $objectManager->get('\Custom\Ctacorriente\Model\OrderCtacorrienteFactory');
        $data = $orderCtacorriente->create()->getCollection()->addFieldToFilter('increment_id',$increment_id)->getFirstItem();
        if($data){
            $is_valid = true;
        }
        return $is_valid;
    }
}
