<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Webdav;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Helper\Logs;
use Magento\Config\Model\ResourceModel\Config;
use Emarsys\Emarsys\Model\WebDav\WebDavExport;
use Magento\Backend\App\Action;

class Index extends Action
{
    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var WebDavExport
     */
    protected $webDavExport;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * Index constructor.
     *
     * @param EmarsysHelper $emarsysHelper
     * @param Context $context
     * @param DateTime $date
     * @param Logs $logsHelper
     * @param Config $config
     * @param WebDavExport $webDavExport
     */
    public function __construct(
        EmarsysHelper $emarsysHelper,
        Context $context,
        DateTime $date,
        Logs $logsHelper,
        Config $config,
        WebDavExport $webDavExport
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->config = $config;
        $this->webDavExport = $webDavExport;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws FileSystemException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params['store'])) {
            $storeId = $params['store'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreId();
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
            //check webdav connection with credentials
            $checkWebDavConnection = $this->webDavExport->testWebDavConnection(
                $webDavUrl,
                $webDavUser,
                $webDavPass
            );
            if ($checkWebDavConnection['status']) {
                //test connection is successful.
                $scopeType = 'websites';
                $scopeId = $website;
                if ($website == '') {
                    $scopeType = 'default';
                    $scopeId = 0;
                }
                //save webdav_url information in respected configuration.
                $this->config->saveConfig(
                    'emartech/webdav_setting/webdav_url',
                    $webDavUrl,
                    $scopeType,
                    $scopeId
                );
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'WebDav test connection';
                $logsArray['description'] = "Inserted URL";
                $logsArray['action'] = 'Synced to Magento';
                $logsArray['message_type'] = 'Success';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->manualLogs($logsArray);

                //save webdav_user information in respected configuration.
                $this->config->saveConfig(
                    'emartech/webdav_setting/webdav_user',
                    $webDavUser,
                    $scopeType,
                    $scopeId
                );
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'WebDav test connection';
                $logsArray['description'] = "Inserted username";
                $logsArray['action'] = 'Synced to Magento';
                $logsArray['message_type'] = 'Success';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->manualLogs($logsArray);

                //save webdav_password information in respected configuration.
                $this->config->saveConfig(
                    'emartech/webdav_setting/webdav_password',
                    $webDavPass,
                    $scopeType,
                    $scopeId
                );
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'WebDav test connection';
                $logsArray['description'] = "Inserted password";
                $logsArray['action'] = 'Synced to Magento';
                $logsArray['message_type'] = 'Success';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->manualLogs($logsArray);
                $this->messageManager->addSuccessMessage(__('Test connection is successful.'));

                $logsArray['id'] = $logId;
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'WebDav test connection completed';
                $this->logsHelper->manualLogs($logsArray);
            } else {
                //test connection is failed.
                $this->messageManager->addErrorMessage(
                    __(
                        'Connection failed. Please check your credentials and try again.'
                    )
                );
                $logsArray['id'] = $logId;
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'WebDav connection failed. Please check your credentials and try again. '
                    . $checkWebDavConnection['response_body'];
                $this->logsHelper->manualLogs($logsArray);
            }
        } else {
            //valid credentials not found.
            $this->messageManager->addErrorMessage(
                __(
                    'Connection failed. Please check your credentials and try again.'
                )
            );
            $logsArray['id'] = $logId;
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'WebDav connection failed. Please enter valid credentials.';
            $this->logsHelper->manualLogs($logsArray);
        }
    }
}
