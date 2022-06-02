<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Api\Data;

interface CustomerLoginInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const CUSTOMER_ID = 'customer_id';
    const CUSTOMER_EMAIL = 'customer_email';
    const CUSTOMERLOGIN_ID = 'customerlogin_id';
    const CUSTOMER_LOGIN_DATETIME = 'customer_login_datetime';
    const CUSTOMER_LOGIN_TRIALS = 'customer_login_trials';
    const CUSTOMER_LOGIN_HASH = 'customer_login_hash';

    /**
     * Get customerlogin_id
     * @return string|null
     */
    public function getCustomerloginId();

    /**
     * Set customerlogin_id
     * @param string $customerloginId
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerloginId($customerloginId);

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerId($customerId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Customcode\LoginByEmail\Api\Data\CustomerLoginExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Customcode\LoginByEmail\Api\Data\CustomerLoginExtensionInterface $extensionAttributes
    );

    /**
     * Get customer_email
     * @return string|null
     */
    public function getCustomerEmail();

    /**
     * Set customer_email
     * @param string $customerEmail
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerEmail($customerEmail);

    /**
     * Get customer_login_hash
     * @return string|null
     */
    public function getCustomerLoginHash();

    /**
     * Set customer_login_hash
     * @param string $customerLoginHash
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerLoginHash($customerLoginHash);

    /**
     * Get customer_login_trials
     * @return string|null
     */
    public function getCustomerLoginTrials();

    /**
     * Set customer_login_trials
     * @param string $customerLoginTrials
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerLoginTrials($customerLoginTrials);

    /**
     * Get customer_login_datetime
     * @return string|null
     */
    public function getCustomerLoginDatetime();

    /**
     * Set customer_login_datetime
     * @param string $customerLoginDatetime
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerLoginDatetime($customerLoginDatetime);
}

