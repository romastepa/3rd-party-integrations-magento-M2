<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Product;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
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
     * @var \Magento\Framework\Data\Collection
     */
    protected $dataCollection;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Emarsys\Emarsys\Helper\Data
     */
    protected $emarsysHelper;

    /**
     * Grid constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $_collection
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Emarsys\Emarsys\Helper\Data $emarsysHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $_collection,
        \Magento\Framework\Data\Collection $dataCollection,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->_collection = $_collection;
        $this->backendHelper = $backendHelper;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->emarsysHelper = $emarsysHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('gridGrid');
        $this->setDefaultSort('grid_record_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
        $this->setVarNameFilter('grid_record');
        $this->session->setData('gridData', '');
        $storeId = $this->getRequest()->getParam('store');
        $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);
        $methods = $this->_collection->addVisibleFilter();
        $productMethods = [];
        foreach ($methods as $productCode => $productModel) {
            $productMethods['product'] = $productCode;
            $rowObj = $this->dataObjectFactory->create();
            $rowObj->setData($productMethods);
            $this->dataCollection->addItem($rowObj);
        }

        $gridArray = [];
        foreach ($this->dataCollection as $col) {
            $attrCode = $col->getAttributeCode();
            $gridArray[$attrCode] = ['emarsys_attr_code' => 'please select', 'store_id' => $storeId];
        }
        $this->session->setData('gridData', $gridArray);
        $this->session->setData('storeId', $storeId);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collection->addVisibleFilter()
            ->setOrder('main_table.frontend_label', 'ASC');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'frontend_label',
            [
                'header' => __('Magento Product Attribute'),
                'type' => 'varchar',
                'index' => 'frontend_label',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'emarsys_attr_code',
            [
                'header' => __('Emarsys Attribute'),
                'renderer' => \Emarsys\Emarsys\Block\Adminhtml\Mapping\Product\Renderer\EmarsysProduct::class,
                'filter' => false,
            ]
        );

        return parent::_prepareColumns();
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
