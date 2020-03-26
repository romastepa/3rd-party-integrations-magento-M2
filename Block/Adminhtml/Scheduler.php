<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Context;

/**
 * Class Scheduler
 */
class Scheduler extends Template
{
    protected $_template = 'cron/grid.phtml';

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Emarsys\Emarsys\Block\Adminhtml\Scheduler\Grid::class,
                'emarsys.logs.grid'
            )
        );
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
}
