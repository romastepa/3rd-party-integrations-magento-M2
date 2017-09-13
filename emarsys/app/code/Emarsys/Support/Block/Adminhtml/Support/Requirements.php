<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Support
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Support\Block\Adminhtml\Support;

use Magento\Backend\Block\Widget\Form\Container;

class Requirements extends \Magento\Backend\Block\Widget\Form
{


    /**
     * @var \Emarsys\Support\Helper\Data
     */
    protected $helper;
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Emarsys\Support\Helper\Data $helper,
        array $data = []
    ) {
    
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->setId('developmentInspectionRequirements');
        $this->setTemplate('requirements.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->requirements = $this->helper->getRequirementsInfo();
        return parent::_beforeToHtml();
    }
}
