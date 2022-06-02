<?php
namespace Customcode\Ctacorriente\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Customer\Model\GroupFactory;

class UpgradeData implements UpgradeDataInterface
{
	protected $groupFactory;

    /**
     * Constructor
     *
     * @param Magento\Customer\Model\GroupFactory $groupFactory
     */
    public function __construct(

        GroupFactory $groupFactory
    ) {

        $this->groupFactory = $groupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        if(version_compare($context->getVersion(), '1.0.1', "<")) {
            /** Create a customer Group */
            /** @var \Magento\Customer\Model\Group $group */
            $setup->startSetup();

            /* Create a multiple customer group */
            $setup->getConnection()->insertForce(
                $setup->getTable('customer_group'),
                ['customer_group_code' => 'Ctacorriente', 'tax_class_id' => 3]
            );

            $setup->endSetup();
        }
    }

}
