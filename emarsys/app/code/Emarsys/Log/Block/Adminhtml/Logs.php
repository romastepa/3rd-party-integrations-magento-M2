<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Log
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Log\Block\Adminhtml;

use Magento\Framework\Stdlib\DateTime\Timezone;

/**
 * Class Logs
 * @package Emarsys\Log\Block\Adminhtml
 */
class Logs extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = '';
    /**
     * @var \Emarsys\Log\Model\LogScheduleFactory
     */
    protected $logScheduleFactory;
    /**
     * @var TimeZone
     */
    protected $timezone;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Emarsys\Log\Model\LogScheduleFactory $logScheduleFactory
     * @param Timezone $timezone
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Emarsys\Log\Model\LogScheduleFactory $logScheduleFactory,
        TimeZone $timezone,
        $data = []
    ) {
    
        $this->logScheduleFactory = $logScheduleFactory;
        $this->timezone = $timezone;

        parent::__construct($context, $data = []);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock('Emarsys\Log\Block\Adminhtml\Logs\Grid', 'loggrid')
        );
        $this->_template = 'logs/grid.phtml';

        return parent::_prepareLayout();
    }

    /**
     * Render grid
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }

    /**
     * @return mixed
     */
    public function getLogDetails()
    {
        $collection = $this->logScheduleFactory->create()->load($this->_request->getParam('schedule_id'));
        return $collection->getData();
    }

    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $storeId;
    }

    /**
     * @return Timezone
     */
    public function getDateObject()
    {
        return $this->timezone;
    }
}
