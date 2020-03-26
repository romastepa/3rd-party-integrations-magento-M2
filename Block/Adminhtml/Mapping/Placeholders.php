<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Block\Adminhtml\Mapping;

/**
 * Class Placeholders
 */
class Placeholders extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var string
     */
    protected $_template = 'mapping/event/view.phtml';

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Emarsys\Emarsys\Block\Adminhtml\Mapping\Placeholders\Grid::class,
                'emarsys.placeholders.grid'
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

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLoadImageUrl()
    {
        $store = $this->_storeManager->getStore();
        $url = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);

        return $url . "adminhtml/Magento/backend/en_US/images/loader-2.gif";
    }
}
