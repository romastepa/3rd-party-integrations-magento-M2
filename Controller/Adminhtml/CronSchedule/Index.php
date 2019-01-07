<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\CronSchedule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime\Timezone;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\CronSchedule
 */
class Index extends Action
{
    /**
     * @var Timezone
     */
    protected $timezone;

    /**
     * Params constructor.
     * @param Timezone $timezone
     * @param Context $context
     */
    public function __construct(
        Timezone $timezone,
        Context $context
    ) {
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * @return void|$this
     * @throws \RuntimeException
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Cronjob Timeline (%1)', $this->timezone->formatDateTime('now', 2)));
        $this->_view->renderLayout();
    }
}
