<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\LoginByEmail\Model\Data;

use Customcode\LoginByEmail\Api\Data\CustomerLoginInterface;

class CustomerLogin extends \Magento\Framework\Api\AbstractExtensibleObject implements CustomerLoginInterface
{

    /**
     * Get customerlogin_id
     * @return string|null
     */
    public function getCustomerloginId()
    {
        return $this->_get(self::CUSTOMERLOGIN_ID);
    }

    /**
     * Set customerlogin_id
     * @param string $customerloginId
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerloginId($customerloginId)
    {
        return $this->setData(self::CUSTOMERLOGIN_ID, $customerloginId);
    }

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Customcode\LoginByEmail\Api\Data\CustomerLoginExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Customcode\LoginByEmail\Api\Data\CustomerLoginExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get customer_email
     * @return string|null
     */
    public function getCustomerEmail()
    {
        return $this->_get(self::CUSTOMER_EMAIL);
    }

    /**
     * Set customer_email
     * @param string $customerEmail
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerEmail($customerEmail)
    {
        return $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * Get customer_login_hash
     * @return string|null
     */
    public function getCustomerLoginHash()
    {
        return $this->_get(self::CUSTOMER_LOGIN_HASH);
    }

    /**
     * Set customer_login_hash
     * @param string $customerLoginHash
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerLoginHash($customerLoginHash)
    {
        return $this->setData(self::CUSTOMER_LOGIN_HASH, $customerLoginHash);
    }

    /**
     * Get customer_login_trials
     * @return string|null
     */
    public function getCustomerLoginTrials()
    {
        return $this->_get(self::CUSTOMER_LOGIN_TRIALS);
    }

    /**
     * Set customer_login_trials
     * @param string $customerLoginTrials
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerLoginTrials($customerLoginTrials)
    {
        return $this->setData(self::CUSTOMER_LOGIN_TRIALS, $customerLoginTrials);
    }

    /**
     * Get customer_login_datetime
     * @return string|null
     */
    public function getCustomerLoginDatetime()
    {
        return $this->_get(self::CUSTOMER_LOGIN_DATETIME);
    }

    /**
     * Set customer_login_datetime
     * @param string $customerLoginDatetime
     * @return \Customcode\LoginByEmail\Api\Data\CustomerLoginInterface
     */
    public function setCustomerLoginDatetime($customerLoginDatetime)
    {
        return $this->setData(self::CUSTOMER_LOGIN_DATETIME, $customerLoginDatetime);
    }
}

