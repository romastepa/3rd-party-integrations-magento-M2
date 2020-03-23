<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Logs;

/**
 * Class Detail
 */
class Detail extends \Magento\Backend\Block\Template
{
    protected $_template = 'logs/detail.phtml';

    protected $logsFactory;

    /**
     * Detail constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Emarsys\Emarsys\Model\LogsFactory $logsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Emarsys\Emarsys\Model\LogsFactory $logsFactory,
        $data = []
    ) {
        $this->logsFactory = $logsFactory;
        parent::__construct($context, $data);
    }

    public function getLog()
    {
        $collection = $this->logsFactory->create()->load($this->_request->getParam('id'));
        return $collection->getData();
    }
}
