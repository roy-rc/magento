<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'customerlogin_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Customcode\LoginByEmail\Model\CustomerLogin::class,
            \Customcode\LoginByEmail\Model\ResourceModel\CustomerLogin::class
        );
    }
}

