<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "1.0.14", "<")) {
            $tableName = $setup->getTable('emarsys_customer_field_mapping');
            $connection = $setup->getConnection();
            if ($connection->isTableExists($tableName)) {
                $connection->delete(
                    $tableName,
                    ['emarsys_contact_field in (?)' => [0, 27, 28, 29, 30, 33, 34, 36, 47, 48]]
                );
            }

            $tableName = $setup->getTable('core_config_data');
            $connection->update(
                $tableName,
                ['path' => '(TRIM(LEADING "crontab/default/jobs/" FROM `path`))'],
                $connection->quoteInto('`path` like ?', '%crontab/default/jobs/emarsys%')
            );
            $connection->update(
                $tableName,
                ['value' => 1],
                $connection->quoteInto('path = ?', 'contacts_synchronization/emarsys_emarsys/realtime_sync')
            );
        }

        if (version_compare($context->getVersion(), "1.0.15", "<")) {
            $tableName = $setup->getTable('emarsys_magento_events');
            $setup->getConnection()->delete(
                $tableName,
                ['config_path = ?' => 'admin/emails/forgot_email_template']
            );
        }

        if (version_compare($context->getVersion(), "1.1.0", "<")) {
            $tableName = $setup->getTable('core_config_data');
            $setup->getConnection()->query(
                'UPDATE ' . $tableName
                . ' SET `path` = REPLACE(`path`, "emarsys_settings/", "emartech/")'
                . ' WHERE (INSTR(`path`, "emarsys_settings/"))'
            );
        }

        $setup->endSetup();
    }
}
