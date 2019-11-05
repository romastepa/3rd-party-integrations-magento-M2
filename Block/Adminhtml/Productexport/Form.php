<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Productexport;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Magento\Backend\Block\Widget\Context;

/**
 * Class Form
 * @package Emarsys\Emarsys\Block\Adminhtml\Productexport
 */
class Form extends \Magento\Backend\Block\Widget\Form
{
    protected $_template = 'bulkexport/bulkexport.phtml';

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @param Context $context
     * @param EmarsysHelper $emarsysHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        EmarsysHelper $emarsysHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->emarsysHelper = $emarsysHelper;
        $this->setId('productExportForm');
    }

    protected function _beforeToHtml()
    {
        $this->isFromError = $this->getRequest()->getParam('error') === 'true';
        return parent::_beforeToHtml();
    }

    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);;
    }
}
