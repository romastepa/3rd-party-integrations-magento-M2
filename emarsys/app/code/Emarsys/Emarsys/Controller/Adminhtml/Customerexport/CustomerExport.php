<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
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
     * @param \Emarsys\Emarsys\Helper\Logs $logsHelper
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
        \Emarsys\Emarsys\Helper\Logs $logsHelper,
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
        set_time_limit(0);
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

        $webDavUrl = $this->customerResourceModel->getDataFromCoreConfig(
            'emarsys_settings/webdav_setting/webdav_url',
            $scope,
            $websiteId
        );
        $webDavUser = $this->customerResourceModel->getDataFromCoreConfig(
            'emarsys_settings/webdav_setting/webdav_user',
            $scope,
            $websiteId
        );
        $webDavPass = $this->customerResourceModel->getDataFromCoreConfig(
            'emarsys_settings/webdav_setting/webdav_password',
            $scope,
            $websiteId
        );

        if ($webDavUrl != '' && $webDavUser != '' && $webDavPass != '') {
            $errorStatus = 0;
        } else {
            $errorStatus = 1;
        }

        $settings = array(
            'baseUri' => $webDavUrl,
            'userName' => $webDavUser,
            'password' => $webDavPass,
            'proxy' => '',
        );

        if ($errorStatus != 1) {
            $client = new Client($settings);
            $response = $client->request('GET');
            if ($response['statusCode'] == '200' || $response['statusCode'] == '403') {
                if (isset($scopeId)) {
                    $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();
                    $data['website'] = $websiteId;
                    if (isset($data['fromDate']) && $data['fromDate'] != '' && isset($data['toDate']) && $data['toDate'] != '') {
                        $data['fromDate'] = date('Y-m-d H:i:s', strtotime($data['fromDate']));
                        $data['toDate'] = date('Y-m-d H:i:s', strtotime($data['toDate']));
                    }
                }
                $mappedAttributes = $this->customerResourceModel->getMappedCustomerAttribute($scopeId);
                $headers = array();
                if (isset($mappedAttributes) && count($mappedAttributes) != '') {
                    $headerIndex = array();
                    $indexCount = 0;
                    foreach ($mappedAttributes as $att) {
                        if ($att['emarsys_contact_field'] == NULL)
                            continue;
                        $emarsysField = $this->customerResourceModel->getEmarsysFieldNameContact($att, $scopeId);
                        $headers["$att[magento_custom_attribute_id]"] = $emarsysField['name'];
                        $headerIndex[$indexCount] = $att['magento_custom_attribute_id'];;
                        $indexCount++;
                    }
                }

                if (count($headers) > 0) {
                    $headers['magento_customer_id'] = 'Magento Customer ID';
                    $headerIndex[$indexCount] = 'magento_customer_id';
                    $headers['magento_customer_unique_id'] = 'Magento Customer Unique ID';
                    $indexCount = $indexCount + 1;
                    $headerIndex[$indexCount] = 'magento_customer_unique_id';
                    //$headers['opt_in'] = 'Opt-In';
                    $indexCount = $indexCount + 1;
                    $headerIndex[$indexCount] = 'opt_in';
                    $headers['customer_email'] = 'Customer Email';
                    $indexCount = $indexCount + 1;
                    $headerIndex[$indexCount] = 'customer_email';
                    $localFilePath = BP . "/var";
                    $outputFile = "customers_" . $this->date->date('YmdHis', time()) . "_" . $storeCode . ".csv";
                    $filePath = $localFilePath . "/" . $outputFile;
                    $handle = fopen($filePath, 'w');
                    fputcsv($handle, $headers);
                    $customerCollection = $this->customerResourceModel->getCustomerCollection($data);
                    $customerValues = array();

                    foreach ($customerCollection as $customerData) {
                        $customerLoad = $this->customer->create()->load($customerData['entity_id']);
                        $primaryBilling = $customerLoad->getPrimaryBillingAddress();
                        $primaryShipping = $customerLoad->getPrimaryshippingAddress();
                        foreach ($headers as $key => $value) {
                            $attributeCode = $this->customerResourceModel->getMagentoAttributeCode($key, $scopeId); //using the custom magento id from the emarsys_magento_customer_attributes table
                            //code for the custom defined attributes in the array starts

                            if ($value == "Magento Customer ID") {
                                $index = array_search($key, $headerIndex);
                                $customerValues[$index] = $customerLoad->getId();
                            } elseif ($value == "Magento Customer Unique ID") {
                                $index = array_search($key, $headerIndex);
                                $customerValues[$index] = $customerLoad->getEmail() . "#" . $customerLoad->getWebsiteId() . "#" . $customerLoad->getStoreId();
                            } elseif ($value == "Customer Email") {
                                $index = array_search($key, $headerIndex);
                                $customerValues[$index] = $customerLoad->getEmail();
                            } elseif ($attributeCode['entity_type_id'] == 1) {
                                //code for the custom defined attributes ends here
                                $index = array_search($key, $headerIndex);
                                $customerValues[$index] = $customerLoad->getData($attributeCode['attribute_code']);
                            } elseif ($attributeCode['entity_type_id'] == 2) {
                                $index = array_search($key, $headerIndex);
                                $attrVal = '';
                                if (isset($customerData['default_shipping']) && $customerData['default_shipping'] != NULL && $customerData['default_shipping'] != 0) {
                                    if ($primaryShipping) {
                                        $attrVal = $primaryShipping->getData($attributeCode['attribute_code']);
                                    }
                                } elseif (isset($customerData['default_billing']) && $customerData['default_billing'] != NULL && $customerData['default_billing'] != NULL) {
                                    if ($primaryShipping) {
                                        $attrVal = $primaryBilling->getData($attributeCode['attribute_code']);
                                    }
                                }
                                $customerValues[$index] = $attrVal;
                            }
                        }
                        fputcsv($handle, $customerValues);
                        $customerValues = array();
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
                        $logsArray['description'] = strip_tags($response['body']);
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
                    $this->messageManager->addError("Attributes are not mapped for this store view !!!");
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
