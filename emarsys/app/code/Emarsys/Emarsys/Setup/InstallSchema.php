<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Setup;

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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'emarsys_contact_field'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_contact_field'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )->addColumn(
                'emarsys_field_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                150,
                [],
                'emarsys_contact_field id'
            )->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                150,
                ['nullable' => false],
                'Name of the field'
            )
            ->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true],
                'Type whether choice or input'
            )->addColumn(
                'string_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true],
                'string key'
            )
            ->addColumn(
                'language_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                150,
                ['nullable' => false],
                'en or nl etc'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Store Id'
            )
            ->setComment('Contact Fields');
        $installer->getConnection()->createTable($table);


        /**
         * Create table 'magento_event_templates'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('magento_event_templates'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_event_template_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Magento Event Template Name'
            )
            ->addColumn(
                'emarsys_event_template',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Emarsys Event Template'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Magento Event Templates');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_variable_mapping'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_variable_mapping'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_variable',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Magento Variable'
            )
            ->addColumn(
                'magento_event_template',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Magento Event Template'
            )
            ->addColumn(
                'emarsys_variable',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Emarsys Variable'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Emarsys Variable Mapping');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_contact_field_option'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_contact_field_option'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )
            ->addColumn(
                'field_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                150,
                [],
                'emarsys_customer_fields fk'
            )
            ->addColumn(
                'option_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '64k',
                [],
                'Emarsys Option Id'
            )
            ->addColumn(
                'option_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Emarsys Option Name'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Store Id'
            )
            ->setComment('Emarsys options related to choice fields');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_magento_customer_field_mapping'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_customer_option_mapping'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_option_attr_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Magento option id fk'
            )
            ->addColumn(
                'emarsys_option_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'emarsys option fk'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Emarsys Magento field mapping');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_magento_customer_field_mapping'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_customer_field_mapping'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Magento customer attribute id fk'
            )
            ->addColumn(
                'emarsys_contact_field',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'emarsys customer fields fk'
            )
            ->addColumn(
                'magento_custom_attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Magento Custom Attribute Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Emarsys Magento field mapping');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_magento_order_field_mapping'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_order_field_mapping'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_column_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Magento Tables Column Names'
            )
            ->addColumn(
                'emarsys_order_field',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys Order Fields'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->setComment('Emarsys Magento order mapping');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_option_mapping'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_option_mapping'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_option_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Code'
            )
            ->addColumn(
                'emarsys_option_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Label'
            )->addColumn(
                'emarsys_field_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'emarsys_field_id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('magento emarsys option mapping');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_product_sync_temp'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_product_sync_temp'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )->addColumn(
                'emarsys_inventory_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys inventory Id'
            )->addColumn(
                'magento_sku',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Magento Sku'
            )->addColumn(
                'magento_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Magento Id'
            )->addColumn(
                'magento_lastsyncdate',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Magento Last Sync Date'
            )->addColumn(
                'emarsys_lastsyncdate',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys Last Sync Date'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )->addColumn(
                'entity_ref',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Reference'
            )->addColumn(
                'flg',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Flag'
            );

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_product_mapping'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_product_mapping'))
            ->addColumn(
                'emarsys_contact_field',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'magento_attr_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Magento Attribute Code'
            )->addColumn(
                'emarsys_attr_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys Attribute Code'
            )->addColumn(
                'sync_direction',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Sync Direction'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('emarsys product mapping');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_emarsys_product_attributes'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_emarsys_product_attributes'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Code'
            )
            ->addColumn(
                'label',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Label'
            )
            ->addColumn(
                'field_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Field Type'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('emarsys emarsys product attributes');
        $installer->getConnection()->createTable($table);
        /**
         * Create table 'emarsys_custom_product_attributes'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_custom_product_attributes'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )->addColumn(
                'attributeid',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Attribute Id'
            )->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Description'
            )->addColumn(
                'controlltype',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Controll Type'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('Emarsys Custome Product Attributes');

        $installer->getConnection()->createTable($table);
        /**
         * Create table 'emarsys_events'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_events'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )->addColumn(
                'event_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Event Id'
            )->addColumn(
                'emarsys_event',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Emarsys Event'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('Emarsys Events');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'emarsys_magento_events' for magento events
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_magento_events'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )->addColumn(
                'magento_event',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Magento Event'
            )->addColumn(
                'config_path',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Magento Template Configuration path'
            )
            ->setComment('Magento Events');

        $installer->getConnection()->createTable($table);


        /**
         * Create table 'emarsys_event_mapping' for mapping emarsys magento events with emarsys events.
         */

        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_event_mapping'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'ID'
            )->addColumn(
                'magento_event_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Magento Event ID'
            )->addColumn(
                'emarsys_event_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Emarsys Event ID'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->setComment('Emarsys Event Mapping');

        $installer->getConnection()->createTable($table);

        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_order_export_status'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Order Id'
            )
            ->addColumn(
                'exported',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Exported'
            )
            ->addColumn(
                'status_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Status Code'
            )
            ->setComment('Magento Order Export Status');

        $installer->getConnection()->createTable($table);

        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_creditmemo_export_status'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Order Id'
            )
            ->addColumn(
                'exported',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Exported'
            )
            ->addColumn(
                'status_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Status Code'
            )
            ->setComment('Magento Credit Memo Export Status');

        $installer->getConnection()->createTable($table);


        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_order_queue'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Entity Id'
            )
            ->addColumn(
                'website_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Website Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            )
            ->addColumn(
                'entity_type_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Entity Type Id'
            )
            ->setComment('Entity type id');

        $installer->getConnection()->createTable($table);
        
        
        $table = $installer->getConnection()
            ->newTable($installer->getTable('emarsys_magento_customer_attributes'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Id'
            )
            ->addColumn(
                'attribute_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Attribute Code'
            )
          
            ->addColumn(
                'attribute_code_custom',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Attribute Code Custom'
            )
            ->addColumn(
               'frontend_label',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Frontend Label'
            )
            ->addColumn(
                'entity_type_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Entity Type Id'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Store Id'
            );

        $installer->getConnection()->createTable($table);

// create table 'emarsys_suite2_queue'
        $emarsys_suite2_queue = $installer->getTable('emarsys_suite2_queue');

        $table = $installer->getConnection()->newTable($installer->getTable($emarsys_suite2_queue))
            ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
            'ID'
        )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default'=>NULL],
                'Entity ID'
            )->addColumn(
                'website_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default'=>NULL],
                'Website ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default'=>NULL],
                'Store ID'
            )->addColumn(
                'entity_type_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default'=>NULL],
                'Entity type ID'
            )->addColumn(
                'params',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '2M',
                ['default' => null],
                'Extra parameters'
            )->addColumn(
                'hit_count',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Hit Count'
            );
            $installer->getConnection()->createTable($table);
// create table 'emarsys_placeholders_mapping'

        $emarsys_placeholders_mapping = $installer->getTable('emarsys_placeholders_mapping');
        $table = $installer->getConnection()->newTable($installer->getTable($emarsys_placeholders_mapping))->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
            'ID'
        )->addColumn(
            'event_mapping_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Event Mapping Id'
        )->addColumn(
            'magento_placeholder_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Magento Placeholder Name'
        )->addColumn(
            'emarsys_placeholder_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Emarsys Placeholder Name'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Store Id'
        );
       $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}
