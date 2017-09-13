<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Uploadinitialdb;

use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Sabre\DAV\Client;

/**
 * Class TestConnection for API credentials
 */
class Index extends \Magento\Backend\App\Action
{

    /**
     * @var Data
     */
    protected $emarsysHelper;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $config;

    /**
     * @var \Emarsys\Log\Helper\Logs
     */
    protected $logsHelper;

    /**
     * 
     * @param Data $emarsysHelper
     * @param \Magento\Customer\Model\CustomerFactory $customer
     * @param \Magento\Backend\App\Action\Context $context
     * @param DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param \Emarsys\Log\Helper\Logs $logsHelper
     * @param \Magento\Config\Model\ResourceModel\Config $config
     */
    public function __construct(
        Data $emarsysHelper,
        \Magento\Customer\Model\CustomerFactory $customer,
        \Magento\Backend\App\Action\Context $context,
        DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Emarsys\Log\Helper\Logs $logsHelper,
        \Magento\Config\Model\ResourceModel\Config $config
    ) {
    
        $this->emarsysHelper = $emarsysHelper;
        $this->customer = $customer;
        $this->logsHelper = $logsHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->config = $config;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $data['fromDate'] = '';
        $data['toDate'] = '';
        $websiteIdArr = [];
        if ( !isset($data['website']) ) // if the default configuration is set then the website id won't come
        {
            foreach($this->storeManager->getWebsites() as $websiteData)
            {
                $data['website'] = $websiteData->getWebsiteId();
                $this->uploadInitialData($data);
            }
            $scopeType = 'default';
            $websiteId = 0;
        }
        else
        {
            $this->uploadInitialData($data);
            $scopeType = 'websites';
            $websiteId = $data['website'];
        }
        $initialLoad     = $data['initial_load'];
        $attribute       = $data['attribute'];
        $attributeValue  = $data['attributevalue'];
        $this->config->saveConfig('contacts_synchronization/initial_db_load/initial_db_load', $initialLoad, $scopeType, $websiteId);
        $this->config->saveConfig('contacts_synchronization/initial_db_load/attribute', $attribute, $scopeType, $websiteId);
        $this->config->saveConfig('contacts_synchronization/initial_db_load/attribute_value', $attributeValue, $scopeType, $websiteId);
    }
    
    public function uploadInitialData($data)
    {
        $scope = 'websites';
        $websiteId = $data['website'];
        $scopeId = $this->emarsysHelper->getFirstStoreIdOfWebsite($websiteId);
        $storeCode = $this->storeManager->getStore($scopeId)->getCode();
        $websiteCode = $this->storeManager->getStore($scopeId)->getWebsite()->getCode();
        $data['storeId'] = $scopeId;

        $data['fromDate'] = '';
        $data['toDate'] = '';

        $logsArray['job_code'] = 'initialdbload';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Initial DB data initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $scopeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray,1);

        //get these values from db

        $webDavUrl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_url', $scope, $websiteId);


        $webDavUser = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_user', $scope, $websiteId);


        $webDavPass = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_password', $scope, $websiteId);

        $errorCount = 0;
        if ($webDavUrl != '' && $webDavUser != '' && $webDavPass != '') {
            $errorStatus = 0;
        } else {
            $errorStatus = 1;
        }

        $notificationMessage = '';
        $subscriberMessage = '';
        $initialLoad = $data['initial_load'];
        $attribute = $data['attribute'];
        $attributeValue = $data['attributevalue'];

        if ($errorStatus != 1) {
            $settings = [
                'baseUri' => $webDavUrl,
                'userName' => $webDavUser,
                'password' => $webDavPass,
                'proxy' => '',
            ];
            $client = new Client($settings);
            $response = $client->request('GET');
            if ($response['statusCode'] == '200' || $response['statusCode'] == '403') {
                $website = $this->getRequest()->getParam('website');
                $scopeType = 'websites';
                $defaultScopeType = 'default';
                $defaultScopeId = '0';
                if ($websiteId == '') {
                    $scopeType = 'default';
                    $websiteId = 0;
                }

                // Upload Customer CSV first
                $customervalues = [];
                $customerData = $this->customer->create();

                $optInStatus = $data['initial_load'];


                if (isset($scopeId)) {
                    $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();
                    $data['website'] = $websiteId;
                    if (isset($data['fromDate']) && $data['fromDate'] != '' && isset($data['toDate']) && $data['toDate'] != '') {
                        $data['fromDate'] = date('Y-m-d H:i:s', strtotime($data['fromDate']));
                        $data['toDate'] = date('Y-m-d H:i:s', strtotime($data['toDate']));
                    }
                    if ($optInStatus == 'attribute') {
                        $subscribedStatus = $data['attributevalue'];

                        $data['subscribeStatus'] = $subscribedStatus;
                        $customervalues = $this->customerResourceModel->getCustomerCollection($data);
                    } else {
                        $customervalues = $this->customerResourceModel->getCustomerCollection($data);
                    }
                }
                $mappedAttributes = $this->customerResourceModel->getMappedCustomerAttribute($scopeId);
                $emarsysFieldNames = [];

                if (isset($mappedAttributes) && count($mappedAttributes) != '') {
                    $mappingField = 0;
                    foreach ($mappedAttributes as $mapAttribute) {
                        $emarsysFieldId = trim($mapAttribute['emarsys_contact_field']);
                        if (!empty($emarsysFieldId)) {
                            $mappingField = 1;
                        }
                        $magentoFieldIds[] = $mapAttribute['magento_attribute_id'];
                        $emarsysFieldName = $this->customerResourceModel->getEmarsysFieldName($scopeId, $emarsysFieldId);
                        $emarsysFieldNames[] = $emarsysFieldName;
                    }
                    if ($mappingField == 1) {
                        if (!in_array('Email', $emarsysFieldNames)) {
                            $emarsysFieldNames[] = 'Email';
                        }
                        $emarsysFieldNames[] = 'Magento Customer ID';
                        $emarsysFieldNames[] = 'Magento Customer Unique ID';

                        if ($optInStatus != '') {
                            $emarsysFieldNames[] = 'Opt-In';
                        }

                        $heading = $emarsysFieldNames;
                        $outputFile = "customers_". $this->date->date('YmdHis', time())."_".$websiteCode.".csv";
                        $handle = fopen($outputFile, 'w');
                        fputcsv($handle, $heading);
                        $magentoFieldCodes = [];
                        foreach ($magentoFieldIds as $field) {
                            $attData = $this->customerResourceModel->getAttributeCodeById($field);
                            if($attData['attribute_code']){
                            $magentoFieldCodes[$attData['attribute_code']] = $attData['attribute_code'];
                            }
                        }
                        $allAttsUsed = [];
                        foreach ($customervalues as $value) {
                            $values = [];
                            $value = array_replace(array_flip($magentoFieldCodes), $value);
                            foreach ($value as $key => $cusData) {
                                $attData = $this->customerResourceModel->getAttributeIdByCode($key);

                                if (in_array($attData['attribute_id'], $magentoFieldIds)) {
                                    $allAttsUsed[] = $key;
                                    if (isset($cusData)) {
                                        $values[] = $cusData;
                                    } else {
                                        $values[] = '';
                                    }
                                }
                            }

                            if (!in_array($value['email'], $values)) {
                                $values[] = $value['email'];
                            }
                            $values[] = $value['entity_id'];
                            $values[] = $value['email'] . "#" . $websiteId . "#" . $value['store_id'];

                            if ($optInStatus == 'true') {
                                $values[] = '1';
                            } elseif ($optInStatus == 'empty') {
                                $values[] = 0;
                            } elseif ($optInStatus == 'attribute') {
                                $values[] = '1';
                            }
                            if (isset($value['default_billing']) && $value['default_billing'] != null) {
                                $magentoAddFields = [];
                                $customerBillingAddress = $this->customerResourceModel->getCustPriBillAddress($value['entity_id']);
                                if ($customerBillingAddress) {
                                    foreach ($customerBillingAddress->getData() as $key => $dataValue) {
                                        $attData = $this->customerResourceModel->getAttributeIdByCode($key);
                                        if (!is_array($dataValue) && in_array($attData['attribute_id'], $magentoFieldIds)) {
                                            $magentoAddFields[$key] = $dataValue;
                                        }
                                    }
                                }
                                $magentoFlipFields = array_replace(array_flip($magentoFieldCodes), $magentoAddFields);
                                foreach ($magentoFlipFields as $key => $cusData) {
                                    $attData = $this->customerResourceModel->getAttributeIdByCode($key);
                                    if (in_array($attData['attribute_id'], $magentoFieldIds)) {
                                        if (isset($magentoAddFields[$key])) {
                                            $attKey = array_search($key, $allAttsUsed);
                                            //echo('Value:'.$magentoAddFields[$key]);
                                            if (empty($magentoAddFields[$key])) {
                                                $values[] = '';
                                            } else {
                                                $values[] = $magentoAddFields[$key];
                                            }
                                        }
                                    }
                                }
                            }

                            if (isset($value['default_shipping']) && $value['default_shipping'] != null) {
                                $magentoAddFields = [];
                                $customerBillingAddress = $this->customerResourceModel->getCustPriShipAddress($value['entity_id']);
                                if ($customerBillingAddress) {
                                    foreach ($customerBillingAddress->getData() as $key => $dataValue) {
                                        $attData = $this->customerResourceModel->getAttributeIdByCode($key);
                                        if (!is_array($dataValue) && in_array($attData['attribute_id'], $magentoFieldIds)) {
                                            $magentoAddFields[$key] = $dataValue;
                                        }
                                    }
                                }
                                $magentoFlipFields = array_replace(array_flip($magentoFieldCodes), $magentoAddFields);
                                foreach ($magentoFlipFields as $key => $cusData) {
                                    $attData = $this->customerResourceModel->getAttributeIdByCode($key);
                                    if (in_array($attData['attribute_id'], $magentoFieldIds)) {
                                        if (isset($magentoAddFields[$key])) {
                                            $attKey = array_search($key, $allAttsUsed);
                                            if (empty($magentoAddFields[$key])) {
                                                $values[] = '';
                                            } else {
                                                $values[] = $magentoAddFields[$key];
                                            }
                                        }
                                    }
                                }
                            }

                            fputcsv($handle, $values);
                        }
                        $file = $outputFile;
                        $filePath = BP . "/" . $outputFile;
                        $fileOpen = fopen($filePath, "r");
                        $response = $client->request('PUT', $file, $fileOpen);
                        unlink($outputFile);


                        if ($response['statusCode'] == '201') {
                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'Customer file uploaded to server successfully';
                            $logsArray['description'] = $response['headers']['location']['0'];
                            $logsArray['action'] = 'synced to emarsys';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'sync';
                            $this->logsHelper->logs($logsArray);
                            $errorCount = 0;
                            $notificationMessage = 'File uploaded to server successfully';
                        } else {
                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'Failed to upload file on server';
                            $logsArray['description'] = 'Failed to upload file on server';
                            $logsArray['action'] = 'synced to emarsys';
                            $logsArray['message_type'] = 'Error';
                            $logsArray['log_action'] = 'sync';
                            $this->logsHelper->logs($logsArray);
                            $errorCount = 1;
                            $notificationMessage = 'Failed to upload file server  !!!';
                        }
                    } else {
                        $logsArray['id'] = $logId;
                        $logsArray['emarsys_info'] = 'Attributes are not mapped';
                        $logsArray['description'] = 'Failed to upload file on server. Attributes are not mapped';
                        $logsArray['action'] = 'synced to emarsys';
                        $logsArray['message_type'] = 'Error';
                        $logsArray['log_action'] = 'sync';
                        $this->logsHelper->logs($logsArray);
                        $errorCount = 1;
                        $notificationMessage = 'Attributes are not mapped !!!';
                    }
                } else {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Attributes are not mapped';
                    $logsArray['description'] = 'Failed to upload file on server. Attributes are not mapped';
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 1;
                    $notificationMessage = 'Attributes are not mapped !!!';
                }


                // Upload subscriber CSV file to WebDAV server
                $websiteStoreIds = [];
                foreach($this->storeManager->getStores() as $storeData)
                {
                    if ($data['website'] == $storeData->getWebsiteId())
                    {
                        $websiteStoreIds[] = $storeData->getStoreId();
                    }
  
                }
                $customervalues = [];

                $optInStatus = $data['initial_load'];


                if (isset($scopeId)) {
                    if ($optInStatus == 'attribute') {
                        $subscribedStatus = $data['attributevalue'];

                        $data['subscribeStatus'] = $subscribedStatus;
                        $customervalues = $this->customerResourceModel->getSubscribedCustomerCollection($data,implode(',',$websiteStoreIds),1);
                    } else {
                        $customervalues = $this->customerResourceModel->getSubscribedCustomerCollection($data,implode(',',$websiteStoreIds),1);
                    }
                }
                $emarsysFieldNames = ['Email', 'Magento Subscriber ID', 'Magento Customer Unique ID'];
                if ($optInStatus != '') {
                    $emarsysFieldNames[] = 'Opt-In';
                }

                $heading = $emarsysFieldNames;
                $outputFile = "subscribers_" . $this->date->date('YmdHis', time()) . "_" . $storeCode . ".csv";
                $handle = fopen($outputFile, 'w');
                fputcsv($handle, $heading);
                foreach ($customervalues as $value) {
                    $values = [];
                    $values[] = $value['subscriber_email'];
                    $values[] = $value['subscriber_id'];
                    $values[] = $value['subscriber_email'] . "#" . $websiteId . "#" . $value['store_id'];
                    if ($optInStatus == 'true') {
                        $values[] = '1';
                    } elseif ($optInStatus == 'empty') {
                        $values[] = 0;
                    } elseif ($optInStatus == 'attribute') {
                        $values[] = '1';
                    }
                    fputcsv($handle, $values);
                }

                $file = $outputFile;
                $filePath = BP . "/" . $outputFile;
                $fileOpen = fopen($filePath, "r");
                $response = $client->request('PUT', $file, $fileOpen);
                unlink($outputFile);

                if ($response['statusCode'] == '201') {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Subscriber file uploaded to server successfully';
                    $logsArray['description'] = $response['headers']['location']['0'];
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 0;
                    $subscriberMessage = 'File uploaded to server successfully';
                } else {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Failed to upload file on server';
                    $logsArray['description'] = 'Failed to upload file on server';
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 1;
                    $subscriberMessage = 'Failed to upload file on server !!!';
                }
            } else {
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Failed to Login with WebDav Server.';
                $logsArray['description'] = 'Failed to Login with WebDav Server. Please check your settings and try again';
                $logsArray['action'] = 'synced to emarsys';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->logs($logsArray);
                $errorCount = 1;
                $this->messageManager->addError("Failed to Login with WebDav Server. Please check your settings and try again !!!");
            }
        } else {
            $logsArray['id'] = $logId;
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Invalid WebDAV credentials.';
            $this->logsHelper->manualLogsUpdate($logsArray);
            $errorCount = 1;
            $notificationMessage = 'Invalid WebDav credentials. Please check your setting.';
        }

        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        if ($errorCount == 1) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Error in Uploading files. Please check.';
            $errorMessage = $notificationMessage . " " . $subscriberMessage;
            $this->messageManager->addError($errorMessage);
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Initial DB data completed';
            $this->messageManager->addSuccess('File uploaded to server successfully !!!');
        }
        $this->logsHelper->manualLogsUpdate($logsArray);
    }
}
