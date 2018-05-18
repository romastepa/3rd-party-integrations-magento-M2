<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

/**
 * Class OrderStatuses
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class OrderStatuses
{
    /**
     * @var
     */
    protected $resource;
    /**
     * @var
     */
    protected $connection;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Config\Model\ResourceModel\Config $config,
        \Emarsys\Emarsys\Model\Logs $emarsysLogs,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_resource = $resource;
        $this->storeManager = $storeManager;
        $this->emarsysLogs = $emarsysLogs;
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $sql = "SELECT order_status.*, order_state.state, order_state.is_default, order_state.visible_on_front FROM " . $this->config->getTable('sales_order_status') . " as order_status INNER JOIN " . $this->config->getTable('sales_order_status_state') . " AS order_state ON order_state.status = order_status.status WHERE order_state.state IN ('closed', 'complete', 'processing')";
        try {
            $orderStatusesCollection = $connection->fetchAll($sql);
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'toOptionArray(orderStatus)');
        }
        $orderStatusesArray = [];
        foreach ($orderStatusesCollection as $order) {
            $orderStatusesArray[] = ['value' => $order['status'], 'label' => $order['label']];
        }
        return $orderStatusesArray;
    }
}
