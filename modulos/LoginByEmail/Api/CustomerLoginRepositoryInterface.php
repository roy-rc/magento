<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CustomerLoginRepositoryInterface
{

    /**
     * Save CustomerLogin
     * @param \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface $customerLogin
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface $customerLogin
    );

    /**
     * Retrieve CustomerLogin
     * @param string $customerloginId
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($customerloginId);

    /**
     * Retrieve CustomerLogin matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete CustomerLogin
     * @param \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface $customerLogin
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface $customerLogin
    );

    /**
     * Delete CustomerLogin by ID
     * @param string $customerloginId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($customerloginId);
}

