<?php

/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\ResourceModel;

use Emarsys\Emarsys\Model\Logs;
use Magento\Framework\{
    App\Config\ScopeConfigInterface,
    Model\ResourceModel\Db\AbstractDb,
    Model\ResourceModel\Db\Context,
    Stdlib\DateTime\DateTime
};

/**
 * Class Sync
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Sync extends AbstractDb
{
    /**
     * @var int
     */
    protected $_syncEnable;

    /**
     * @var int
     */
    protected $_syncId;

    /**
     * @var int
     */
    protected $_syncAutoCron;

    /**
     * @var
     */
    protected $_connection;

    /**
     * @var string
     */
    protected $_fields;

    /**
     * @var
     */
    protected $_where;

    /**
     * @var
     */
    protected $_title;

    /**
     * @var
     */
    protected $_coll;

    protected $date;

    protected $scopeConfigInterface;

    /**
     * @param Context $context
     * @param DateTime $date
     * @param Logs $emarsysLogs
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        DateTime $date,
        Logs $emarsysLogs,
        ScopeConfigInterface $scopeConfigInterface,
        $connectionName = null
    ) {
    
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->scopeConfigInterface = $scopeConfigInterface;
        parent::__construct($context, $connectionName);
    }

    /**
     * constructor
     */
    public function _construct()
    {
        $this->_init('emarsys_attribute_sync', 'id');
    }



    /**
     * 
     * @param type $entity
     * @param type $storeId
     * @return array
     */
    public function getAttributes($entity = null, $storeId)
    {
        $connection = $this->getConnection();

        $table = false;
        if ($entity == 'product') {
            $table = $this->getTable('emarsys_emarsys_product_attributes');
        }
        if ($entity == 'customer') {
            $table = $this->getTable('emarsys_emarsys_customer_attributes');
        }
        if ($entity == 'order') {
            $table = $this->getTable('emarsys_emarsys_order_attributes');
        }

        if ($entity == 'customproductattributes') {
            $table = $this->getTable('emarsys_custom_product_attributes');
        }

        if ($table) {
            $select = $connection->select()
                ->from($table)
                ->where('store_id = ?', $storeId);

            return $connection->fetchAll($select);
        }

        return [];
    }

    /**
     * 
     * @param type $syncId
     * @param int $storeId
     * @return array
     */
    public function getLastSyncDate($syncId = null, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = 1;
        }
        $subselect = $this->getConnection()->select()
            ->from($this->getTable('emarsys_syncstatus'), 'MAX(id)')
            ->where('status = ?', 'SUCCESS')
            ->where('sync_id = ?', '$syncId')
            ->where('store_id = ?', $storeId);

        $select = $this->getConnection()->select()
            ->from($this->getTable('emarsys_syncstatus'), ['syncdate' => 'DATE_FORMAT(MAX(finished_at), "%Y-%m-%d %H:%i:%s")'])
            ->where('id in (?)', $subselect);

        try {
            $lastsyncDate = $this->getConnection()->fetchOne($select);
            if ($lastsyncDate == null || $lastsyncDate == '') {
                $lastsyncDate = $this->date->date('Y-m-d H:i:s', strtotime('-5 days'));
            }

            return $lastsyncDate;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getLastSyncDate');
        }
    }

    /**
     * 
     * @param type $path
     * @param type $scope
     * @param type $scopeId
     * @return array
     */
    public function getDataFromCoreConfig($path, $scope = null, $scopeId = null)
    {
        try {
            if ($scope && $scopeId) {
                return $this->scopeConfigInterface->getValue($path, $scope, $scopeId);
            } else {
                return  $this->scopeConfigInterface->getValue($path);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $scopeId, 'getDataFromCoreConfig in Sync.php');
        }
    }
}
