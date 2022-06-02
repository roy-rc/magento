<?php


namespace Customcode\Ctacorriente\Model\Payment;

class Ctacorriente extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "ctacorriente";
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}
