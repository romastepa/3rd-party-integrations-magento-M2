<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */


namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Field;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $_collection;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Field
     */
    protected $resourceModelField;

    /**
     * @var \Magento\Framework\Data\Collection
     */
    protected $dataCollection;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $entityType;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $attribute;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Eav\Model\Entity\Type $entityType
     * @param \Magento\Eav\Model\Entity\Attribute\Option $attribute
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Field $resourceModelField
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Eav\Model\Entity\Type $entityType,
        \Magento\Eav\Model\Entity\Attribute\Option $attribute,
        \Magento\Framework\Data\Collection $dataCollection,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Field $resourceModelField,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Eav\Model\Config $eavConfig,
        $data = []
    ) {
    
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->moduleManager = $moduleManager;
        $this->backendHelper = $backendHelper;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resourceModelField = $resourceModelField;
        $this->_storeManager = $context->getStoreManager();
        $this->eavConfig = $eavConfig;
        parent::__construct($context, $backendHelper, $data = []);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->session->setData('gridData', '');
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        $this->session->setData('store', $storeId);
    }


    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $entitiesToMerge = ['customer_address', 'customer'];
        $entitiesData = [];
        if (!empty($entitiesToMerge)) {
            foreach ($entitiesToMerge as $entityCode) {
                $entity = $this->entityType->loadByCode($entityCode);
                $entitiesData['id'][] = $entity->getId();
            }
        }
        $entitiesData['id'] = array_unique($entitiesData['id']);

        $customCollection = $this->attribute->getCollection();
        $customCollection->addFieldToFilter('ea.entity_type_id', ['in' => $entitiesData['id']]);
        $customCollection->addFieldToFilter('ea.frontend_label', ['notnull' => true]);

        $customCollection->addFieldToFilter('ea.frontend_input', ['in' => ['select', 'multiselect']]);

        $customCollection->join(
            ['ea' => 'eav_attribute'],
            'ea.attribute_id = main_table.attribute_id',
            ['ea.attribute_code', 'ea.frontend_label', 'ea.frontend_input']
        );

        $customCollection->join(
            ['attrOptVal' => 'eav_attribute_option_value'],
            'main_table.option_id = attrOptVal.option_id',
            ['attrOptVal.value', 'attrOptVal.value_id']
        );
        $customCollection->addFieldToFilter('attrOptVal.store_id', ['eq' => 0]);
        $customCollection->setOrder('ea.frontend_label', 'ASC');
        $this->setCollection($customCollection);

        return parent::_prepareCollection();
    }


    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

        $this->addColumn(
            'value',
            [
                'header' => __('Magento Fields'),
                'type' => 'varchar',
                'index' => 'value',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Field\Renderer\Option',
            ]
        );
        $this->addColumn(
            'emarsys_contact_header',
            [
                'header' => __('Emarsys Fields'),
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Field\Renderer\FieldOption',
                'filter' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getMainButtonsHtml()
    {
        $html = parent::getMainButtonsHtml();
        $storeId = $this->getRequest()->getParam('store');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        $moduleName = 'emarsys_emarsys';
        $controllerNamePleaseSelect = 'mapping_field';
        $controllerNameField = 'mapping_field';
        $controllerNameProduct = 'mapping_product';
        $controllerNameCustomer = 'mapping_customer';
        $controllerNameOrder = 'mapping_order';
        $controllerNameEvent = 'mapping_event';

        $adminUrlEmarsysProduct = $this->backendHelper->getUrl($moduleName . '/' . $controllerNameProduct . '/index/store/' . $storeId);
        $adminUrlEmarsysField = $this->backendHelper->getUrl($moduleName . '/' . $controllerNameField . '/index/store/' . $storeId);
        $adminUrlEmarsysCustomer = $this->backendHelper->getUrl($moduleName . '/' . $controllerNameCustomer . '/index/store/' . $storeId);
        $adminUrlEmarsysOrder = $this->backendHelper->getUrl($moduleName . '/' . $controllerNameOrder . '/index/store/' . $storeId);
        $adminUrlEmarsysEvent = $this->backendHelper->getUrl($moduleName . '/' . $controllerNameEvent . '/index/store/' . $storeId);
        $adminUrlEmarsysPleaseSelect = $this->backendHelper->getUrl($moduleName . '/' . $controllerNamePleaseSelect . '/index/store/' . $storeId);
        $html = parent::getMainButtonsHtml();

        return $html;
    }

    /**
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
