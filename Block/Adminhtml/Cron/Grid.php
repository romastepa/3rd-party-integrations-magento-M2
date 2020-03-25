<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Cron;

/**
 * Class Grid
 */
class Grid extends \Magento\Backend\Block\Template
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
                \Emarsys\Emarsys\Block\Adminhtml\Cron::class,
                'emarsys.categorylogs.grid'
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
