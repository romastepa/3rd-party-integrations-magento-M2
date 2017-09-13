<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\FtpTestConnection;

use Emarsys\Emarsys\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface as Logger;


/**
 * Class TestConnection for API credentials
 */
class Index extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $config;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Logger
     */
    protected $logger;
    
    /*
     * @var scopeConfig
     */
    protected $scopeConfig;
    /**
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param DateTime $date
     * @param \Magento\Customer\Model\CustomerFactory $customer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param Logger $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory,
        DateTime $date,
        \Magento\Customer\Model\CustomerFactory $customer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Config\Model\ResourceModel\Config $config,
        Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig

    )
    {
        $this->customer = $customer;
        $this->storeManager = $storeManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->date = $date;
        $this->config = $config;
        $this->getRequest = $request;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Emarsys test connection api credentials
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $data = $this->getRequest->getParams();
        if ($data['ftpssl'] == 1) {
            $ftpConnection = @ftp_ssl_connect($data['hostname'], $data['port'], 30);
        } else {
            $ftpConnection = @ftp_connect($data['hostname'], $data['port'], 30);
        }
        if ($ftpConnection != '') {
            $ftpLogin = @ftp_login($ftpConnection, $data['username'], $data['password']);
            if ($ftpLogin == 1) {
                $pasvstat = true;
                if ($data['passivemode'] == 1) {
                    $pasvstat = @ftp_pasv($ftpConnection, true);
                }
                
                if($pasvstat) {
                    // Save value in DB
                    $scope = 'websites';
                    $websiteId = $data['website'];

                    $this->config->saveConfig('emarsys_settings/ftp_settings/hostname', $data['hostname'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/port', $data['port'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/username', $data['username'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/ftp_password', $data['password'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/ftp_bulk_export_dir', $data['bulkexpdir'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/useftp_overssl', $data['ftpssl'], $scope, $websiteId);
                    $this->config->saveConfig('emarsys_settings/ftp_settings/usepassive_mode', $data['passivemode'], $scope, $websiteId);

                    if ($websiteId == 1) {
                        $websiteId = 0;
                        $scope = 'default';
                        $this->config->saveConfig('emarsys_settings/ftp_settings/hostname', $data['hostname'], $scope, $websiteId);
                        $this->config->saveConfig('emarsys_settings/ftp_settings/port', $data['port'], $scope, $websiteId);
                        $this->config->saveConfig('emarsys_settings/ftp_settings/username', $data['username'], $scope, $websiteId);
                        $this->config->saveConfig('emarsys_settings/ftp_settings/ftp_password', $data['password'], $scope, $websiteId);
                        $this->config->saveConfig('emarsys_settings/ftp_settings/ftp_bulk_export_dir', $data['bulkexpdir'], $scope, $websiteId);
                        $this->config->saveConfig('emarsys_settings/ftp_settings/useftp_overssl', $data['ftpssl'], $scope, $websiteId);
                        $this->config->saveConfig('emarsys_settings/ftp_settings/usepassive_mode', $data['passivemode'], $scope, $websiteId);
                    }
                    $this->messageManager->addSuccess('Test connection successful !!!');
                } else {
                    $this->messageManager->addError('Failed to connect using passive mode.');                   
                }
            } else {
                $this->messageManager->addError('Failed to login with server !!!');
            }
        } else {
            $this->messageManager->addError('Failed to connect with server !!!');
        }
    }
}
