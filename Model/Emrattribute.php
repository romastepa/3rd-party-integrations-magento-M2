<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

/**
 * Class Emrattribute
 * @package Emarsys\Emarsys\Model
 */
class Emrattribute extends \Magento\Framework\Model\AbstractModel
{
    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Emarsys\Model\ResourceModel\Emrattribute');
    }
}
