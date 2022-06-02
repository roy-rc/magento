<?php

namespace Customcode\Ctacorriente\Model\ResourceModel;

class OrderCtacorriente extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('order_ctacorriente', 'id');
    }

  
}
