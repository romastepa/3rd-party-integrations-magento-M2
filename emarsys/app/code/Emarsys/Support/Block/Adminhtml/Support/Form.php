<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Support
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Support\Block\Adminhtml\Support;

use Magento\Backend\Block\Widget\Form\Container;
use Emarsys\Support\Controller\Adminhtml\Support;

class Form extends \Magento\Backend\Block\Widget\Form
{
    protected $_template = 'support.phtml';
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setId('supportGeneralForm');
        $this->setTemplate('support.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->isFromError = $this->getRequest()->getParam('error') === 'true';
        $systemRequirementsBlock = $this->getLayout()->createBlock('Emarsys\Support\Block\Adminhtml\Support\Requirements', '', ['is_support_mode' => true]);
        $this->setChild('system_requirements', $systemRequirementsBlock);

        return parent::_beforeToHtml();
    }
}
