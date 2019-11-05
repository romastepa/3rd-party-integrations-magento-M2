<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Subscriberexport;

use Magento\Backend\Block\Widget\Form\Container;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;

/**
 * Class Edit
 * @package Emarsys\Emarsys\Block\Adminhtml\Subscriberexport
 */
class Edit extends Container
{
    /**
     * Core registry
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * Edit constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Request\Http $request
     * @param EmarsysHelper $emarsysHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        EmarsysHelper $emarsysHelper,
        array $data = []
    ) {
        $this->getRequest = $request;
        $this->storeManager = $context->getStoreManager();
        $this->_coreRegistry = $registry;
        $this->emarsysHelper = $emarsysHelper;
        parent::__construct($context, $data);
    }

    /**
     * Subscriber edit block
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Emarsys_Emarsys';
        $this->_controller = 'adminhtml_subscriberexport';
        $storeId = $this->getRequest->getParam('store');
        $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);
        if ($storeId != '') {
            $url = $this->getUrl("emarsys_emarsys/subscriberexport/subscriberExport", ["storeId" => $storeId]);
        } else {
            $url = $this->getUrl("emarsys_emarsys/subscriberexport/subscriberExport");
        }

        parent::_construct();

        $this->buttonList->remove('back');
        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        if (isset($storeId)) {
            $this->buttonList->add(
                'showreport',
                [
                    'label' => __("Export"),
                    'class' => 'save primary',
                    'onclick' => "bulkSubscriberExportData('" . $url . "')",
                ]
            );
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
