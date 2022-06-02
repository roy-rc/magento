<?php

namespace Customcode\Updateorder\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        if (version_compare($context->getVersion(), '1.0.0') < 0){
	
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $salesSetup = $objectManager->create('Magento\Sales\Setup\SalesSetup');
            
            $salesSetup->addAttribute('order', 'compromiso_despacho', ['type' =>'varchar','length' => 150]);
            $salesSetup->addAttribute('order', 'oc_cliente', ['type' =>'varchar','length' => 150]);
            $salesSetup->addAttribute('order', 'fecha_oc_cliente', ['type' =>'varchar','length' => 150]);
            $salesSetup->addAttribute('order', 'code_sap', ['type' =>'varchar','length' => 150]);
            $quoteSetup = $objectManager->create('Magento\Quote\Setup\QuoteSetup');
			
		}
        /* 
            SELECT DISTINCT TABLE_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE COLUMN_NAME IN ('oc_cliente','valid_sap')
                AND TABLE_SCHEMA='magento';
        */
    }
}