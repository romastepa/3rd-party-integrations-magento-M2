<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\Io\File;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Translate\Inline\StateInterface;

/**
 * Class CleanLog
 * @package Emarsys\Emarsys\Cron
 */
class CleanLog
{
    /**
     * @var DeploymentConfig
     */
    protected $config;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var DirectoryList
     */
    protected $baseDirPath;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var File
     */
    protected $ioFile;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * CleanLog constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $baseDirPath
     * @param ResourceConnection $resource
     * @param File $ioFile
     * @param Config $resourceConfig
     * @param DateTime $date
     * @param EmarsysHelper $emarsysHelper
     * @param DeploymentConfig $config
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param StateInterface $inlineTranslation
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $baseDirPath,
        ResourceConnection $resource,
        File $ioFile,
        Config $resourceConfig,
        DateTime $date,
        EmarsysHelper $emarsysHelper,
        DeploymentConfig $config,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        StateInterface $inlineTranslation
    ) {
        $this->config = $config;
        $this->date = $date;
        $this->baseDirPath = $baseDirPath;
        $this->scopeConfig = $scopeConfig;
        $this->_resource = $resource;
        $this->resourceConfig = $resourceConfig;
        $this->ioFile = $ioFile;
        $this->emarsysHelper = $emarsysHelper;
        $this->storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
    }

    public function execute()
    {
        /** @var \Magento\Store\Model\Store $store */
        foreach ($this->storeManager->getStores() as $store) {
            $websiteId = $store->getWebsiteId();
            $storeId = $store->getStoreId();
            $scopeType = 'websites';

            $configData = $this->config->getConfigData();
            $connection = $configData['db']['connection']['default'];

            $username = $connection['username'];
            $password = $connection['password'];
            $hostname = $connection['host'];
            $database = $connection['dbname'];

            $username = escapeshellcmd($username);
            $password = escapeshellcmd($password);
            $hostname = escapeshellcmd($hostname);
            $database = escapeshellcmd($database);

            $logCleaning = $store->getConfig('logs/log_setting/log_cleaning');
            $archive = $store->getConfig('logs/log_setting/archive_data');
            $backupFile = '';
            if ($logCleaning) {
                $logCleaningDays = $store->getConfig('logs/log_setting/log_days');
                $cleanUpDate = $this->date->date('Y-m-d', strtotime("-" . $logCleaningDays . " days"));
                $cleanUpDate = $this->emarsysHelper->getDateTimeInLocalTimezone($cleanUpDate);

                if ($archive == 'archive') {
                    /* Create archive folder */
                    $varDir = $this->baseDirPath->getRoot() . "/";
                    $archivePath = $store->getConfig('logs/log_setting/archive_datapath');

                    $archiveFolder = $varDir . $archivePath . $this->date->date('Y-m-d');
                    $this->ioFile->checkAndCreateFolder($archiveFolder);

                    /* backup sql file */
                    $backupFile = $archiveFolder . "/emarsys_logs.sql";
                }
                /* logs table */
                $logTable = $this->resourceConfig->getTable('emarsys_log_details');

                /* Dump record of logs table in one file */
                $successLog = 0;
                $errorLog = 0;

                try {
                    if ($archive == 'archive') {
                        $command = "mysqldump -u$username -p$password -h$hostname $database $logTable > $backupFile";
                        system(escapeshellarg($command), $result);
                    }

                    /* Delete record from log_details tables */
                    $sqlConnection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
                    $sqlConnection->delete($this->resourceConfig->getTable('emarsys_log_details'), 'DATE(created_at) <= "' . $cleanUpDate . '"');
                    $successLog = 1;
                } catch (\Exception $e) {
                    $errorLog = 1;
                    $errorResult[] = $e->getMessage();
                }

                /**
                 * Email send if failure
                 */

                $errorEmailData = $this->emarsysHelper->logErrorSenderEmail();
                $senderEmailId = $errorEmailData['email'];
                $senderEmailName = $errorEmailData['name'];
                $logEmailRecipient = $this->scopeConfig->getValue('logs/log_setting/log_email_recipient', $scopeType, $websiteId);
                if ($logEmailRecipient == '' && $websiteId == 1) {
                    $logEmailRecipient = $this->scopeConfig->getValue('logs/log_setting/log_email_recipient');
                }
                if ($successLog == 1 && $archive == 'archive') {
                    $templateOptions = ['area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE, 'store' => $storeId];
                    $templateVars = [
                        'store' => $storeId,
                        'message' => $backupFile,
                    ];
                    $from = [
                        'email' => $senderEmailId,
                        'name' => $senderEmailName,
                    ];
                    $this->inlineTranslation->suspend();
                    $to = [
                        'email' => $logEmailRecipient,
                        'name' => $logEmailRecipient,
                    ];
                    $transport = $this->_transportBuilder->setTemplateIdentifier('emarsys_log_cleaning_template')
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
                    $transport->sendMessage();
                    $this->inlineTranslation->resume();
                }

                /* Send email for Error in archived File */
                if ($errorLog == 1) {
                    $errorMessage = implode(",", $errorResult);
                    $templateOptions = ['area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE, 'store' => $storeId];
                    $templateVars = [
                        'store' => $storeId,
                        'message' => $errorMessage,
                    ];
                    $from = [
                        'email' => $senderEmailId,
                        'name' => $senderEmailName,
                    ];
                    $this->inlineTranslation->suspend();
                    $to = [
                        'email' => $logEmailRecipient,
                        'name' => $logEmailRecipient,
                    ];
                    $transport = $this->_transportBuilder->setTemplateIdentifier('emarsys_log_cleaning_error_template')
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
                    $transport->sendMessage();
                    $this->inlineTranslation->resume();
                }

                /**
                 * Delete the archived Data older than $deleteArchivedData
                 */

                if ($archive == 'archive') {
                    $deleteArchivedData = $this->scopeConfig->getValue('logs/log_setting/delete_archive_days', $scopeType, $websiteId); // Number of days to delete old archived data
                    if ($deleteArchivedData == '' && $websiteId == 1) {
                        $deleteArchivedData = $this->scopeConfig->getValue('logs/log_setting/delete_archive_days');
                    }
                    $deleteDate = $this->date->date('Y-m-d', strtotime("-" . $deleteArchivedData . " days")); //date to delete old archived data
                    $deleteDate = $this->emarsysHelper->getDateTimeInLocalTimezone($deleteDate);
                    $archiveFolderPath = $archivePath;                                      //Archive folder path
                    $dir = new \DirectoryIterator($archiveFolderPath);                      //Sub-dir inside archive dir
                    foreach ($dir as $fileinfo) {
                        if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                            $path = $archiveFolderPath . "/" . $fileinfo->getFilename();        //get sub-dir name
                            $stat = stat($path);
                            $folderCreateDate = $this->date->date('Y-m-d', $stat['ctime']);  //create date of sub-dir
                            $folderCreateDate = $this->emarsysHelper->getDateTimeInLocalTimezone($folderCreateDate);
                            /* If create date of sub-dir less than delete date of sub-dir then delete sub-dir */
                            if ($folderCreateDate <= $deleteDate && is_dir($path)) {
                                array_map('unlink', glob($path . "/*"));                      //delete all files inside directory
                                rmdir($path);                                               //delete directory
                            }
                        }
                    }
                }
            }
        }
    }
}
