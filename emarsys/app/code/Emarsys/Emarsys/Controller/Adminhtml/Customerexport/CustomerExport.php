<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Customerexport;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Sabre\DAV\Client;


/**
 * Class Index
 */
class CustomerExport extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $customer;
    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $customerResourceModel;
    /**
     * @var
     */
    protected $messageManager;
    
    /**
     *
     * @var \Magento\Framework\Stdlib\DateTime\Timezone 
     */
    protected $timezone;
    
    /**
     *
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface 
     */
    protected $timezoneInterface;

    /**
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Customer\Model\CustomerFactory $customer
     * @param DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Log\Helper\Logs $logsHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Customer\Model\CustomerFactory $customer,
        DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Log\Helper\Logs $logsHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->customer = $customer;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->request = $request;
        $this->timezone = $timezone;
        $this->timezoneInterface = $timezoneInterface;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $data = $this->request->getParams();
        $scope = 'websites';
        $scopeId = $data['storeId'];
        $resultRedirect = $this->resultRedirectFactory->create();

        if (isset($data['fromDate']) && $data['fromDate'] != '') {
            $toTimezone = $this->timezone->getDefaultTimezone();
            $fromDate = $this->timezone->date($data['fromDate'])
                ->setTimezone(new \DateTimeZone($toTimezone))
                ->format('Y-m-d H:i:s');
            $magentoTime = $this->date->date('Y-m-d H:i:s');
            $currentTime = new \DateTime($magentoTime);
            $currentTime->format('Y-m-d H:i:s');
            $datetime2 = new \DateTime($fromDate);
            $interval = $currentTime->diff($datetime2);
            if ($interval->y > 2 || ($interval->y == 2 && $interval->m >= 1) || ($interval->y == 2 && $interval->d >= 1)) {
                $this->messageManager->addError("The timeframe cannot be more than 2 years");
                $url = $this->getUrl("emarsys_emarsys/customerexport/index/store/$scopeId");
                return $resultRedirect->setPath($url);
            }
        }

        $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();
        $storeCode = $this->storeManager->getStore($scopeId)->getWebsite()->getCode();

        $logsArray['job_code'] = 'customer';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'bulk customer export started';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $scopeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray);

        $webDavUrl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_url', $scope, $websiteId);
        if ($webDavUrl == '' && $websiteId == 1) {
            $webDavUrl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_url');
        }

        $webDavUser = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_user', $scope, $websiteId);
        if ($webDavUser == '' && $websiteId == 1) {
            $webDavUser = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_user');
        }

        $webDavPass = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_password', $scope, $websiteId);
        if ($webDavPass == '' && $websiteId == 1) {
            $webDavPass = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_password');
        }

        if ($webDavUrl != '' && $webDavUser != '' && $webDavPass != '') {
            $errorStatus = 0;
        } else {
            $errorStatus = 1;
        }

        if ($errorStatus != 1) {

            $settings = array(
                'baseUri' => $webDavUrl,
                'userName' => $webDavUser,
                'password' => $webDavPass,
                'proxy' => '',
            );

            $client = new Client($settings);
            $response = $client->request('GET');
            if ($response['statusCode'] == '200' || $response['statusCode'] == '403') {

                $customervalues = array();
                $customerData = $this->customer->create();

                $optInStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/initial_db_load', $scope, $websiteId);
                if ($optInStatus == '' && $websiteId == 1) {
                    $optInStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/initial_db_load');
                }

                if (isset($scopeId)) {
                    $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();
                    $data['website'] = $websiteId;
                    if (isset($data['fromDate']) && $data['fromDate'] != '' && isset($data['toDate']) && $data['toDate'] != '') {
                        $data['fromDate'] = date('Y-m-d H:i:s', strtotime($data['fromDate']));
                        $data['toDate'] = date('Y-m-d H:i:s', strtotime($data['toDate']));
                    }
                    if ($optInStatus == 'attribute') {
                        $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value', $scope, $websiteId);
                        if ($subscribedStatus == '' && $websiteId == 1) {
                            $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value');
                        }
                        $data['subscribeStatus'] = $subscribedStatus;
                        $customervalues = $this->customerResourceModel->getCustomerCollection($data);
                    } else {
                        $customervalues = $this->customerResourceModel->getCustomerCollection($data);
                    }
                }
                $mappedAttributes = $this->customerResourceModel->getMappedCustomerAttribute($scopeId);
                $emarsysFieldNames = array();
                

                if (isset($mappedAttributes) && count($mappedAttributes) != '') {    
                $headers      = array();
                $headerIndex  = array();
                $indexCount   = 0;
                foreach($mappedAttributes as $att)
                {
                    if ($att['emarsys_contact_field'] == NULL)
                        continue;
                    $emarsysField = $this->customerResourceModel->getEmarsysFieldNameContact($att,$scopeId);
                    $headers["$att[magento_custom_attribute_id]"] = $emarsysField['name'];
                    $headerIndex[$indexCount] = $att['magento_custom_attribute_id'];;
                    $indexCount++;
                }
                $headers['magento_customer_id']         = 'Magento Customer ID';
                $headerIndex[$indexCount] = 'magento_customer_id';
                $headers['magento_customer_unique_id']  = 'Magento Customer Unique ID';
                $indexCount = $indexCount+1;
                $headerIndex[$indexCount] = 'magento_customer_unique_id';
                $headers['opt_in'] = 'Opt-In';
                $indexCount = $indexCount+1;
                $headerIndex[$indexCount] = 'opt_in';
                $headers['customer_email'] = 'Customer Email';
                $indexCount = $indexCount+1;
                $headerIndex[$indexCount] = 'customer_email';
                $localFilePath = BP . "/var";
                $outputFile = "customers_" . $this->date->date('YmdHis', time()) . "_" . $storeCode . ".csv";
                $filePath = $localFilePath . "/" . $outputFile;
                $handle = fopen($filePath, 'w');
                fputcsv($handle, $headers);
                $customerCollection = $this->customerResourceModel->getCustomerCollection($data);
                $customerValues = array();
                foreach($customerCollection as $customerData)
                {
                    $customerLoad    = $this->customer->create()->load($customerData['entity_id']);
                    //die(print_r($customerLoad->getData()));
                    $primaryBilling  = $customerLoad->getPrimaryBillingAddress();
                    $primaryShipping = $customerLoad->getPrimaryshippingAddress();
                    foreach($headers as $key=>$value)
                    {
                        $attributeCode = $this->customerResourceModel->getMagentoAttributeCode($key,$scopeId); //using the custom magento id from the emarsys_magento_customer_attributes table
                        //code for the custom defined attributes in the array starts
                        if ($value == "Opt-In")
                        {
                            if ($optInStatus == 'true') {
                                $index = array_search($key,$headerIndex);
                                $customerValues[$index] = 1;
                            } else if ($optInStatus == 'empty') {
                                $index = array_search($key,$headerIndex);
                                $customerValues[$index] = 0;
                            } else if ($optInStatus == 'attribute') {
                                $index = array_search($key,$headerIndex);
                                $customerValues[$index] = 1;
                            }
                            else
                            {
                                $index = array_search($key,$headerIndex);
                                $customerValues[$index] = '';
                            }
                        }
                        else  if ($value == "Magento Customer ID")
                        {
                            $index = array_search($key,$headerIndex);
                            $customerValues[$index] = $customerLoad->getId();
                        }
                        else if ($value == "Magento Customer Unique ID")
                        {
                            
                            $index = array_search($key,$headerIndex);
                            $customerValues[$index] = $customerLoad->getEmail()."#".$customerLoad->getWebsiteId()."#".$customerLoad->getStoreId();
                        } 
                        else if ($value == "Customer Email")
                        {
                            $index = array_search($key,$headerIndex);
                            $customerValues[$index] = $customerLoad->getEmail();
                        }
                        //code for the custom defined attributes ends here
                        else if ($attributeCode['entity_type_id'] == 1)
                        {
                            $index = array_search($key,$headerIndex);
                            $customerValues[$index] = $customerLoad->getData($attributeCode['attribute_code']);
                        }
                        else if ($attributeCode['entity_type_id'] == 2)
                        {
                            $index = array_search($key,$headerIndex);
                            if (isset($customerData['default_shipping']) && $customerData['default_shipping'] != NULL && $customerData['default_shipping'] != 0)
                            {
                                if ($primaryShipping)
                                {
                                    $customerValues[$index] = $primaryShipping->getData($attributeCode['attribute_code']);                                    
                                }
                                else
                                {
                                    $customerValues[$index] = '';
                                }
                            }
                            else if (isset($customerData['default_billing']) && $customerData['default_billing'] != NULL && $customerData['default_billing'] != NULL)
                            {
                                if($primaryShipping)
                                {
                                    $customerValues[$index] = $primaryBilling->getData($attributeCode['attribute_code']);                                    
                                }
                                else
                                {
                                    $customerValues[$index] = '';
                                }

                            }
                        }
                    }
                    fputcsv($handle, $customerValues);
                    $customerValues  = array();
                }
                $file = $outputFile;
                $fileOpen = fopen($filePath, "r");
                $response = $client->request('PUT', $file, $fileOpen);
                unlink($filePath);
                $errorCount = 0;
                if ($response['statusCode'] == '201') {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'File uploaded to server successfully';
                    $logsArray['description'] = $response['headers']['location']['0'];
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 0;
                    $this->messageManager->addSuccess("File uploaded to server successfully !!!");
                } else {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Failed to upload file on server';
                    $logsArray['description'] = 'Failed to upload file on server';
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 1;
                    $this->messageManager->addError("Failed to upload file on server !!!");
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
                    $this->messageManager->addError("Attributes are not mapped !!!");
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
            $logsArray['emarsys_info'] = 'Invalid credentials';
            $logsArray['description'] = 'Invalid credential. Please check your settings and try again';
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);
            $errorCount = 1;

            $this->messageManager->addError("Invalid credential. Please check your settings and try again !!!");
        }

        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        if ($errorCount == 1) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Customer export have an error. Please check';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Customer export completed';
        }
        $this->logsHelper->manualLogsUpdate($logsArray);
        $url = $this->getUrl("emarsys_emarsys/customerexport/index/store/$scopeId");
        return $resultRedirect->setPath($url);
    }
}
