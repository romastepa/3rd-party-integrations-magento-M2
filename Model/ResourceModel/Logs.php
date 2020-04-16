<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Logs extends AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_log_details', 'id');
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getLogsData()
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), ['created_at', 'description', 'message_type'])
            ->order('id DESC');

        return $this->getConnection()->fetchAll($select);
    }
}
