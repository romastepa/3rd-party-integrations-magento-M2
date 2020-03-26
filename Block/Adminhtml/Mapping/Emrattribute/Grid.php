<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Emrattribute;

use Emarsys\Emarsys\Model\OrderFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;

/**
 * Class Grid
 */
class Grid extends Extended
{
    /**
     * @var Data
     */
    protected $backendHelper;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * Grid constructor.
     * @param OrderFactory $orderFactory
     * @param Context $context
     * @param Data $backendHelper
     * @param array $data
     */
    public function __construct(
        OrderFactory $orderFactory,
        Context $context,
        Data $backendHelper,
        $data = []
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $customCollection = $this->orderFactory->create()
            ->getCollection()
            ->setOrder('magento_column_name', 'ASC');
        $this->setCollection($customCollection);
    }

    /**
     * @param Product|DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
