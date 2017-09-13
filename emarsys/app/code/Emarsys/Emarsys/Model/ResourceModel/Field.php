<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Model\ResourceModel;

/**
 * Customer resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Field extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * 
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param type $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        $connectionName = null
    ) {
    
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
        $this->_init('emarsys_option_mapping', 'id');
    }

    /**
     * Checking count of the mapping table
     */
    public function checkOptionsMapping($storeId)
    {
        $customerAttributes = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_option_mapping') . " WHERE store_id =" . $storeId);
        return $customerAttributes;
    }

    /**
     * truncate the mapping table
     * @param $storeId
     */
    public function truncateMappingTable($storeId)
    {
        $this->getConnection()->query("DELETE FROM  " . $this->getTable("emarsys_option_mapping") . " WHERE store_id = $storeId");
    }

    /**
     * @param array $fieldOptions
     * @param $storeId
     * @return bool
     */
    public function updateOptionSchema($fieldOptions = [], $storeId)
    {
        $this->getConnection()->query("DELETE FROM " . $this->getTable("emarsys_contact_field_option") . " WHERE store_id = " . $storeId);
        if (count($fieldOptions) > 0) {
            foreach ($fieldOptions as $fieldId => $arrOptions) {
                foreach ($arrOptions as $option) {
                    $data = ['field_id' => $fieldId, 'option_id' => $option['id'], 'option_name' => $option['choice'], 'store_id' => $storeId];
                    $data = $this->_prepareDataForTable(
                        new \Magento\Framework\DataObject($data),
                        $this->getTable("emarsys_contact_field_option")
                    );

                    $this->getConnection()->insert($this->getTable("emarsys_contact_field_option"), $data);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function getEmarsysFieldOption($storeId)
    {
        $query = "SELECT ecfo.option_id,ecfo.option_name,ecf.name,ecf.emarsys_field_id,ecf.type FROM " . $this->getTable('emarsys_contact_field_option')." ecfo inner join ".$this->getTable('emarsys_contact_field')." ecf on ecf.emarsys_field_id = ecfo.field_id and ecfo.store_id =  ".$storeId;
        try {
            $result = $this->getConnection()->fetchAll($query);
            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $attributeCode
     * @return string
     */
    public function getCustomerAttributeId($attributeCode)
    {
        if (strpos($attributeCode, 'BILLADD_') !== false) {
            $entityTypeId = 2;
            $attributeCode = str_replace('BILLADD_', '', $attributeCode);
        } else {
            $entityTypeId = 1;
        }
        $attributeId = $this->getConnection()->fetchOne("SELECT attribute_id FROM " . $this->getTable('eav_attribute') . " where attribute_code = '" . $attributeCode . "' and entity_type_id = $entityTypeId ");
        if ($attributeId) {
            return $attributeId;
        } else {
            return '';
        }
    }

    public function checkSelectedOption($magentoOptionId, $emarsysOptionId, $emarsysFieldId, $storeId)
    {
        $magentoSelectedOptions = $this->getConnection()->fetchOne("SELECT count(*) as assigned FROM " . $this->getTable('emarsys_option_mapping') . " eom inner join " . $this->getTable('eav_attribute_option') . " eao on eao.option_id = eom.magento_option_id WHERE eao.option_id = '$magentoOptionId' and eom.emarsys_option_id='$emarsysOptionId' and eom.emarsys_field_id ='$emarsysFieldId'
         and eom.store_id='" . $storeId . "'");
        return $magentoSelectedOptions;
    }

    /**
     * @return string
     */
    public function getEmarsysOptionCount($storeId)
    {
        $emarsysFieldCount = $this->getConnection()->fetchOne("SELECT count(*) FROM " . $this->getTable('emarsys_contact_field_option') . ' where store_id=' . $storeId);
        return $emarsysFieldCount;
    }

    public function getRecommendedFieldAttribute($storeId)
    {
        $emarsysCodes = [
            '1' => '1-5', //male-gender
            '2' => '2-5', //female-gender
        ];
        return $emarsysCodes;
    }

    public function getEmarsysOptionId($optionId, $emarsys_contact_field, $websiteId)
    {
        return $emarsys_contact_field;
    }
}
