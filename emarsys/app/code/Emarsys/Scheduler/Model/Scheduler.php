<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Scheduler\Model;

class Scheduler extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Scheduler\Model\ResourceModel\Scheduler');
    }
}
