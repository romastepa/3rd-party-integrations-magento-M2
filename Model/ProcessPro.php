<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

class ProcessPro extends \Magento\Framework\Model\AbstractModel
{
    protected $_collectionName = 'sho_pro';

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Emarsys\Emarsys\Model\ResourceModel\ProcessPro::class);
    }

    public function getExportData()
    {
        $table = $this->getResource()->getTable('emarsys_product_export_data');
        $select = $this->getResource()->getConnection('sho_pro')
            ->select()
            ->from($table, 'export_data');

        return $this->getResource()->getConnection('sho_pro')->fetchOne($select);
    }
}
