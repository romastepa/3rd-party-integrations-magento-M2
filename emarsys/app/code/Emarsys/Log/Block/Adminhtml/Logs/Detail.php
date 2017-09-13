<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Log
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Log\Block\Adminhtml\Logs;

class Detail extends \Magento\Backend\Block\Template
{
    protected $_template = 'logs/detail.phtml';

    protected $logsFactory;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Emarsys\Log\Model\LogsFactory $logsFactory,
        $data = []
    ) {
    
        $this->logsFactory = $logsFactory;
        parent::__construct($context, $data = []);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    function getLog()
    {
        $collection = $this->logsFactory->create()->load($this->_request->getParam('id'));
        return $collection->getData();
    }
}
