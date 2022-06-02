<?php
/**
 * Copyright © 2020 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\Docs\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Docs extends AbstractHelper
{

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }
}
