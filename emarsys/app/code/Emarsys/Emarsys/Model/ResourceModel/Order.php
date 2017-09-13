<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;
    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $attribute;
    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $entityType;

    /**
     * 
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Eav\Model\Entity\Type $entityType
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param type $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Eav\Model\Entity\Type $entityType,
        \Magento\Eav\Model\Entity\Attribute $attribute,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        $connectionName = null
    ) {
    
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->storeRepository = $storeRepository;
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_order_field_mapping', 'id');
    }

    public function getEmarsysAttrCount($storeId)
    {
        $emarsysFieldCount = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_event_mapping') . " WHERE store_id=" . $storeId . "");
        return $emarsysFieldCount;
    }

    public function checkOrderMapping($storeId)
    {
        $customerAttributes = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_event_mapping') . " WHERE store_id =" . $storeId);
        return $customerAttributes;
    }

    public function getSalesOrderColumnNames()
    {
        $stmt = $this->getConnection()->query("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE  `TABLE_NAME`='" . $this->getTable('sales_order') . "'");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function insertIntoMappingTable($data, $storeId)
    {
        foreach ($data as $key => $value) {
            //$stmt = $this->getConnection()->query("REPLACE INTO  ".$this->getTable('emarsys_order_field_mapping')." (`magento_attribute_id`,`emarsys_order_field`,`store_id`) VALUES ( '".$value['COLUMN_NAME']."', '".$value['COLUMN_NAME']."', '".$storeId."'  ) ");
            $stmt = $this->getConnection()->query("SELECT magento_column_name FROM " . $this->getTable('emarsys_order_field_mapping') . " WHERE magento_column_name = '" . $value['COLUMN_NAME'] . "' ");
            $result = $stmt->fetch();
            if ($result == 0) {
                //insert the attribute
                $stmt = $this->getConnection()->query("INSERT INTO  " . $this->getTable('emarsys_order_field_mapping') . " (magento_column_name,emarsys_order_field,store_id) VALUES ('" . $value['COLUMN_NAME'] . "','" . '' . "','" . $storeId . "')  "); // for the first time enter the empty records for the emarsys id
            }
        }
    }

    public function insertIntoMappingTableStaticData($data, $storeId)
    {
        foreach ($data as $key => $value) {
            //$stmt = $this->getConnection()->query("REPLACE INTO  ".$this->getTable('emarsys_order_field_mapping')." (`magento_attribute_id`,`emarsys_order_field`,`store_id`) VALUES ( '".$value['COLUMN_NAME']."', '".$value['COLUMN_NAME']."', '".$storeId."'  ) ");
            $stmt = $this->getConnection()->query("SELECT magento_column_name FROM " . $this->getTable('emarsys_order_field_mapping') . " WHERE magento_column_name = '" . $value . "' ");
            $result = $stmt->fetch();
            if ($result == 0) {
                //insert the attribute
                $stmt = $this->getConnection()->query("INSERT INTO  " . $this->getTable('emarsys_order_field_mapping') . " (magento_column_name,emarsys_order_field,store_id) VALUES ('" . $value . "','" . $key . "','" . $storeId . "')  "); // for the first time enter the empty records for the emarsys id
            }
        }
    }

    public function insertIntoMappingTableCustomValue($data, $storeId)
    {
        foreach ($data as $key => $value) {
            $stmt = $this->getConnection()->query("SELECT magento_column_name FROM " . $this->getTable('emarsys_order_field_mapping') . " WHERE magento_column_name = '" . $key . "' ");
            $result = $stmt->fetch();
            if ($result == 0) {
                //insert the attribute
                $stmt = $this->getConnection()->query("INSERT INTO  " . $this->getTable('emarsys_order_field_mapping') . " (magento_column_name,emarsys_order_field,store_id) VALUES ('" . $key . "','" . $value . "','" . $storeId . "')  "); // for the first time enter the empty records for the emarsys id
            } else {
                //else update the attribute value
                $stmt = $this->getConnection()->query("UPDATE " . $this->getTable('emarsys_order_field_mapping') . " SET emarsys_order_field = '" . $value . "' WHERE magento_column_name = '" . $key . "'");
            }
        }
    }

    public function orderMappingExists()
    {
        $stmt = $this->getConnection()->query("SELECT magento_column_name FROM " . $this->getTable('emarsys_order_field_mapping') . "  ");
        $result = $stmt->fetch();
        return $result;
    }

    public function getEmarsysOrderFields()
    {
        $heading = ['order', 'date', 'customer', 'item', 'unit_price', 'c_sales_amount', 'quantity'];
        $excludeFields = implode("', '", $heading);
        $headingStr = implode(',', $heading);
        $stmt = $this->getConnection()->query("SELECT emarsys_order_field FROM " . $this->getTable('emarsys_order_field_mapping') . " WHERE emarsys_order_field NOT IN ('" . $excludeFields . "') ");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getOrderColValue($emarsysField, $orderId)
    {
        $heading = ['order', 'date', 'customer', 'item', 'unit_price', 'c_sales_amount', 'quantity'];
        if (in_array($emarsysField, $heading)) {
            return;
        }
        $stmt = $this->getConnection()->query("SELECT magento_column_name FROM " . $this->getTable('emarsys_order_field_mapping') . " WHERE emarsys_order_field = '" . $emarsysField . "'  ");
        $result = $stmt->fetch();
        if ($result['magento_column_name'] == 'created_at') {
            $stmt = $this->getConnection()->query("SELECT ".$result['magento_column_name']." as created_at FROM ".$this->getTable('sales_order')." WHERE entity_id = '".$orderId."'  ");
        } elseif ($result['magento_column_name'] == 'updated_at') {
            $stmt = $this->getConnection()->query("SELECT ".$result['magento_column_name']." as updated_at FROM ".$this->getTable('sales_order')." WHERE entity_id = '".$orderId."'  ");
        } elseif ($result['magento_column_name'] == 'customer_dob') {
            $stmt = $this->getConnection()->query("SELECT DATE_FORMAT(".$result['magento_column_name'].",'%Y-%m-%d') as magento_column_value FROM ".$this->getTable('sales_order')." WHERE entity_id = '".$orderId."'  ");
        } else {
            $stmt = $this->getConnection()->query("SELECT ".$result['magento_column_name']." as magento_column_value FROM ".$this->getTable('sales_order')." WHERE entity_id = '".$orderId."'  ");
        }

        return $stmt->fetch();
    }

    public function getStores()
    {
        $stmt = $this->getConnection()->query("SELECT * FROM " . $this->getTable('store') . " ");
        $result = $stmt->fetchAll();
        return $result;
    }
}
