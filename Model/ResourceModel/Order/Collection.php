<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel\Order;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Initializes collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Emarsys\Emarsys\Model\Order',
            'Emarsys\Emarsys\Model\ResourceModel\Order'
        );
    }
}
