<?php

/**
 * Copyright © 2017 Easebuzz Payment.
 */

namespace Easebuzz\Ebp\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface {

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Install Data
         */
        $data = [
            ['order_id' => '1', 'request_debug_at' => '0000-00-00 00:00:00', 'response_debug_at' => '0000-00-00 00:00:00', 'request_body' => 'null', 'response_body' => 'null'],
        ];
        
        foreach ($data as $bind) {
            $installer->getConnection()
                    ->insertForce($installer->getTable('ease_buzz_debug'), $bind);
        }
        $installer->endSetup();
    }

}