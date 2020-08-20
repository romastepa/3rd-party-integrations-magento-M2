<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Class Queue
 */
class ProcessPro extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $connectionName = 'sho_pro';

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $this->connectionName);
    }

    protected function _construct()
    {
        $this->_init('emarsys_product_export', 'entity_id');
    }
}
