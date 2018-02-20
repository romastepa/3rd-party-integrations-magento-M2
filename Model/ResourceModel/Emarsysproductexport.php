<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class Emarsysproductexport
 * @package Emarsys\Emarsys\Model\ResourceModel
 */
class Emarsysproductexport extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_product_export', 'entity_id');
    }

}