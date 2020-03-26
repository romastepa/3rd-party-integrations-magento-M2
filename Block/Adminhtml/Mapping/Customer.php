<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Mapping;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Customer
 */
class Customer extends Container
{
    /**
     * @var string
     */
    protected $_template = 'mapping/customer/view.phtml';

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Emarsys\Emarsys\Block\Adminhtml\Mapping\Customer\Grid::class,
                'emarsys.customer.grid'
            )
        );
        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }
}
