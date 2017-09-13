<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */


namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders;

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
     * @var \Emarsys\Emarsys\Model\ResourceModel\Event
     */
    protected $resourceModelEvent;

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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Eav\Model\Entity\Type $entityType
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Event $resourceModelEvent
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Eav\Model\Entity\Type $entityType,
        \Magento\Eav\Model\Entity\Attribute $attribute,
        \Magento\Framework\Data\Collection $dataCollection,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Event $resourceModelEvent,
        \Emarsys\Emarsys\Model\EmarsyseventmappingFactory $EmarsyseventmappingFactory,
        \Emarsys\Emarsys\Model\PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        \Emarsys\Emarsys\Helper\Data $EmarsysHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        $data = []
    ) {
    
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->moduleManager = $moduleManager;
        $this->backendHelper = $backendHelper;
        $this->EmarsyseventmappingFactory = $EmarsyseventmappingFactory;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resourceModelEvent = $resourceModelEvent;
        $this->_storeManager = $context->getStoreManager();
        $this->EmarsysHelper = $EmarsysHelper;
        $this->_url = $context->getUrlBuilder();
        $this->_responseFactory = $responseFactory;
        $this->_messageManager = $messageManager;
        parent::__construct($context, $backendHelper, $data = []);
    }


    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $storeId = $this->getRequest()->getParam('store');
        $mappingId = $this->getRequest()->getParam('mapping_id');
        if (!isset($storeId)) {
            $storeId = 1;
        }
        $EventMappingCollection = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection()->addFieldToFilter("store_id", $storeId)->addFieldToFilter("event_mapping_id", $mappingId);
        $this->setCollection($EventMappingCollection);
        return parent::_prepareCollection();
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->session->setData('gridData', '');
        $mapping_id = $this->getRequest()->getParam('mapping_id');
        $storeId = $this->getRequest()->getParam('store_id');

        if (!isset($storeId)) {
            $storeId = 1;
        }

        $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection();
        $emarsysEventPlaceholderMappingColl->addFieldToFilter('event_mapping_id', $mapping_id);
        $emarsysEventPlaceholderMappingColl->addFieldToFilter('store_id', $storeId);


        if (!$emarsysEventPlaceholderMappingColl->getSize()) {
            $val = $this->EmarsysHelper->insertFirstimeMappingPlaceholders($mapping_id, $storeId);
            if ($val == "") {
                $this->_messageManager->addError(__("Please Assign Email Template to event"));
                $RedirectUrl = $this->_url->getUrl('emarsys_emarsys/mapping_event/index/store_id/' . $storeId);
                $this->_responseFactory->create()->setRedirect($RedirectUrl)->sendResponse();
            }
        }
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {


        $this->addColumn(
            'magento_placeholder_name',
            ['header' => __('Magento Variable'),
                'index' => 'magento_placeholder_name']
        );


        /*$this->addColumn(
            'magento_event_template_name',
            [
                'header' => __('Magento Email Templates'),
                'type' => 'varchar',
                'index' => 'magento_event_template_name',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
            );*/

        $this->addColumn(
            'emarsys_placeholder_name',
            [
                'header' => __('Emarsys Placeholder'),
                'index' => 'emarsys_placeholder_name',
                'renderer' => 'Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders\Renderer\EmarsysPlaceholders',
                'filter' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */


    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }

    /**
     * @return string
     */
    public function getMainButtonsHtml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $formKey = $objectManager->get('Magento\Framework\Data\Form\FormKey');
        $form = '<form id="eventsPlaceholderForm"  method="post" action="' . $this->backendHelper->getUrl('emarsys_emarsys/mapping_event/saveplaceholdermapping') . '"><input name="form_key" type="hidden" value="' . $formKey->getFormKey() . '" /><input type="hidden" id="placeholderData" name="placeholderData" value=""></form>';

        return $form;

    }

}
