<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel\CustomerMagentoAtts;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initializes collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Emarsys\Emarsys\Model\CustomerMagentoAtts::class,
            \Emarsys\Emarsys\Model\ResourceModel\CustomerMagentoAtts::class
        );
    }
}
