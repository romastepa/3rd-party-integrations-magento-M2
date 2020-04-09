<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\Api;

use Magento\{
    Customer\Model\Address,
    Customer\Model\Customer,
    Customer\Model\CustomerFactory,
    Customer\Model\ResourceModel\Customer\Collection as CustomerCollection,
    Framework\Stdlib\DateTime\DateTime,
    Framework\Message\ManagerInterface as MessageManagerInterface,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Helper\Data as EmarsysHelperData,
    Helper\Logs,
    Helper\Cron as EmarsysCronHelper,
    Helper\Country as EmarsysCountryHelper,
    Model\QueueFactory,
    Model\AsyncFactory,
    Model\ResourceModel\CustomerFactory as CustomerResourceModel,
    Logger\Logger as EmarsysLogger
};

class Contact
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerResourceModel
     */
    protected $customerResourceModel;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var EmarsysHelperData
     */
    protected $emarsysHelper;

    /**
     * @var CustomerCollection
     */
    protected $custColl;

    /**
     * @var EmarsysCountryHelper
     */
    protected $emarsysCountryHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var QueueFactory
     */
    protected $queueModel;

    /**
     * @var AsyncFactory
     */
    protected $asyncModel;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var Subscriber
     */
    protected $subscriberApi;

    /**
     * @var EmarsysLogger
     */
    protected $emarsysLogger;

    /**
     * @var array
     */
    protected $mappedCustomerAttribute = [];

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var int
     */
    protected $websiteId;

    /**
     * @var string
     */
    protected $exportMode;

    /**
     * @var int
     */
    protected $emailKey;

    /**
     * @var int
     */
    protected $customerIdKey;

    /**
     * Contact constructor.
     *
     * @param Api $api
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param CustomerResourceModel $customerResourceModel
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param EmarsysHelperData $emarsysHelper
     * @param CustomerCollection $customerColl
     * @param EmarsysCountryHelper $emarsysCountryHelper
     * @param StoreManagerInterface $storeManager
     * @param QueueFactory $queueModel
     * @param AsyncFactory $asyncModel
     * @param MessageManagerInterface $messageManager
     * @param EmarsysCronHelper $cronHelper
     * @param Subscriber $subscriberApi
     * @param EmarsysLogger $emarsysLogger
     */
    public function __construct(
        Api $api,
        Customer $customer,
        CustomerFactory $customerFactory,
        CustomerResourceModel $customerResourceModel,
        DateTime $date,
        Logs $logsHelper,
        EmarsysHelperData $emarsysHelper,
        CustomerCollection $customerColl,
        EmarsysCountryHelper $emarsysCountryHelper,
        StoreManagerInterface $storeManager,
        QueueFactory $queueModel,
        AsyncFactory $asyncModel,
        MessageManagerInterface $messageManager,
        EmarsysCronHelper $cronHelper,
        Subscriber $subscriberApi,
        EmarsysLogger $emarsysLogger
    ) {
        $this->api = $api;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->date = $date;
        $this->logsHelper = $logsHelper;
        $this->emarsysHelper = $emarsysHelper;
        $this->custColl = $customerColl;
        $this->emarsysCountryHelper = $emarsysCountryHelper;
        $this->storeManager = $storeManager;
        $this->queueModel = $queueModel;
        $this->asyncModel = $asyncModel;
        $this->messageManager = $messageManager;
        $this->cronHelper = $cronHelper;
        $this->subscriberApi = $subscriberApi;
        $this->emarsysLogger = $emarsysLogger;
    }

    /**
     * @param Customer $customer
     * @param $websiteId
     * @param $storeId
     * @param int $cron
     * @param null|Address $customerAddress
     * @return bool
     * @throws \Exception
     */
    public function syncContact($customer, $websiteId, $storeId, $cron = 0, $customerAddress = null)
    {
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

        $store = $this->storeManager->getStore($storeId);
        $sId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);

        if (!($customer instanceof Customer)) {
            $customer = $this->customer->load($customer->getId());
        }

        $buildRequest = [];
        $customerResourceModel = $this->customerResourceModel->create();
        $emailKey = $customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_EMAIL, $sId);
        $buildRequest['key_id'] = $emailKey;
        if ($emailKey && $customer->getEmail()) {
            $buildRequest[$emailKey] = $customer->getEmail();
        }

        $customerIdKey = $customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_ID, $sId);
        if ($customerIdKey && $customer->getId()) {
            $buildRequest[$customerIdKey] = $customer->getId();
        }

        $errorMsg = 0;
        $getEmarsysMappedFields = $customerResourceModel->fetchMappedFields($sId);

        unset($customerResourceModel);

        if (empty($getEmarsysMappedFields)) {
            $errorMsg = 1;
        }

        foreach ($getEmarsysMappedFields as $mField) {
            if ($customer->getData($mField['attribute_code']) && $mField['emarsys_contact_field'] != 0) {
                if (!is_null($mField['source_model'])) {
                    if ($mField['frontend_input'] != 'multiselect') {
                        $optionId = $customer->getData($mField['attribute_code']);
                        /**
                         * Get Mapped Emarsys OptionId
                         */
                        $emarsysOptionId = $optionId;
                        if ($emarsysOptionId) {
                            $buildRequest[$mField['emarsys_contact_field']] = $emarsysOptionId;
                        }
                    }
                } else {
                    $buildRequest[$mField['emarsys_contact_field']] = $customer->getData($mField['attribute_code']);
                }
            }
        }

        //Fetch Customer's Mapped Address Attributes
        $customerMappedAddressAttributes = $this->getMappedCustomersAddressAttributes(
            $customer,
            $storeId,
            $customerAddress
        );
        foreach ($customerMappedAddressAttributes as $key => $value) {
            $buildRequest[$key] = $value;
        }

        if (count($buildRequest) > 0) {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Send Customer to Emarsys';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'PUT ' . Api::CONTACT_CREATE_IF_NOT_EXISTS
                . ' ' . \Zend_Json::encode($buildRequest);
            $logsArray['log_action'] = 'sync';
            if ($this->emarsysHelper->isAsyncEnabled()) {

                $this->asyncModel->create()
                    ->setWebsiteId($websiteId)
                    ->setEndpoint(Api::CONTACT_CREATE_IF_NOT_EXISTS)
                    ->setEmail($customer->getEmail())
                    ->setCustomerId($customer->getId())
                    ->setSubscriberId(null)
                    ->setRequestBody(\Zend_Json::encode($buildRequest))
                    ->save();

                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['emarsys_info'] = 'Added to Async queue';
                $logsArray['description'] = 'Added to Async queue';
                $this->logsHelper->manualLogs($logsArray);
                return true;
            }
            $this->logsHelper->manualLogs($logsArray);

            $result = $this->api->createContactInEmarsys($buildRequest);

            $logsArray['emarsys_info'] = 'Create customer in Emarsys';
            $logsArray['action'] = 'Synced to Emarsys';

            if ($result['status'] == '200') {
                $logsArray['message_type'] = 'Success';

                $confirmUrl = '';
                if ($customer->getConfirmation()) {
                    $confirmUrl = ' | Confirmation URL : '
                        . $store->getUrl(
                            'customer/account/confirm',
                            ['id' => $customer->getId(), 'key' => $customer->getConfirmation()]
                        );
                }

                $res = 'PUT ' . Api::CONTACT_CREATE_IF_NOT_EXISTS . ' ' . \Zend_Json::encode($result);
                $logsArray['description'] = 'Created customer ' . $customer->getEmail()
                    . ' in Emarsys succcessfully | ' . $res . ' | ' . $confirmUrl;
                $this->emarsysHelper->syncSuccess($customer->getId(), $websiteId, $storeId, $cron);
            } else {
                $this->emarsysHelper->syncFail($customer->getId(), $websiteId, $storeId, $cron, 1);
                $logsArray['message_type'] = 'Error';
                $logsArray['description'] = \Zend_Json::encode($result);
                $errorMsg = 1;
            }
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->manualLogs($logsArray);
        } else {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Create customer';
            $logsArray['action'] = 'Synced to Emarsys';
            $logsArray['message_type'] = 'error';
            $logsArray['description'] = 'Customer attribute mapping not working.';
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->manualLogs($logsArray);
        }

        /**
         * Logs for Sync completed with / without Error
         */
        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        if ($errorMsg == 1) {
            $logsArray['status'] = 'error';
            $logsArray['message_type'] = 'Error';
            $logsArray['emarsys_info'] = 'Error';
            $logsArray['description'] = 'ERROR on Customer creation';
            if (empty($getEmarsysMappedFields)) {
                $logsArray['description'] = 'ERROR on Customer creation. Mapping is empty.';
            }
        } else {
            $logsArray['status'] = 'success';
            $logsArray['message_type'] = 'Success';
            $logsArray['emarsys_info'] = 'Success';
            $logsArray['description'] = 'Created Customer in Emarsys';
        }
        $this->logsHelper->manualLogs($logsArray);

        return $errorMsg ? false : true;
    }

    /**
     * Fetch Customer's Mapped Address attributes values
     *
     * @param $customer
     * @param int $storeId
     * @param null|Address $customerAddress
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Json_Exception
     */
    public function getMappedCustomersAddressAttributes($customer, $storeId, $customerAddress = null)
    {
        $addressFields = [];
        $mappedAttributes = $this->getMappedCustomerAttribute($storeId);
        if (count($mappedAttributes)) {
            $primaryShipping = $customer->getPrimaryShippingAddress();
            if (($customerAddress && $customerAddress->getDefaultShipping()) || !$primaryShipping) {
                $primaryShipping = $customerAddress;
            } elseif ($customerAddress && $primaryShipping->getId() == $customerAddress->getId()) {
                $primaryShipping = $customerAddress;
            }
            if (!$primaryShipping) {
                $primaryShipping = current($customer->getAddresses());
            }

            $primaryBilling = $customer->getPrimaryBillingAddress();
            if (($customerAddress && $customerAddress->getDefaultBilling()) || !$primaryBilling) {
                $primaryBilling = $customerAddress;
            } elseif ($customerAddress && $primaryBilling->getId() == $customerAddress->getId()) {
                $primaryBilling = $customerAddress;
            }
            if (!$primaryBilling) {
                $primaryBilling = $primaryShipping;
            }

            $mappedCountries = $this->emarsysCountryHelper->getMapping($storeId);

            foreach ($mappedAttributes as $key => $attribute) {
                if (!$attribute['emarsys_contact_field']) {
                    continue;
                }
                $attCode = $this->customerResourceModel->create()->getMagentoAttributeCode(
                    $attribute['magento_custom_attribute_id'],
                    $storeId
                );
                if (!empty($attCode) && $attCode['entity_type_id'] == 2) { // If the field type is Address
                    $isShippingAttr = (strpos($attCode['attribute_code_custom'], 'default_shipping_') !== false)
                        ? true
                        : false;
                    $isBillingAttr = (strpos($attCode['attribute_code_custom'],
                            'default_billing_') !== false) ? true : false;
                    $attrValue = '';
                    if ($isShippingAttr && $primaryShipping) {
                        $attrValue = $primaryShipping->getData($attCode['attribute_code']);
                    } elseif ($isBillingAttr && $primaryBilling) {
                        $attrValue = $primaryBilling->getData($attCode['attribute_code']);
                    }
                    if ($attCode['attribute_code'] == 'country_id') {
                        $attrValue = isset($mappedCountries[$attrValue]) ? $mappedCountries[$attrValue] : '';
                    } elseif ($attCode['attribute_code'] == 'street') {
                        $attrValue = str_replace("\n", ', ', $attrValue);
                    }

                    $addressFields[$attribute['emarsys_contact_field']] = $attrValue;
                }
            }
        }

        return $addressFields;
    }

    /**
     * @param $objCustomer
     * @param $storeId
     * @param $emailKey
     * @param $customerIdKey
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Json_Exception
     */
    public function getCustomerPayload(
        $objCustomer,
        $storeId,
        $emailKey,
        $customerIdKey
    ) {
        $customerData = [];
        if ($emailKey && $objCustomer->getEmail()) {
            $customerData[$emailKey] = $objCustomer->getEmail();
        }

        if ($customerIdKey && $objCustomer->getId()) {
            $customerData[$customerIdKey] = $objCustomer->getId();
        }

        $getEmarsysMappedFields = $this->customerResourceModel->create()->fetchMappedFields($storeId);

        foreach ($getEmarsysMappedFields as $mField) {
            if ($objCustomer->getData($mField['attribute_code']) && $mField['emarsys_contact_field'] != 0) {
                if (!is_null($mField['source_model'])) {
                    if ($mField['frontend_input'] != 'multiselect') {
                        $optionId = $objCustomer->getData($mField['attribute_code']);
                        //Get Mapped Emarsys OptionId
                        if ($optionId) {
                            $customerData[$mField['emarsys_contact_field']] = $optionId;
                        }
                    }
                } else {
                    $customerData[$mField['emarsys_contact_field']] = $objCustomer->getData($mField['attribute_code']);
                }
            }
        }
        //Fetch Customer's Mapped Address Attributes
        $customerMappedAddressAttributes = $this->getMappedCustomersAddressAttributes($objCustomer, $storeId);
        foreach ($customerMappedAddressAttributes as $key => $value) {
            $customerData[$key] = $value;
        }

        return $customerData;
    }

    /**
     * @param $customerCollectionArray
     * @param $keyId
     * @return array
     */
    public function prepareCustomerPayload($customerCollectionArray, $keyId)
    {
        $buildRequest = [];
        if ($keyId) {
            $buildRequest['key_id'] = $keyId;
            $buildRequest['contacts'] = $customerCollectionArray;
        }

        return $buildRequest;
    }

    /**
     * @param $exportMode
     * @param $data
     * @param null $logId
     * @return bool
     * @throws \Exception
     */
    public function preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId = null)
    {
        $websiteId = $data['website'];

        if (isset($data['storeId'])) {
            $storeId = $data['storeId'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);
        }

        $sId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);

        $this->exportMode = $exportMode;
        $this->storeId = $storeId;
        $this->websiteId = $websiteId;

        $customerResourceModel = $this->customerResourceModel->create();
        $emailKey = $customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_EMAIL, $sId);
        $customerIdKey = $customerResourceModel->getKeyId(EmarsysHelperData::CUSTOMER_ID, $sId);
        $this->emailKey = $emailKey;
        $this->customerIdKey = $customerIdKey;

        $params = [
            'website' => $websiteId,
            'storeId' => $storeId,
            'fromDate' => $data['fromDate'],
            'toDate' => $data['toDate'],
        ];
        $success = false;
        $jobDetails = $this->cronHelper->getJobDetail($exportMode);

        //initial logs for customer export
        $logsArray['job_code'] = $jobDetails['job_code'];
        $logsArray['status'] = 'started';
        $logsArray['messages'] = $jobDetails['job_title'] . ' initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        if (!$logId) {
            $logId = $this->logsHelper->manualLogs($logsArray, 1);
        }
        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['log_action'] = 'sync';
        $logsArray['action'] = 'contact sync';

        //customer export starts
        $logsArray['emarsys_info'] = __('Customer Export Started');
        $logsArray['description'] = __('Customer Export Started for Store ID : %1', $storeId);
        $logsArray['message_type'] = 'Success';
        $this->logsHelper->manualLogs($logsArray);

        //check customer attributes are mapped
        $mappedAttributes = $this->getMappedCustomerAttribute($sId);
        if (count($mappedAttributes)) {
            $allCustomersPayload = [];
            if ($exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE) {
                $queueCollection = $this->queueModel->create()->getCollection();
                $queueCollection->addFieldToSelect('entity_id');
                $queueCollection->addFieldToFilter('entity_type_id', 1);

                //Prepare Customers Payload Array
                foreach ($queueCollection as $item) {
                    $allCustomersPayload[] = $this->getCustomerPayload(
                        $this->customerFactory->create()->load($item->getEntityId()),
                        $sId,
                        $emailKey,
                        $customerIdKey
                    );
                }

                $this->processBatch($allCustomersPayload, $logsArray);
            } else {
                $firstPageNumber = $currentPageNumber = isset($data['page']) ? $data['page'] : 1;

                $customerCollection = $this->customerResourceModel->create()->getCustomerCollection(
                    $params,
                    $storeId,
                    $currentPageNumber
                );
                $lastPageNumber = $customerCollection->getLastPageNumber();

                //Prepare Customers Payload Array
                while ($currentPageNumber <= $lastPageNumber) {
                    if ($currentPageNumber != $firstPageNumber) {
                        $customerCollection = $this->customerResourceModel->create()->getCustomerCollection(
                            $params,
                            $storeId,
                            $currentPageNumber
                        );
                    }
                    foreach ($customerCollection as $customerData) {
                        $allCustomersPayload[] = $this->getCustomerPayload(
                            $customerData,
                            $sId,
                            $emailKey,
                            $customerIdKey
                        );
                    }

                    $success = $this->processBatch($allCustomersPayload, $logsArray);

                    $logsArray['emarsys_info'] = __('Processing data for store %1', $storeId);
                    $logsArray['description'] = __('%1 of %2', $currentPageNumber, $lastPageNumber);
                    $logsArray['message_type'] = $success ? 'Success' : 'False';
                    $this->logsHelper->manualLogs($logsArray);
                    $currentPageNumber++;
                    unset($customerCollection);
                    unset($allCustomersPayload);
                }
            }
        } else {
            $logsArray['emarsys_info'] = 'Attributes are not mapped';
            $logsArray['description'] = 'Failed to sync contacts. Customer attributes are not mapped.';
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->manualLogs($logsArray);
            $this->messageManager->addErrorMessage("Attributes are not mapped for this store view !!!");
        }

        if ($success) {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Customer export completed';
        } else {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Customer export have an error. Please check';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogs($logsArray);

        return $success;
    }

    public function processBatch($allCustomersPayload, $logsArray)
    {
        if (empty($allCustomersPayload)) {
            //no Customers data found
            $logsArray['emarsys_info'] = 'No Customers Found.';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Error';
            $logsArray['description'] = __('No Customers for the store with store id %1.', $this->storeId);
            $this->logsHelper->manualLogs($logsArray);
            $this->messageManager->addErrorMessage(__('No Customers found for the store with store id %1.',
                $this->storeId));
            return false;
        }

        $emailKey = $this->emailKey;

        $buildRequest = $this->prepareCustomerPayload($allCustomersPayload, $emailKey);
        if (count($buildRequest) > 0) {
            $logsArray['emarsys_info'] = 'Send customers to Emarsys';
            $logsArray['action'] = 'Magento to Emarsys';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'PUT ' . Api::CONTACT_CREATE_IF_NOT_EXISTS
                . ' ' . \Zend_Json::encode($buildRequest);
            $this->logsHelper->manualLogs($logsArray);
            $this->emarsysLogger->info($logsArray['description']);

            //Send request to Emarsys with Customer's Data
            $this->api->setWebsiteId($this->websiteId);
            $result = $this->api->createContactInEmarsys($buildRequest);

            $logsArray['emarsys_info'] = 'Create customers in Emarsys';
            $logsArray['action'] = 'Synced to Emarsys';
            $res = 'PUT ' . Api::CONTACT_CREATE_IF_NOT_EXISTS . ' ' . \Zend_Json::encode($result);

            if ($result['status'] == '200') {
                $logsArray['message_type'] = 'Success';
                $logsArray['emarsys_info'] = __('Created customers in Emarsys succcessfully');
                $logsArray['description'] = "Created customers in Emarsys succcessfully " . $res;

                if ($this->exportMode == EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE) {
                    $custEmailIds = [];
                    foreach ($allCustomersPayload as $cust) {
                        if (isset($cust[$emailKey])) {
                            $custEmailIds[] = $cust[$emailKey];
                        }
                    }
                    $customeFail = $this->custColl;
                    $customerSuccess = $this->custColl;
                    $errIds = [];

                    if (isset($result['body']['data']['errors'])) {
                        $errIds = array_keys($result['body']['data']['errors']);
                        if (count($result['body']['data']['errors'])) {
                            $dataDataColl = $customeFail->addAttributeToFilter('email', ["in" => $errIds]);
                            $custData = $dataDataColl->getData();
                            foreach ($custData as $custIndividualData) {
                                $this->emarsysHelper->syncFail(
                                    $custIndividualData['entity_id'],
                                    $custIndividualData['website_id'],
                                    $custIndividualData['store_id'],
                                    1,
                                    1
                                );
                            }
                        }
                    }

                    if (count($result['body']['data']['ids'])) {
                        $successIds = array_diff($custEmailIds, $errIds);
                        $dataDataColl = $customerSuccess->addAttributeToFilter('email', ["in" => $successIds]);
                        $custData = $dataDataColl->getData();
                        foreach ($custData as $custIndividualData) {
                            $this->emarsysHelper->syncSuccess(
                                $custIndividualData['entity_id'],
                                $custIndividualData['website_id'],
                                $custIndividualData['store_id'],
                                1
                            );
                        }
                    }
                }
                $this->logsHelper->manualLogs($logsArray);
                $this->emarsysLogger->info($logsArray['description']);
            } else {
                //error response from emarsys
                $logsArray['emarsys_info'] = __('Error while customer export.');
                $logsArray['message_type'] = 'Error';
                $logsArray['description'] = $res;
                $this->messageManager->addErrorMessage(__(
                    'Customers export have an error. Please check emarsys logs for more details!!'
                ));
                $this->logsHelper->manualLogs($logsArray);
                $this->emarsysLogger->info($logsArray['description']);
                return false;
            }
        }

        return true;
    }

    /**
     * @param $exportMode
     * @param $data
     * @return bool|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncFullContactUsingApi($exportMode, $data)
    {
        $jobDetails = $this->cronHelper->getJobDetail($exportMode);

        $websiteId = $data['website'];

        $website = $this->storeManager->getWebsite($websiteId);

        if (!$this->emarsysHelper->isContactsSynchronizationEnable($websiteId)) {
            return;
        }

        if (isset($data['storeId'])) {
            $storeId = $data['storeId'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);
        }

        $fromDate = (isset($data['fromDate']) && !empty($data['fromDate'])) ? $data['fromDate'] : '';
        $toDate = (isset($data['toDate']) && !empty($data['toDate']))
            ? $data['toDate']
            : $this->date->date('Y-m-d') . ' 23:59:59';
        $page = (isset($data['page']) && !empty($data['page'])) ? $data['page'] : 1;

        $params = [
            'website' => $websiteId,
            'storeId' => $storeId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'page' => $page,
        ];
        $errorStatus = true;

        //initial logs for customer export
        $logsArray['job_code'] = $jobDetails['job_code'];
        $logsArray['status'] = 'started';
        $logsArray['messages'] = $jobDetails['job_title'] . ' initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray, 1);
        $logsArray['id'] = $logId;
        $logsArray['log_action'] = 'sync';
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['action'] = 'synced to emarsys';

        //check if emarsys enabled for the website
        if ($this->emarsysHelper->getEmarsysConnectionSetting($websiteId) &&
            $website->getConfig(EmarsysHelperData::XPATH_EMARSYS_ENABLE_CONTACT_FEED)
        ) {
            $errorStatus = $this->exportDataToApi($exportMode, $params, $logId);
        } else {
            //Emarsys is disabled for the store
            $logsArray['emarsys_info'] = __('Emarsys is disabled');
            $logsArray['description'] = __('Emarsys is disabled for the store');
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->manualLogs($logsArray);
        }

        if ($errorStatus) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Something went wrong, please check logs';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Contacts successfully synced';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogs($logsArray);

        return $errorStatus;
    }

    public function exportDataToApi($exportMode, $data, $logId)
    {
        $errorStatus = true;

        switch ($exportMode) {
            case EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE:
                $customerExportStatus = $this->preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId);
                $subscriberExportStatus = $this->subscriberApi->syncMultipleSubscriber($exportMode, $data, $logId);

                if ($subscriberExportStatus && $customerExportStatus) {
                    $errorStatus = false;
                }
                break;
            default:
                $customerExportStatus = $this->preparePayloadAndSyncMultipleContacts($exportMode, $data, $logId);
                if ($customerExportStatus) {
                    $errorStatus = false;
                }
                break;
        }

        return $errorStatus;
    }

    /**
     * @param $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getMappedCustomerAttribute($storeId)
    {
        if (!isset($this->mappedCustomerAttribute[$storeId])) {
            $this->mappedCustomerAttribute[$storeId] = $this->customerResourceModel->create()
                ->getMappedCustomerAttribute($storeId);
        }

        return $this->mappedCustomerAttribute[$storeId];
    }
}
