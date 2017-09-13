<?php
namespace Emarsys\Emarsys\Helper;

class Store extends \Magento\Framework\View\Element\Template
{
    protected $_storeManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
    
        $this->_storeManager = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Get website identifier
     * @param null $storeId
     * @return int
     */
    public function getWebsiteId($storeId = null)
    {
        if (is_null($storeId)) {
            return $this->_storeManager->getStore()->getWebsiteId();
        } else {
            return $this->_storeManager->getStore($storeId)->getWebsiteId();
        }
    }

    /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    /**
     * Get Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    /**
     * Get current url for store
     *
     * @param bool|string $fromStore Include/Exclude from_store parameter from URL
     * @return string
     */
    public function getStoreUrl($fromStore = true)
    {
        return $this->_storeManager->getStore()->getCurrentUrl($fromStore);
    }

    /**
     * Check if store is active
     *
     * @return boolean
     */
    public function isStoreActive()
    {
        return $this->_storeManager->getStore()->isActive();
    }
}
