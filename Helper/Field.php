<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Emarsys\Emarsys\Model\ResourceModel\Customer as EmarsysResourceModelCustomer;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\Api\Api as EmarsysModelApiApi;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class Field
 * @package Emarsys\Emarsys\Helper
 */
class Field extends AbstractHelper
{
    /**
     * @var EmarsysHelper
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
     * @var EmarsysResourceModelCustomer
     */
    protected $customerResourceModel;

    /**
     * Field constructor.
     *
     * @param EmarsysHelper $emarsysHelper
     * @param EmarsysModelApiApi $api
     * @param StoreManager $storeManager
     * @param Context $context
     * @param EmarsysResourceModelCustomer $customer
     */
    public function __construct(
        EmarsysHelper $emarsysHelper,
        EmarsysModelApiApi $api,
        StoreManager $storeManager,
        Context $context,
        EmarsysResourceModelCustomer $customer
    ) {
        ini_set('default_socket_timeout', 5000);
        $this->emarsysHelper = $emarsysHelper;
        $this->api = $api;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customer;
    }

    /**
     * @param $storeId
     * @return array
     * @throws \Exception
     * @throws \Zend_Json_Exception
     */
    public function getEmarsysOptionSchema($storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $this->api->setWebsiteId($store->getWebsiteId());
        $emarsysContactFields = $this->customerResourceModel->getEmarsysContactFields($storeId);
        $emarsysFieldOptions = [];
        foreach ($emarsysContactFields as $emarsysField) {
            if ($emarsysField['type'] == "singlechoice" || $emarsysField['type'] == "multichoice") {
                $response = $this->api->sendRequest('GET', 'field/' . $emarsysField['emarsys_field_id'] . '/choice');
                $jsonDecode = \Zend_Json::decode($response);
                if (is_array($jsonDecode['data'])) {
                    foreach ($jsonDecode['data'] as $optionField) {
                        $emarsysFieldOptions[$emarsysField['emarsys_field_id']][] = $optionField;
                    }
                }
            }
        }

        return $emarsysFieldOptions;
    }
}
