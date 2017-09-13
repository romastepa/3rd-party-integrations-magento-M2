<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Log
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Log\Model;

class CronSchedule extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Log\Model\ResourceModel\CronSchedule');
    }
}
