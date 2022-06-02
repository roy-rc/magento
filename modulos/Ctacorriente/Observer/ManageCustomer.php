<?php

namespace Customcode\Ctacorriente\Observer;

use Magento\Framework\Event\ObserverInterface;

class ManageCustomer implements ObserverInterface
{
    const CUSTOMER_GROUP_ID = 4;

    protected $_customerRepositoryInterface;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $customer = $observer->getEvent()->getCustomer();
        $email = $customer->getEmail();
        
        $connection  = $this->resourceConnection->getConnection();
        $tableName   = $connection->getTableName('ctacorriente_customers');
        $query = "SELECT * FROM $tableName WHERE correo = '" . $email . "' LIMIT 1";
        $result = $connection->fetchAll($query);

        if (count($result) > 0) {
            $mcData = $result[0];
            $customer->setTaxvat($mcData['rut']);
            $customer->setFirstname($mcData['nombres']);
            $customer->setLastname($mcData['apellidos']);
            $customer->setPrefix($mcData['codigo']);
            $customer->setGroupId(self::CUSTOMER_GROUP_ID);
            $this->_customerRepositoryInterface->save($customer);
        }
    }
}