<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Webdav;

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
     * @param \Magento\Backend\App\Action\Context $context
     * @param DateTime $date
     * @param \Emarsys\Log\Helper\Logs $logsHelper
     * @param \Magento\Config\Model\ResourceModel\Config $config
     */
    public function __construct(
        Data $emarsysHelper,
        \Magento\Backend\App\Action\Context $context,
        DateTime $date,
        \Emarsys\Log\Helper\Logs $logsHelper,
        \Magento\Config\Model\ResourceModel\Config $config
    ) {
    
        $this->emarsysHelper = $emarsysHelper;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->config = $config;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params['store'])) {
            $storeId = $params['store'];
        } else {
            $storeId = 1;
        }
        $website = $this->getRequest()->getParam('website');
        $logsArray['job_code'] = 'testconnection';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'WebDav test connection initiated';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $website;
        $logId = $this->logsHelper->manualLogs($logsArray);


        $webDavUrl = $params['url'];
        $webDavUser = $params['username'];
        $webDavPass = $params['password'];

        if ($webDavUrl != '' && $webDavUser != '' && $webDavPass != '') {
            $errorStatus = 0;
        } else {
            $errorStatus = 1;
        }

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
                $scopeType = 'websites';
                $defaultScopeType = 'default';
                $defaultScopeId = '0';
                $scopeId = $website;
                if ($website == '') {
                    $scopeType = 'default';
                    $scopeId = 0;
                }

                $this->config->saveConfig('emarsys_settings/webdav_setting/webdav_url', $webDavUrl, $scopeType, $scopeId);
                if ($website == 1) {
                    $this->config->saveConfig('emarsys_settings/webdav_setting/webdav_url', $webDavUrl, $defaultScopeType, $defaultScopeId);
                }

                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'WebDav test connection';
                $logsArray['description'] = "Inserted URL";
                $logsArray['action'] = 'Synced to Magento';
                $logsArray['message_type'] = 'Success';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->logs($logsArray);


                $this->config->saveConfig('emarsys_settings/webdav_setting/webdav_user', $webDavUser, $scopeType, $scopeId);
                if ($website == 1) {
                    $this->config->saveConfig('emarsys_settings/webdav_setting/webdav_user', $webDavUser, $defaultScopeType, $defaultScopeId);
                }

                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'WebDav test connection';
                $logsArray['description'] = "Inserted username";
                $logsArray['action'] = 'Synced to Magento';
                $logsArray['message_type'] = 'Success';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->logs($logsArray);

                $this->config->saveConfig('emarsys_settings/webdav_setting/webdav_password', $webDavPass, $scopeType, $scopeId);
                if ($website == 1) {
                    $this->config->saveConfig('emarsys_settings/webdav_setting/webdav_password', $webDavPass, $defaultScopeType, $defaultScopeId);
                }

                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'WebDav test connection';
                $logsArray['description'] = "Inserted password";
                $logsArray['action'] = 'Synced to Magento';
                $logsArray['message_type'] = 'Success';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->logs($logsArray);

                $this->messageManager->addSuccess('Test connection is success.');

                $logsArray['id'] = $logId;
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'WebDav test connection completed';
                $this->logsHelper->manualLogsUpdate($logsArray);
            } else {
                $this->messageManager->addError('Test connection is failed.');
                $logsArray['id'] = $logId;
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'WebDav test connection failed';
                $this->logsHelper->manualLogsUpdate($logsArray);
            }
        } else {
            $this->messageManager->addError('Please enter the valid credentials');
            $logsArray['id'] = $logId;
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'WebDav test connection is failed. Please enter valid credentials';
            $this->logsHelper->manualLogsUpdate($logsArray);
        }
    }
}
