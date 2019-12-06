<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders;

use Magento\{
    Backend\Block\Widget\Grid\Extended,
    Backend\Block\Template\Context,
    Backend\Helper\Data,
    Eav\Model\Entity\Type,
    Eav\Model\Entity\Attribute,
    Framework\Data\Collection,
    Framework\DataObjectFactory,
    Framework\Module\Manager as ModuleManager,
    Framework\Message\ManagerInterface as MessageManagerInterface,
    Framework\App\ResponseFactory
};
use Emarsys\Emarsys\{
    Model\ResourceModel\Event,
    Model\EmarsyseventmappingFactory,
    Model\PlaceholdersFactory,
    Helper\Data as EmarsysHelper
};

/**
 * Class Grid
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders
 */
class Grid extends Extended
{
    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var
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
     * @var Event
     */
    protected $resourceModelEvent;

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
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var EmarsyseventmappingFactory
     */
    protected $emarsysEventMappingFactory;

    /**
     * @var PlaceholdersFactory
     */
    protected $emarsysEventPlaceholderMappingFactory;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * Grid constructor.
     * @param Context $context
     * @param Data $backendHelper
     * @param Type $entityType
     * @param Attribute $attribute
     * @param Collection $dataCollection
     * @param DataObjectFactory $dataObjectFactory
     * @param Event $resourceModelEvent
     * @param EmarsyseventmappingFactory $emarsysEventMappingFactory
     * @param PlaceholdersFactory $emarsysEventPlaceholderMappingFactory
     * @param EmarsysHelper $emarsysHelper
     * @param ModuleManager $moduleManager
     * @param MessageManagerInterface $messageManager
     * @param ResponseFactory $responseFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Type $entityType,
        Attribute $attribute,
        Collection $dataCollection,
        DataObjectFactory $dataObjectFactory,
        Event $resourceModelEvent,
        EmarsyseventmappingFactory $emarsysEventMappingFactory,
        PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        EmarsysHelper $emarsysHelper,
        ModuleManager $moduleManager,
        MessageManagerInterface $messageManager,
        ResponseFactory $responseFactory,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->entityType = $entityType;
        $this->attribute = $attribute;
        $this->moduleManager = $moduleManager;
        $this->backendHelper = $backendHelper;
        $this->emarsysEventMappingFactory = $emarsysEventMappingFactory;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
        $this->dataCollection = $dataCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resourceModelEvent = $resourceModelEvent;
        $this->_storeManager = $context->getStoreManager();
        $this->emarsysHelper = $emarsysHelper;
        $this->_url = $context->getUrlBuilder();
        $this->_responseFactory = $responseFactory;
        $this->_messageManager = $messageManager;
        $this->formKey = $context->getFormKey();
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return Extended
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareCollection()
    {
        $storeId = $this->getRequest()->getParam('store');
        $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);
        $mappingId = $this->getRequest()->getParam('mapping_id');

        $eventMappingCollection = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection()
            ->addFieldToFilter("store_id", $storeId)
            ->addFieldToFilter("event_mapping_id", $mappingId);

        $this->setCollection($eventMappingCollection);

        return parent::_prepareCollection();
    }

    /**
     * @throws \Exception
     */
    protected function _construct()
    {
        parent::_construct();
        $this->session->setData('gridData', '');
        $mappingId = $this->getRequest()->getParam('mapping_id');
        $storeId = $this->getRequest()->getParam('store_id');
        $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);

        $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection()
            ->addFieldToFilter('event_mapping_id', $mappingId)
            ->addFieldToFilter('store_id', $storeId);

        if (!$emarsysEventPlaceholderMappingColl->getSize()) {
            $val = $this->emarsysHelper->insertFirstTimeMappingPlaceholders($mappingId, $storeId);
            if (!$val) {
                $this->_messageManager->addErrorMessage(__("Please Assign Email Template to event"));
                $redirectUrl = $this->_url->getUrl('emarsys_emarsys/mapping_event/index', ["store_id" => $storeId]);
                $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
            }
        }
    }

    /**
     * @return Extended
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'magento_placeholder_name',
            ['header' => __('Magento Variable'),
                'index' => 'magento_placeholder_name']
        );

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
        $url = $this->backendHelper->getUrl('emarsys_emarsys/mapping_event/saveplaceholdermapping');
        $form = '<form id="eventsPlaceholderForm"  method="post" action="' . $url . '">'
        . '<input name="form_key" type="hidden" value="' . $this->formKey->getFormKey() . '" />'
        . '<input type="hidden" id="placeholderData" name="placeholderData" value="">'
        . '</form>';

        return $form;
    }
}
