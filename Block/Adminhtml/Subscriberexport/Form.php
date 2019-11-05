<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Subscriberexport;

use Magento\Backend\Block\Widget\Form\Container;
use Emarsys\Emarsys\Controller\Adminhtml\Subscriberexport;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\Subscriberexport
 */
class Form extends \Magento\Backend\Block\Widget\Form
{
    protected $_template = 'bulkexport/bulkexport.phtml';

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param EmarsysHelper $emarsysHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        EmarsysHelper $emarsysHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->emarsysHelper = $emarsysHelper;
        $this->setId('subscriberExportForm');
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->isFromError = $this->getRequest()->getParam('error') === 'true';
        return parent::_beforeToHtml();
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);;
    }
}
