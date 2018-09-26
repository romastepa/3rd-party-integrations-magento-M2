<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\ResourceModel\Product;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Emarsys\Emarsys\Model\ResourceModel\Product
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
        $this->_init('Emarsys\Emarsys\Model\Product', 'Emarsys\Emarsys\Model\ResourceModel\Product');
    }
}
