<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Scheduler\Block;

use Magento\Framework\UrlFactory;

class BaseBlock extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Emarsys\Scheduler\Helper\Data
     */
    protected $_devToolHelper;

    /**
     * @var \Magento\Framework\Url
     */
    protected $_urlApp;

    /**
     * @var \Emarsys\Scheduler\Model\Config
     */
    protected $_config;

    /**
     * @param Context $context
     */
    public function __construct(
        \Emarsys\Scheduler\Block\Context $context
    ) {
    
        $this->_devToolHelper = $context->getSchedulerHelper();
        $this->_config = $context->getConfig();
        $this->_urlApp=$context->getUrlFactory()->create();
        parent::__construct($context);
    }

    /**
     * Function for getting event details
     * @return array
     */
    public function getEventDetails()
    {
        return  $this->_devToolHelper->getEventDetails();
    }

    /**
     * Function for getting current url
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlApp->getCurrentUrl();
    }

    /**
     * Function for getting controller url for given router path
     * @param string $routePath
     * @return string
     */
    public function getControllerUrl($routePath)
    {
        return $this->_urlApp->getUrl($routePath);
    }

    /**
     * Function for getting current url
     * @param string $path
     * @return string
     */
    public function getConfigValue($path)
    {
        return $this->_config->getCurrentStoreConfigValue($path);
    }

    /**
     * Function canShowScheduler
     * @return bool
     */
    public function canShowScheduler()
    {
        $isEnabled=$this->getConfigValue('scheduler/module/is_enabled');
        if ($isEnabled) {
            $allowedIps=$this->getConfigValue('scheduler/module/allowed_ip');
            if (is_null($allowedIps)) {
                return true;
            } else {
                $remoteIp=$_SERVER['REMOTE_ADDR'];
                if (strpos($allowedIps, $remoteIp) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
}
