<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\{
    Framework\Model\AbstractModel,
    Framework\Model\Context,
    Framework\Model\ResourceModel\AbstractResource,
    Framework\Registry,
    Framework\Data\Collection\AbstractDb,
    Framework\Stdlib\DateTime\DateTime,
    Framework\Mail\MessageInterface,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Helper\Data as EmarsysHelper,
    Helper\Logs as EmarsysLogsHelper,
    Model\ResourceModel\Customer as CustomerResourceModel,
    Model\Api\Api as EmarsysModelApiApi
};

/**
 * Class Transport
 * @package Emarsys\Emarsys\Model
 */
class SendEmail extends AbstractModel
{
    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var CustomerResourceModel
     */
    protected $CustomerResourceModel;

    /**
     * @var MessageInterface|\Zend_Mail
     */
    protected $_message;

    /**
     * @var EmarsysLogsHelper
     */
    protected $logsHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EmarsysModelApiApi
     */
    protected $api;

    /**
     * @var EmarsyseventsFactory
     */
    protected $emarsyseventsFactory;

    /**
     * SendEmail constructor.
     * @param Context $context
     * @param Registry $registry
     * @param EmarsysHelper $emarsysHelper
     * @param CustomerResourceModel $customerResourceModel
     * @param DateTime $date
     * @param MessageInterface $message
     * @param EmarsysLogsHelper $logsHelper
     * @param StoreManagerInterface $storeManagerInterface
     * @param EmarsysModelApiApi $api
     * @param EmarsyseventsFactory $emarsyseventsFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        EmarsysHelper $emarsysHelper,
        CustomerResourceModel $customerResourceModel,
        DateTime $date,
        MessageInterface $message,
        EmarsysLogsHelper $logsHelper,
        StoreManagerInterface $storeManagerInterface,
        EmarsysModelApiApi $api,
        EmarsyseventsFactory $emarsyseventsFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->date = $date;
        $this->customerResourceModel = $customerResourceModel;
        $this->_message = $message;
        $this->logsHelper = $logsHelper;
        $this->storeManager = $storeManagerInterface;
        $this->api = $api;
        $this->emarsyseventsFactory = $emarsyseventsFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @return bool
     * @throws \Exception
     */
    public function sendMail($message)
    {
        $errorStatus = false;
        $emarsysErrorStatus = false;
        $emarsysApiEventID = '';

        $storeId = $this->storeManager->getStore()->getId();
        try {
            $_emarsysPlaceholdersData = $message->getEmarsysData();

            if (isset($_emarsysPlaceholdersData['store_id'])) {
                $storeId = $_emarsysPlaceholdersData['store_id'];
            }
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

            $logsArray['job_code'] = 'transactional_mail';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Transactional Email Started';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logsHelper->manualLogs($logsArray, 1);
            $logsArray['id'] = $logId;

            //check emarsys module status
            if ($this->emarsysHelper->isEmarsysEnabled($websiteId)) {

                //check emarsys transaction emails enable
                if ($this->checkTransactionalMailEnabled($websiteId)) {
                    $emarsysPlaceholdersData = [];

                    if (is_array($_emarsysPlaceholdersData)) {
                        if (isset($_emarsysPlaceholdersData['emarsysPlaceholders'])) {
                            $emarsysPlaceholdersData = $_emarsysPlaceholdersData['emarsysPlaceholders'];
                        }
                        if (isset($_emarsysPlaceholdersData['emarsysEventId'])) {
                            $emarsysApiEventID = $_emarsysPlaceholdersData['emarsysEventId'];
                        }
                    }

                    //check emarsys event id present
                    if ($emarsysApiEventID != '') {
                        //mapping found for event
                        $this->api->setWebsiteId($websiteId);
                        /** @var \Zend\Mail\Message $zendMessage */
                        if (is_callable([$message, 'getZendMessage'])
                            && method_exists($message, 'getZendMessage')
                            && $zendMessage = $message->getZendMessage()
                        ) {
                            /** @var \Zend\Mail\AddressList $addressList */
                            $addressList = $zendMessage->getTo();
                            $externalId = $addressList->current()->getEmail();
                        } else {
                            $externalId = $message->getRecipients()[0];
                        }
                        $buildRequest = [];

                        $buildRequest['key_id'] = $this->customerResourceModel->getKeyId(EmarsysHelper::CUSTOMER_EMAIL, $storeId);
                        $buildRequest[$buildRequest['key_id']] = $externalId;

                        $customerId = $this->customerResourceModel->checkCustomerExistsInMagento(
                            $externalId,
                            $websiteId
                        );

                        if (!empty($customerId)) {
                            $customerIdKey = $this->customerResourceModel->getKeyId(EmarsysHelper::CUSTOMER_ID, $storeId);
                            $buildRequest[$customerIdKey] = $customerId;
                        }

                        $data = [
                            'email' => $externalId,
                            'store_id' => $storeId
                        ];
                        $subscribeId = $this->customerResourceModel->getSubscribeIdFromEmail($data);

                        if (!empty($subscribeId)) {
                            $subscriberIdKey = $this->customerResourceModel->getKeyId(EmarsysHelper::SUBSCRIBER_ID, $storeId);
                            $buildRequest[$subscriberIdKey] = $subscribeId;
                        }

                        $emailKey = $this->customerResourceModel->getKeyId(EmarsysHelper::CUSTOMER_EMAIL, $storeId);
                        $buildRequest['key_id'] = $emailKey;
                        $buildRequest[$emailKey] = $externalId;

                        //log information that is about to send for contact sync
                        $logsArray['emarsys_info'] = 'Create contact if not exists';
                        $logsArray['description'] = 'PUT ' . " contact/?create_if_not_exists=1 " . \Zend_Json::encode($buildRequest);
                        $logsArray['action'] = 'Magento to Emarsys';
                        $logsArray['message_type'] = 'Success';
                        $logsArray['log_action'] = 'sync';
                        $this->logsHelper->manualLogs($logsArray);

                        //sync contact to emarsys
                        $response = $this->api->sendRequest(
                            'PUT',
                            'contact/?create_if_not_exists=1',
                            $buildRequest
                        );
                        if (($response['status'] == 200) || ($response['status'] == 400 && $response['body']['replyCode'] == 2009)) {
                            //contact synced to emarsys successfully

                            //log contact sync response
                            $contactSyncReq =
                            $logsArray['emarsys_info'] = 'Emarsys response on Contact creation';
                            $logsArray['description'] = 'Created Contact ' . $externalId . ' in Emarsys successfully | '
                                . 'PUT  contact/?create_if_not_exists=1 ' . \Zend_Json::encode($response);;
                            $logsArray['action'] = 'Synced to Emarsys';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'True';
                            $this->logsHelper->manualLogs($logsArray);

                            $arrCustomerData = [
                                "key_id" => $buildRequest['key_id'],
                                "external_id" => $buildRequest[$buildRequest['key_id']],
                                "data" => $emarsysPlaceholdersData
                            ];

                            //log information that is about to send for email sync
                            $logsArray['emarsys_info'] = 'Trigger Event';
                            $logsArray['description'] = 'POST ' . " event/$emarsysApiEventID/trigger: " . \Zend_Json::encode($arrCustomerData);
                            $logsArray['action'] = 'Mail Sent';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'True';
                            $this->logsHelper->manualLogs($logsArray);

                            //trigger email event
                            $emailEventResponse = $this->api->sendRequest(
                                'POST',
                                "event/$emarsysApiEventID/trigger",
                                $arrCustomerData
                            );

                            //check email event's response status
                            if ($emailEventResponse['status'] == 200) {
                                //email send successfully
                                $logsArray['emarsys_info'] = 'Transactional Mail response';
                                $logsArray['description'] = print_r($emailEventResponse, true);
                                $logsArray['action'] = 'Mail Sent';
                                $logsArray['message_type'] = 'Success';
                                $logsArray['log_action'] = 'True';
                                $this->logsHelper->manualLogs($logsArray);
                            } else {
                                //email send event failed
                                $emarsysErrorStatus = true;
                                $logsArray['emarsys_info'] = 'Transactional Mails response';
                                $logsArray['description'] = 'Failed to send email from emarsys. '
                                    . 'Emarsys Event ID : ' . $emarsysApiEventID
                                    . ', Store Id : ' . $storeId
                                    . ' | Response : ' . print_r($emailEventResponse, true);
                                $logsArray['action'] = 'Mail Sent Fail';
                                $logsArray['message_type'] = 'Error';
                                $logsArray['log_action'] = 'False';
                                $this->logsHelper->manualLogs($logsArray);
                            }
                        } else {
                            //failed to sync contact to emarsys
                            $logsArray['emarsys_info'] = 'Transactional Mails';
                            $logsArray['description'] = 'Failed to Sync Contact to Emarsys. '
                                . 'Emarsys Event ID :' . $emarsysApiEventID
                                . ', Store Id : ' . $storeId
                                . ' | Request: ' . print_r($buildRequest, true)
                                . ' | Response: ' . print_r($response, true);
                            $logsArray['action'] = 'Mail Sent Fail';
                            $logsArray['message_type'] = 'Error';
                            $logsArray['log_action'] = 'False';
                            $this->logsHelper->manualLogs($logsArray);
                        }
                    } else {
                        //no mapping found for emarsys event
                        $errorStatus = true;
                        $logsArray['status'] = 'error';
                        $logsArray['emarsys_info'] = 'Error';
                        $logsArray['description'] = 'No Mapping Found for the Emarsys Event ID : ' . $emarsysApiEventID . '. Email sent from Magento for the store .' . $storeId;
                        $logsArray['action'] = 'Mail Sent';
                        $logsArray['message_type'] = 'Error';
                        $logsArray['log_action'] = 'True';
                        $this->logsHelper->manualLogs($logsArray);
                    }
                } else {
                    //emarsys transaction emails disable
                    $errorStatus = true;
                    $logsArray['status'] = 'notice';
                    $logsArray['emarsys_info'] = 'Transactional Mails';
                    $logsArray['description'] = 'Emarsys Transaction Email Either Disabled or Some Extension Conflict (if enabled). Email sent from Magento for store id ' . $storeId;
                    $logsArray['action'] = 'Mail Sent';
                    $logsArray['message_type'] = 'notice';
                    $logsArray['log_action'] = 'True';
                    $this->logsHelper->manualLogs($logsArray);
                }
            } else {
                //emarsys module disabled
                $errorStatus = true;
                $logsArray['status'] = 'notice';
                $logsArray['emarsys_info'] = 'Transactional Mails';
                $logsArray['description'] = 'Emarsys is not enabled. Email sent from Magento for Store Id ' . $storeId;
                $logsArray['action'] = 'Mail Sent';
                $logsArray['message_type'] = 'notice';
                $logsArray['log_action'] = 'True';
                $this->logsHelper->manualLogs($logsArray);
            }
        } catch (\Exception $e) {
            //log exception
            $errorStatus = true;
            $logsArray['status'] = 'error';
            $logsArray['emarsys_info'] = 'Emarsys Transactional Email Error';
            $logsArray['description'] = $e->getMessage() . " Due to this error, Email Sent From Magento for Store Id " . $storeId;
            $logsArray['action'] = 'Mail Sending Fail';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'Fail';
            $this->logsHelper->manualLogs($logsArray);
        }

        $emarsysEvent = '';
        $emarsysEventCollection = $this->emarsyseventsFactory->create()->getCollection()
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('event_id', ['eq' => $emarsysApiEventID]);
        if ($emarsysEventCollection->getSize()) {
            $emarsysEvent = $emarsysEventCollection->getFirstItem()->getEmarsysEvent();
            $emarsysEvent = 'Template Name: ' . ucwords(str_replace('_', ' ', $emarsysEvent));
        }

        if ($errorStatus || $emarsysErrorStatus) {
            $logsArray['status'] = 'error';
            $logsArray['message_type'] = 'Error';
            $logsArray['emarsys_info'] = 'Error';
            $logsArray['description'] = __('Error while sending Transactional Email. %1', $emarsysEvent);
        } else {
            $logsArray['status'] = 'success';
            $logsArray['message_type'] = 'Success';
            $logsArray['emarsys_info'] = 'Success';
            $logsArray['description'] = __('Transactional Email Completed. %1', $emarsysEvent);
        }

        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogs($logsArray);

        return $errorStatus;
    }

    /**
     * @param $websiteId
     * @return mixed
     */
    public function checkTransactionalMailEnabled($websiteId)
    {
        $status = $this->emarsysHelper->getConfigValue(
            'transaction_mail/transactionmail/enable_customer', 'websites', $websiteId
        );

        return $status;
    }
}
