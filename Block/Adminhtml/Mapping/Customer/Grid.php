<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */


namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer;

use Emarsys\Emarsys\{
    Helper\Data as EmarsysHelper,
    Model\CustomerMagentoAttsFactory,
    Model\ResourceModel\Customer
};
use Magento\{
    Backend\Block\Template\Context,
    Backend\Helper\Data as BackendHelper,
    Eav\Model\Entity\Attribute,
    Eav\Model\Entity\Type,
    Framework\Data\Collection,
    Framework\DataObjectFactory,
    Framework\Module\Manager
};

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
     * @var CustomerMagentoAttsFactory
     */
    protected $customerMagentoAttsFactory;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param BackendHelper $backendHelper
     * @param Type $entityType
     * @param Attribute $attribute
     * @param Collection $dataCollection
     * @param DataObjectFactory $dataObjectFactory
     * @param Customer $resourceModelCustomer
     * @param Manager $moduleManager
     * @param CustomerMagentoAttsFactory $customerMagentoAttsFactory
     * @param EmarsysHelper $emarsysHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        Type $entityType,
        Attribute $attribute,
        Collection $dataCollection,
        DataObjectFactory $dataObjectFactory,
        Customer $resourceModelCustomer,
        Manager $moduleManager,
        CustomerMagentoAttsFactory $customerMagentoAttsFactory,
        EmarsysHelper $emarsysHelper,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->moduleManager = $moduleManager;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
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
