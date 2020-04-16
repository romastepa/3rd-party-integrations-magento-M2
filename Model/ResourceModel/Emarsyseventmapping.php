<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Api\StoreRepositoryInterface;

class Emarsyseventmapping extends AbstractDb
{
    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;
    /**
     * @var Attribute
     */
    protected $attribute;
    /**
     * @var Type
     */
    protected $entityType;

    /**
     *
     * @param Context $context
     * @param Type $entityType
     * @param Attribute $attribute
     * @param StoreRepositoryInterface $storeRepository
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        Type $entityType,
        Attribute $attribute,
        StoreRepositoryInterface $storeRepository,
        $connectionName = null
    ) {
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->storeRepository = $storeRepository;
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emarsys_event_mapping', 'id');
    }

    /**
     * Truncate the mapping table
     *
     * @param  $storeId
     * @throws LocalizedException
     */
    public function truncateMappingTable($storeId)
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            $this->getConnection()->quoteInto('store_id = ?', $storeId)
        );
    }
}
