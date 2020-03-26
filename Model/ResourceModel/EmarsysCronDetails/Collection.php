<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel\EmarsysCronDetails;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialization here
     *
     * @return voids
     */
    protected function _construct()
    {
        $this->_init(
            \Emarsys\Emarsys\Model\EmarsysCronDetails::class,
            \Emarsys\Emarsys\Model\ResourceModel\EmarsysCronDetails::class
        );
    }
}
