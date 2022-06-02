<?php

namespace Customcode\Updateorder\Setup;
 
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
 
class UpgradeData implements UpgradeDataInterface
{
 
    /**
     * Upgrade Data
     *
     * @param ModuleDataSetupInterface $setup   Module Data Setup
     * @param ModuleContextInterface   $context Module Context
     *
     * @return void
     */
    public function upgrade( ModuleDataSetupInterface $setup, ModuleContextInterface $context )
    {
        /* 
        https://www.damianculotta.com.ar/magento/instalar-y-actualizar-informacion-con-los-data-scripts-en-magento2/
        */
        //$installer = $setup;
 
        if (version_compare($context->getVersion(), '1.0.2')) {

            /* if ($installer->getTableRow($installer->getTable('barbanet_samplemodule'), 'row_id', 1)) {
                $installer->updateTableRow(
                    $installer->getTable('barbanet_samplemodule'),
                    'row_id',
                    1,
                    'description',
                    'Actualizado contenido con script'
                );
            } */

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $salesSetup = $objectManager->create('Magento\Sales\Setup\SalesSetup');
            
            $salesSetup->addAttribute('order', 'is_valid_sap', ['type' =>'varchar','length' => 255]);
            $quoteSetup = $objectManager->create('Magento\Quote\Setup\QuoteSetup');
        }
        if (version_compare($context->getVersion(), '1.0.3')) {

            /* if ($installer->getTableRow($installer->getTable('barbanet_samplemodule'), 'row_id', 1)) {
                $installer->updateTableRow(
                    $installer->getTable('barbanet_samplemodule'),
                    'row_id',
                    1,
                    'description',
                    'Actualizado contenido con script'
                );
            } */

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $salesSetup = $objectManager->create('Magento\Sales\Setup\SalesSetup');
            
            $salesSetup->addAttribute('order', 'fecha_compromiso_despacho', ['type' =>'varchar','length' => 255]);
            $quoteSetup = $objectManager->create('Magento\Quote\Setup\QuoteSetup');
        }

        if (version_compare($context->getVersion(), '1.0.4')) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $salesSetup = $objectManager->create('Magento\Sales\Setup\SalesSetup');
            
            $salesSetup->addAttribute('order', 'tipo_despacho', ['type' =>'varchar','length' => 255]);
            $salesSetup->addAttribute('order', 'direccion_oficina', ['type' =>'varchar','length' => 255]);
            $quoteSetup = $objectManager->create('Magento\Quote\Setup\QuoteSetup');
        }
    }
 
}
