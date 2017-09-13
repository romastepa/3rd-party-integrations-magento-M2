<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Emarsys Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Helper;

use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Model\Api\Api;

class Field extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var Api
     */
    protected $api;

    protected $customerResourceModel;

    /**
     * 
     * @param Data $dataHelper
     * @param Api $api
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Customer $customer
     */
    public function __construct(
        Data $dataHelper,
        Api $api,
        \Magento\Framework\App\Helper\Context $context,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customer
    ) {
    
        ini_set('default_socket_timeout', 5000);
        $this->logger = $context->getLogger();
        $this->api = $api;
        $this->dataHelper = $dataHelper;
        $this->customerResourceModel = $customer;
    }

    /**
     * 
     * @param type $storeId
     * @return array
     */
    public function getEmarsysOptionSchema($storeId)
    {
        $this->dataHelper->getEmarsysAPIDetails($storeId);
        $emarsysContactFields = $this->customerResourceModel->getEmarsysContactFields($storeId);
        $emarsysFieldOptions = [];
        foreach ($emarsysContactFields as $emarsysField) {
            if ($emarsysField['type'] == "singlechoice" || $emarsysField['type'] == "multichoice") {
                $response = $this->dataHelper->send('GET', 'field/' . $emarsysField['emarsys_field_id'] . '/choice');
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
