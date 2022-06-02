<?php

namespace Customcode\Ctacorriente\Model\ResourceModel\OrderCtacorriente;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Customcode\Ctacorriente\Model\OrderCtacorriente', 'Customcode\Ctacorriente\Model\ResourceModel\OrderCtacorriente');
    }
}
