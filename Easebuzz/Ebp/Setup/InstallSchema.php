<?php

/**
 * Copyright © 2017 Easebuzz Payment.
 */

namespace Easebuzz\Ebp\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface {

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {


        $installer = $setup;
        $installer->startSetup();
        /**
         * Create table 'ease_buzz_debug'
         */
        $table = $installer->getConnection()
                ->newTable($installer->getTable('ease_buzz_debug'))
                ->addColumn(
                        'debug_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Primary Key'
                )
                ->addColumn(
                        'order_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 20, ['default' => '0'], 'Order ID'
                )
                ->addColumn(
                        'request_debug_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT], 'Request Debug At'
                )
                ->addColumn(
                        'response_debug_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT], 'response Debug At'
                )
                ->addColumn(
                        'request_body', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['default' => ''], 'Request'
                )
                ->addColumn(
                        'response_body', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['default' => ''], 'Response'
                );
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }

}
