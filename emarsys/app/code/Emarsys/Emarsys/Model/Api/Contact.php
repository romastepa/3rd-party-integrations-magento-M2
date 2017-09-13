<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Model\Api;

use Emarsys\Emarsys\Model\Api\Api;
use Magento\Customer\Model\Customer;
use Emarsys\Emarsys\Model\ResourceModel\Customer as customerResourceModel;
use Emarsys\Emarsys\Model\ResourceModel\Field;
use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use \Emarsys\Log\Helper\Logs;
use \Psr\Log\LoggerInterface;

/**
 * API class for Emarsys API wrappers
 */
class Contact
{
    /**
     * @var Api
     */
    protected $api;
    /**
     * @var
     */
    protected $customer;
    /**
     * @var
     */
    protected $customerResourceModel;
    /**
     * @var
     */
    protected $dataHelper;
    /**
     * @var
     */
    protected $fieldResourceModel;
    /**
     * @var
     */
    protected $logger;

    /**
     * @param Api $api
     * @param Customer $customer
     * @param customerResourceModel $customerResourceModel
     * @param Field $fieldResourceModel
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param Data $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Api $api,
        Customer $customer,
        customerResourceModel $customerResourceModel,
        Field $fieldResourceModel,
        DateTime $date,
        Logs $logsHelper,
        Data $dataHelper,
        \Psr\Log\LoggerInterface $logger,
        \Emarsys\Emarsys\Model\Queue $queueModel,
        \Emarsys\Emarsys\Model\ResourceModel\Queue\Collection $queueModelColl,
        \Magento\Customer\Model\ResourceModel\Customer\Collection $customerColl
    ) {
    
        $this->customer = $customer;
        $this->api = $api;
        $this->dataHelper = $dataHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->fieldResourceModel = $fieldResourceModel;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->logger = $logger;
        $this->queue = $queueModel;
        $this->queueColl = $queueModelColl;
        $this->custColl = $customerColl;
    }

    /**
     * @param $customerId
     * @param $websiteId
     * @return int -- 2 No data found for mapped fields
     */
    public function syncContact($customerId, $websiteId, $storeId, $cron = 0)
    {
        $objCustomer = $this->customer->load($customerId);
        $arrCustomer = $objCustomer->getData();
        $logsArray['job_code'] = 'customer';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Customer is sync to Emarsys';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray);
        $this->api->setWebsiteId($websiteId);

        $buildRequest = [];
        $keyField = $this->dataHelper->getContactUniqueField($websiteId);
        if ($keyField == 'email') {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Email', $storeId);
            $buildRequest[$buildRequest['key_id']] = $objCustomer->getEmail();
        } elseif ($keyField == 'magento_id') {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer ID', $storeId);
            $buildRequest[$buildRequest['key_id']] = $customerId;
        } elseif ($keyField == 'unique_id') {
            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
            $buildRequest[$buildRequest['key_id']] = $objCustomer->getEmail() . "#" . $websiteId . "#" . $storeId;
        }

        $keyId = $this->customerResourceModel->getKeyId('Email', $storeId);
        $buildRequest[$keyId] = $objCustomer->getEmail();

        $keyId = $this->customerResourceModel->getKeyId('Magento Customer ID', $storeId);
        $buildRequest[$keyId] = $customerId;

        $keyId = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
        $buildRequest[$keyId] = $objCustomer->getEmail() . "#" . $websiteId . "#" . $storeId;

        $getEmarsysMappedFields = $this->customerResourceModel->fetchMappedFields($storeId);
        foreach ($getEmarsysMappedFields as $mappedField) {
            if (isset($arrCustomer[$mappedField['attribute_code']]) && $mappedField['emarsys_contact_field'] != 0) {
                if (!is_null($mappedField['source_model'])) {
                    if ($mappedField['frontend_input'] == 'multiselect') {
                    } else {
                        $optionId = $arrCustomer[$mappedField['attribute_code']];
                        /**
                         * Get Mapped Emarsys OptionId
                         */
                        $emarsysOptionId = $optionId;//$this->fieldResourceModel->getEmarsysOptionId($optionId,$mappedField['emarsys_contact_field'],$websiteId);
                        if ($emarsysOptionId) {
                            $buildRequest[$mappedField['emarsys_contact_field']] = $emarsysOptionId;
                        }
                    }
                } else {
                    $buildRequest[$mappedField['emarsys_contact_field']] = $arrCustomer[$mappedField['attribute_code']];
                }
            }
        }
        $errorMsg = 0;
        if (count($buildRequest) > 0) {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Send Customer to Emarsys';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($buildRequest, JSON_PRETTY_PRINT);
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);
            $result = $this->createContactInEmarsys($buildRequest);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Create customer in Emarsys';
            $logsArray['action'] = 'Synced to Emarsys';
            if ($result['status'] == '200') {
                $logsArray['message_type'] = 'Success';
                $res = 'PUT ' . " contact/?create_if_not_exists=1 " . json_encode($result, JSON_PRETTY_PRINT);
                $logsArray['description'] = 'Created customer ' . $objCustomer->getEmail() . ' in Emarsys succcessfully '.$res;
                $this->dataHelper->syncSuccess($customerId, $websiteId, $storeId, $cron);
            } else {
                $this->dataHelper->syncFail($customerId, $websiteId, $storeId, $cron, 1);
                $logsArray['message_type'] = 'Error';
                $logsArray['description'] = $result['body']['replyText'];
                $errorMsg = 1;
            }
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);
        } else {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Create customer';
            $logsArray['action'] = 'Synced to Emarsys';
            $logsArray['message_type'] = 'error';
            $logsArray['description'] = 'Customer attribute mapping not working.';
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);
        }

        /**
         * Logs for Sync completed with / without Error
         */
        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        if ($errorMsg == 1) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Create customer in Emarsys with ERROR !!!';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Create customer in Emarsys';
        }
        $this->logsHelper->manualLogsUpdate($logsArray);
    }

    public function syncMultipleContacts($customer)
    {
        $logsArray['job_code'] = 'customerMultiple';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Customer is sync to Emarsys';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = '';
        $logId = $this->logsHelper->manualLogs($logsArray);

        $buildRequest = [];
        $buildRequest['key_id'] = '3';
        $buildRequest['contacts'] = $customer;

        $custEmailIds = [];
        foreach ($customer as $cust) {
            $custEmailIds[] = $cust['3'];
        }
        $errorMsg = 0;
        if (count($buildRequest) > 0) {
            $result = $this->createContactInEmarsys($buildRequest);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Create customer in Emarsys';
            $logsArray['action'] = 'Synced to Emarsys';
            if ($result['status'] == '200') {
                $customerCollFail = $this->custColl;
                $customerCollSuccess = $this->custColl;
                $errIds = array_keys($result['body']['data']['errors']);
                if (count($result['body']['data']['errors'])) {
                    $dataDataColl = $customerCollFail->addAttributeToFilter('email', ["in" => $errIds]);
                    $custData = $dataDataColl->getData();
                    foreach ($custData as $custIndividualData) {
                        $this->dataHelper->syncFail($custIndividualData['entity_id'], $custIndividualData['website_id'], $custIndividualData['store_id'], 1, 1);
                    }
                }
                if (count($result['body']['data']['ids'])) {
                    $successIds = array_diff($custEmailIds, $errIds);

                    $dataDataColl = $customerCollSuccess->addAttributeToFilter('email', ["in" => $successIds]);
                    $custData = $dataDataColl->getData();
                    foreach ($custData as $custIndividualData) {
                        $this->dataHelper->syncSuccess($custIndividualData['entity_id'], $custIndividualData['website_id'], $custIndividualData['store_id'], 1);
                    }
                }
            }
        }
    }

    /**
     * @param $arrCustomerData
     * @return array
     */
    public function createContactInEmarsys($arrCustomerData)
    {
        $response = $this->api->sendRequest('PUT', 'contact/?create_if_not_exists=1', $arrCustomerData);
        return $response;
    }
}
