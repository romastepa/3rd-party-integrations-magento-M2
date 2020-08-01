<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

class Process extends \Magento\Framework\Model\AbstractModel
{
    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Emarsys\Emarsys\Model\ResourceModel\Process::class);
    }

    public function getExportData()
    {
        $table = $this->getResource()->getTable('emarsys_product_export_data');
        $select = $this->getResource()->getConnection()
            ->select()
            ->from($table, 'export_data');

        return $this->getResource()->getConnection()->fetchOne($select);
    }
}
