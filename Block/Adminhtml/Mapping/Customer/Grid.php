<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */


namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer;

use Emarsys\Emarsys\Helper\Data as EmarsysHelperData;
use Emarsys\Emarsys\Model\CustomerMagentoAttsFactory;
use Emarsys\Emarsys\Model\ResourceModel\Customer;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Data\Collection;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Module\Manager;

/**
 * Class Grid
 *
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var Manager
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
     * @var Data
     */
    protected $backendHelper;

    /**
     * @var Customer
     */
    protected $resourceModelCustomer;

    /**
     * @var Collection
     */
    protected $dataCollection;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var Type
     */
    protected $entityType;

    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CustomerMagentoAttsFactory
     */
    protected $customerMagentoAttsFactory;

    /**
     * @var EmarsysHelperData
     */
    protected $emarsysHelper;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param Type $entityType
     * @param Attribute $attribute
     * @param Collection $dataCollection
     * @param DataObjectFactory $dataObjectFactory
     * @param Customer $resourceModelCustomer
     * @param Manager $moduleManager
     * @param CustomerMagentoAttsFactory $customerMagentoAttsFactory
     * @param EmarsysHelperData $emarsysHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Type $entityType,
        Attribute $attribute,
        Collection $dataCollection,
        DataObjectFactory $dataObjectFactory,
        Customer $resourceModelCustomer,
        Manager $moduleManager,
        CustomerMagentoAttsFactory $customerMagentoAttsFactory,
        EmarsysHelperData $emarsysHelper,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->moduleManager = $moduleManager;
        $this->backendHelper = $backendHelper;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->_storeManager = $context->getStoreManager();
        $this->customerMagentoAttsFactory = $customerMagentoAttsFactory;
        $this->emarsysHelper = $emarsysHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->session->setData('gridData', '');
        $storeId = $this->getRequest()->getParam('store');
        if (!$storeId) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
        $collection = $this->customerMagentoAttsFactory->create()->getCollection()
            ->addFieldToFilter('store_id', ['eq' => $storeId]);
        $collection->setOrder('main_table.frontend_label', 'ASC');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

        $this->addColumn(
            'frontend_label',
            [
                'header' => __('Magento Customer Attribute'),
                'type' => 'varchar',
                'index' => 'frontend_label',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Renderer\MagentoAttribute',
            ]
        );
        $this->addColumn(
            'emarsys_contact_header',
            [
                'header' => __('Emarsys Customer Attribute'),
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Renderer\EmarsysCustomer',
                'filter' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->session->setData('gridData', '');
        $storeId = $this->getRequest()->getParam('store');
        if (!$storeId) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
        $this->session->setData('store', $storeId);
        $mappingExists = $this->resourceModelCustomer->customerMappingExists($storeId);
        if ($mappingExists == FALSE) {
            $customerAttData = $this->attribute->getCollection()
                ->addFieldToSelect('frontend_label')
                ->addFieldToSelect('attribute_code')
                ->addFieldToSelect('entity_type_id')
                ->addFieldToFilter('entity_type_id', ['in' => '1, 2'])
                ->getData();
            $this->resourceModelCustomer->insertCustomerMageAtts($customerAttData, $storeId);
        }
    }

    /**
     * @return string
     */
    public function getMainButtonsHtml()
    {
        return parent::getMainButtonsHtml();
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
