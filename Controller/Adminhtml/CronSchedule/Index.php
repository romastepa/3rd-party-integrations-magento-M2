<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\CronSchedule;

use Magento\Backend\App\Action;

/**
 * Class Index
 * @package Emarsys\Emarsys\Controller\Adminhtml\CronSchedule
 */
class Index extends Action
{
    /**
     * @return void|$this
     * @throws \RuntimeException
     */
    public function execute()
    {
        $time = \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Stdlib\DateTime\TimezoneInterface::class
        )->date()->format('M j, Y, g:i:s a');
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Cronjob Timeline (%1)', $time));
        $this->_view->renderLayout();
    }
}
