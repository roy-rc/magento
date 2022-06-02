<?php
namespace Customcode\Invoice\Observer;
use Magento\Framework\Event\ObserverInterface;
use Customcode\Logger\Model\Logger;

class Createinvoice implements \Magento\Framework\Event\ObserverInterface
{

    protected $_invoiceService;
    protected $_transactionFactory;
    protected $_invoiceSender;

    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
    ) {
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceSender = $invoiceSender;
    }

    public function execute(\Magento\Framework\Event\Observer $observer){
        $logger = new Logger("sendOrder");
	    $order = $observer->getEvent()->getOrder();

        if($order->getStatus() == "processing" && $order->canInvoice()){
            $logger->info("Order status:".$order->getIncrementId()." - ".$order->getStatus());
            $logger->info(" - CreateInvoice - ");
            try {
                $shippingAddress = $order->getShippingAddress();
                $shippingAddress->setPostcode("00");
                $shippingAddress->save();

                $billingAddress = $order->getBillingAddress();
                $billingAddress->setPostcode("00");
                $billingAddress->save();

                if(!$order->canInvoice()) {
                    return null;
                }
                if(!$order->getState() == 'new') {
                    return null;
                }

                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();

                $transaction = $this->_transactionFactory->create()
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transaction->save();
                $logger->info("Invoice created on order:".$order->getIncrementId());
                
                $this->_invoiceSender->send($invoice);
                //send notification code
                $order->addStatusHistoryComment(
                        __('Notified customer about invoice #%1.', $invoice->getId())
                    )->setIsCustomerNotified(true)->save();
                $logger->info("Invoice send notification:". $invoice->getId());

            } catch (\Exception $e) {
                $order->addStatusHistoryComment('Exception message: '.$e->getMessage(), false);
                $order->save();
                $logger->info("Invoice Error on created:".$order->getIncrementId()." - ".$e->getMessage());
                return null;
            }
        }
    }
}
