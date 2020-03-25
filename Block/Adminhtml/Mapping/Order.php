<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Mapping;

/**
 * Class Order
 */
class Order extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var string
     */
    protected $_template = 'mapping/order/view.phtml';

    /**
     * Order constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Emarsys\Emarsys\Block\Adminhtml\Mapping\Order\Grid::class,
                'emarsys.order.grid'
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
