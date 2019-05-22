<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\Api\Api as EmarsysModelApiApi;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class Customer
 * @package Emarsys\Emarsys\Helper
 */
class Customer extends AbstractHelper
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var EmarsysModelApiApi
     */
    protected $api;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * Customer constructor.
     *
     * @param Context $context
     * @param EmarsysHelper $emarsysHelper
     * @param EmarsysModelApiApi $api
     * @param StoreManager $storeManager
     */
    public function __construct(
        Context $context,
        EmarsysHelper $emarsysHelper,
        EmarsysModelApiApi $api,
        StoreManager $storeManager
    ) {
        ini_set('default_socket_timeout', 1000);
        $this->context = $context;
        $this->emarsysHelper = $emarsysHelper;
        $this->api = $api;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $storeId
     * @return bool|mixed
     */
    public function getEmarsysCustomerSchema($storeId)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            $this->api->setWebsiteId($store->getWebsiteId());
            $response = $this->api->sendRequest('GET', 'field/translate/en');
            return $response['body'];
        } catch (\Exception $e) {
            $this->emarsysHelper->addErrorLog(
                'getEmarsysCustomerSchema',
                htmlentities($e->getMessage()),
                $storeId,
                'getEmarsysCustomerSchema'
            );
        }
        return false;
    }
}
