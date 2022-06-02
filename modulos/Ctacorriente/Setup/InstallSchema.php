<?php
/**
 * Copyright © 2021 Customcode. All rights reserved.
 */

namespace Customcode\Ctacorriente\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
	
        $installer = $setup;

        $installer->startSetup();

		/**
         * Create table 'order_ctacorriente'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('order_ctacorriente')
        )
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true
            ],
            'ID'
        )
        ->addColumn(
            'increment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => '0'],
            'N.Orden'
        )
        ->addColumn(
            'url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => '0'],
            'Ctacorriente Url'
        )
        ->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )
		/*{{CedAddTableColumn}}}*/
		
		
        ->setComment(
            'Customcode Ctacorriente url exito'
        );
		
		$installer->getConnection()->createTable($table);
		/*{{CedAddTable}}*/

        $installer->endSetup();

    }
}

