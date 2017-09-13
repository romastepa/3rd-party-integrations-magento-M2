<?php
/**
 *  * Mail Transport
 *  */
namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Model\ResourceModel\Customer as customerResourceModel;

class Transport extends \Zend_Mail_Transport_Sendmail implements \Magento\Framework\Mail\TransportInterface
{
    public function __construct(
        Data $dataHelper,
        customerResourceModel $customerResourceModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Mail\MessageInterface $message,
        $parameters = null
    ) {
    
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }

        parent::__construct($parameters);
        $this->dataHelper = $dataHelper;
        $this->date = $date;
        $this->customerResourceModel = $customerResourceModel;
        $this->_message = $message;
    }

    public function sendMessage()
    {


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $dataHelper = $objectManager->create('Emarsys\Emarsys\Helper\Data');
        $logsHelper = $objectManager->create('Emarsys\Log\Helper\Logs');
        $storeManagerInterface = $objectManager->create('\Magento\Store\Model\StoreManagerInterface');
        $store_id = $storeManagerInterface->getStore()->getId();
        $websiteId = $storeManagerInterface->getStore()->getWebsiteId();
        $logsArray['job_code'] = 'transactional_mail';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Transactional Email Started';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Automatic';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $store_id;

        $logId = $logsHelper->manualLogs($logsArray);
        try {
            if($this->dataHelper->isEmarsysEnabled($websiteId) =='false'){
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'Emarsys is not enabled. Email sent from Magento.';
                $logsArray['action'] = 'Mail Sent';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $logsArray['website_id'] = $websiteId;
                $logsHelper->logs($logsArray);
                parent::send($this->_message);
            }
            $emarsysPlaceholdersData = $this->_message->getEmarsysData()['emarsysPlaceholders'];
            $emarsysApiEventID = $this->_message->getEmarsysData()['emarsysEventId'];
            if ($emarsysPlaceholdersData == "" || $emarsysApiEventID == "") {
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'No Mapping Found for the Emarsys Event ID. Email sent from Magento.';
                $logsArray['action'] = 'Mail Sent';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $logsArray['website_id'] = $websiteId;
                $logsHelper->logs($logsArray);
                parent::send($this->_message);
            } else {
                $contactApi = $objectManager->create('Emarsys\Emarsys\Model\Api\Contact');
                $api = $objectManager->create('Emarsys\Emarsys\Model\Api\Api');
                $storeId = $storeManagerInterface->getStore()->getId();
                $storeCode = $storeManagerInterface->getStore()->getCode();
                $websiteId = $storeManagerInterface->getStore()->getWebsiteId();
                $api->setWebsiteId($websiteId);
                $scopeConfigInterface = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');

                $emarsysEnable = $dataHelper->getConfigValue('transaction_mail/transactionmail/enable_customer', 'websites', $websiteId);
                if ($emarsysEnable == '' && $websiteId == 1) {
                    $emarsysEnable = $dataHelper->getConfigValue('transaction_mail/transactionmail/enable_customer');
                }
                if (!$emarsysEnable) {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Transactional Mails';
                    $logsArray['description'] = 'Emarsys Transaction Email Disabled. Email sent from Magento.';
                    $logsArray['action'] = 'Mail Sent';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'True';
                    $logsArray['website_id'] = $websiteId;
                    $logsHelper->logs($logsArray);
                    parent::send($this->_message);
                } else {
                    $emarsysPlaceholdersData = $this->_message->getEmarsysData()['emarsysPlaceholders'];
                    $externalId = $this->_message->getRecipients()[0];
                    $buildRequest = [];

                    $keyField = $this->dataHelper->getContactUniqueField($websiteId);
                    if ($keyField == 'email') {
                        $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Email', $storeId);
                        $buildRequest[$buildRequest['key_id']] = $externalId;
                    } elseif ($keyField == 'magento_id') {
                        // check customer exists in magento or not
                        $customerId = $this->customerResourceModel->checkCustomerExistsInMagento($externalId, $websiteId,$storeId);
                        $data = [
                            'email' => $externalId,
                            'storeId' => $storeId
                        ];
                        $subscribeId = $this->customerResourceModel->getSubscribeIdFromEmail($data);
                        //if customer exists
                        if (!empty($customerId)) {
                            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer ID', $storeId);
                            $buildRequest[$buildRequest['key_id']] = $customerId;//$customerId;
                        } elseif (!empty($subscribeId)) {
                            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Subscriber ID', $storeId);
                            $buildRequest[$buildRequest['key_id']] = $subscribeId;//$subscribeId;
                        } else {
                            $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Email', $storeId);
                            $buildRequest[$buildRequest['key_id']] = $externalId;
                        }
                    } elseif ($keyField == 'unique_id') {
                        $buildRequest['key_id'] = $this->customerResourceModel->getKeyId('Magento Customer Unique ID', $storeId);
                        $buildRequest[$buildRequest['key_id']] = $externalId . "#" . $websiteId . "#" . $storeId;
                    }

                    $response = $api->sendRequest('PUT', 'contact/?create_if_not_exists=1', $buildRequest);

                    if (($response['status'] == 200) || ($response['status'] == 400 && $response['body']['replyCode'] == 2009)) {
                        $arrCustomerData = [
                            "key_id" => $buildRequest['key_id'],
                            "external_id" => $buildRequest[$buildRequest['key_id']],
                            "data" => $emarsysPlaceholdersData
                        ];
                        $req = 'POST ' . " event/$emarsysApiEventID/trigger: " . json_encode($arrCustomerData, JSON_PRETTY_PRINT);

                        $logsArray['id'] = $logId;
                        $logsArray['emarsys_info'] = 'Transactional Mails';
                        $logsArray['description'] = $req;
                        $logsArray['action'] = 'Mail Sent';
                        $logsArray['message_type'] = 'Success';
                        $logsArray['log_action'] = 'True';
                        $logsArray['website_id'] = $websiteId;
                        $logsHelper->logs($logsArray);

                        $emarsysApiEventID = $this->_message->getEmarsysData()['emarsysEventId'];
                        $res = $api->sendRequest('POST', "event/$emarsysApiEventID/trigger", $arrCustomerData);

                        if ($res['status'] == 200) {
                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'Transactional Mails';
                            $logsArray['description'] = print_r($res, true);
                            $logsArray['action'] = 'Mail Sent';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'True';
                            $logsArray['website_id'] = $websiteId;
                            $logsHelper->logs($logsArray);
                            $logsArray['status'] = 'success';
                            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                            $logId = $logsHelper->manualLogs($logsArray);
                        } else {
                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'Transactional Mails';
                            $logsArray['description'] = print_r($res, true);
                            $logsArray['action'] = 'Mail Sent Fail';
                            $logsArray['message_type'] = 'Error';
                            $logsArray['log_action'] = 'False';
                            $logsArray['website_id'] = $websiteId;
                            $logsHelper->logs($logsArray);
                            $logsArray['status'] = 'error';
                            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                            $logId = $logsHelper->manualLogs($logsArray);
                        }
                    } else {
                        $logsArray['id'] = $logId;
                        $logsArray['emarsys_info'] = 'Transactional Mails';
                        $logsArray['description'] = 'Failed to Sync Contact to Emarsys \n Request: ' . print_r($buildRequest, true) . '\n Response: ' . print_r($response, true);
                        $logsArray['action'] = 'Mail Sent Fail';
                        $logsArray['message_type'] = 'Error';
                        $logsArray['log_action'] = 'False';
                        $logsArray['website_id'] = $websiteId;
                        $logsHelper->logs($logsArray);
                    }
                }
            }
        } catch (\Exception $e) {
            //echo $e->getMessgae();
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Emarsys Transactional Email Error';
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Mail Sending Fail';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'Fail';
            $logsArray['website_id'] = $websiteId;
            $logsHelper->logs($logsArray);
            $logsArray['status'] = 'error';
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logId = $logsHelper->manualLogs($logsArray);
        }
    }
}
