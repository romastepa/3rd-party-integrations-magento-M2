<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders;

use Magento\{
    Backend\Block\Widget\Grid\Extended,
    Backend\Block\Template\Context,
    Backend\Helper\Data,
    Framework\Message\ManagerInterface as MessageManagerInterface,
    Framework\App\ResponseFactory
};
use Emarsys\Emarsys\{
    Model\PlaceholdersFactory,
    Helper\Data as EmarsysHelper
};

class Grid extends Extended
{
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
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var PlaceholdersFactory
     */
    protected $emarsysEventPlaceholderMappingFactory;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var ResponseFactory
     */
    protected $_responseFactory;

    /**
     * @var MessageManagerInterface
     */
    protected $_messageManager;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param PlaceholdersFactory $emarsysEventPlaceholderMappingFactory
     * @param EmarsysHelper $emarsysHelper
     * @param MessageManagerInterface $messageManager
     * @param ResponseFactory $responseFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        EmarsysHelper $emarsysHelper,
        MessageManagerInterface $messageManager,
        ResponseFactory $responseFactory,
        $data = []
    ) {
        $this->session = $context->getBackendSession();
        $this->backendHelper = $backendHelper;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
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

        $eventMappingCollection = $this->emarsysEventPlaceholderMappingFactory->create()
            ->getCollection()
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
                $redirectUrl = $this->_url->getUrl(
                    'emarsys_emarsys/mapping_event/index',
                    ["store_id" => $storeId]
                );
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
            [
                'header' => __('Magento Variable'),
                'index' => 'magento_placeholder_name',
            ]
        );

        $this->addColumn(
            'emarsys_placeholder_name',
            [
                'header' => __('Emarsys Placeholder'),
                'index' => 'emarsys_placeholder_name',
                'renderer' => \Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders\Renderer\EmarsysPlaceholders::class,
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

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMainButtonsHtml()
    {
        $url = $this->backendHelper->getUrl('emarsys_emarsys/mapping_event/saveplaceholdermapping');
        return '<form id="eventsPlaceholderForm"  method="post" action="' . $url . '">'
            . '<input name="form_key" type="hidden" value="' . $this->formKey->getFormKey() . '" />'
            . '<input type="hidden" id="placeholderData" name="placeholderData" value="">'
            . '</form>';
    }
}
