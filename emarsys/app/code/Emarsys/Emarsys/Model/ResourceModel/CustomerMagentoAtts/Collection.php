<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel\CustomerMagentoAtts;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Initializes collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Emarsys\Emarsys\Model\CustomerMagentoAtts', 'Emarsys\Emarsys\Model\ResourceModel\CustomerMagentoAtts');
    }

}
