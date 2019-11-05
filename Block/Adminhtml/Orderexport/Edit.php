<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Orderexport;

use Magento\Backend\Block\Widget\Form\Container;

/**
 * Class Edit
 * @package Emarsys\Emarsys\Block\Adminhtml\Orderexport
 */
class Edit extends Container
{
    /**
     * @var \Emarsys\Emarsys\Helper\Data
     */
    protected $emarsysHelper;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Emarsys\Emarsys\Helper\Data $emarsysHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        array $data = []
    ) {
        $this->getRequest = $request;
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
        $this->_controller = 'adminhtml_orderexport';
        $storeId = $this->getRequest->getParam('store');
        $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);
        if ($storeId != '') {
            $url = $this->getUrl("emarsys_emarsys/orderexport/orderExport", ["storeId" => $storeId]);
        } else {
            $url = $this->getUrl("emarsys_emarsys/orderexport/orderExport");
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
                    'onclick' => "bulkOrderExportData('" . $url . "')",
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
