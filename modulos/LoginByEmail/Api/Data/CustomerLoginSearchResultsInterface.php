<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Api\Data;

interface CustomerLoginSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get CustomerLogin list.
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface[]
     */
    public function getItems();

    /**
     * Set customer_id list.
     * @param \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

