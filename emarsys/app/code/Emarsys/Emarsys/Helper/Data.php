<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var DateTime
     */
    protected $date;


    const Version = '2.1';


    /**
     * @var  \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Filesystem\File
     */
    protected $file;

    /**
     * @var Timezone
     */
    protected $timezone;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var
     */
    private $_username;

    /**
     * @var
     */
    private $_secret;

    /**
     * @var
     */
    private $_emarsysApiUrl;

    const MODULE_NAME='Emarsys_Emarsys';

   /**
    * 
    * @param Context $context
    * @param DateTime $date
    * @param Timezone $timezone
    * @param \Emarsys\Log\Helper\Logs $logHelper
    * @param \Magento\Framework\Filesystem\Io\File $file
    * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
    * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
    * @param \Magento\Framework\Message\ManagerInterface $messageManager
    * @param \Emarsys\Emarsys\Model\Queue $queueModel
    * @param \Magento\Framework\App\ProductMetadataInterface $productMetadataInterface
    * @param \Emarsys\Emarsys\Model\QueueFactory $queueModelColl
    * @param \Magento\Email\Model\TemplateFactory $emailTemplateModelColl
    * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $magentoEventsCollection
    * @param \Emarsys\Emarsys\Model\PlaceholdersFactory $emarsysEventPlaceholderMappingFactory
    * @param \Magento\Email\Controller\Adminhtml\Email\Template $emailTemplate
    * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $magentoProductAttributeColl
    * @param \Emarsys\Emarsys\Model\EmarsyseventmappingFactory $Emarsyseventmapping
    * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory $emarsysEventCollectonFactory
    * @param \Emarsys\Emarsys\Model\Api $modelApi
    * @param \Emarsys\Emarsys\Model\Emarsysevents $EmarsyseventsModel
    * @param \Emarsys\Emarsys\Model\ResourceModel\Event $eventsResourceModel
    * @param \Magento\Email\Model\TemplateFactory $templateFactory
    * @param \Magento\Backend\Model\Session $session
    * @param \Emarsys\Emarsys\Model\EmarsyseventsFactory $EmarsyseventsModelFactory
    * @param \Emarsys\Emarsys\Model\Api\Api $api
    * @param \Magento\Framework\Registry $registry
    * @param \Emarsys\Log\Model\Logs $emarsysLogs
    * @param \Magento\Store\Model\ResourceModel\Store\CollectionFactory $storeCollection
    * @param \Magento\Framework\Module\ModuleListInterface $moduleListInterface
    */
    public function __construct(
        Context $context,
        DateTime $date,
        Timezone $timezone,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Emarsys\Emarsys\Model\Queue $queueModel,
        \Magento\Framework\App\ProductMetadataInterface $productMetadataInterface,
        \Emarsys\Emarsys\Model\QueueFactory $queueModelColl,
        \Magento\Email\Model\TemplateFactory $emailTemplateModelColl,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $magentoEventsCollection,
        \Emarsys\Emarsys\Model\PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        \Emarsys\Emarsys\Controller\Adminhtml\Email\Template $emailTemplate,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $magentoProductAttributeColl,
        \Emarsys\Emarsys\Model\EmarsyseventmappingFactory $Emarsyseventmapping,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory $emarsysEventCollectonFactory,
        \Emarsys\Emarsys\Model\Api $modelApi,
        \Emarsys\Emarsys\Model\Emarsysevents $EmarsyseventsModel,
        \Emarsys\Emarsys\Model\ResourceModel\Event $eventsResourceModel,
        \Magento\Email\Model\TemplateFactory $templateFactory,
        \Magento\Backend\Model\Session $session,
        \Emarsys\Emarsys\Model\EmarsyseventsFactory $EmarsyseventsModelFactory,
        \Emarsys\Emarsys\Model\Api\Api $api,
        \Magento\Framework\Registry $registry,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Magento\Store\Model\ResourceModel\Store\CollectionFactory $storeCollection,
        \Magento\Framework\Module\ModuleListInterface $moduleListInterface
    ) {
    
        $this->context = $context;
        $this->emarsysLogs = $emarsysLogs;
        $this->date = $date;
        $this->timezone = $timezone;
        $this->file = $file;
        $this->productMetadataInterface = $productMetadataInterface;
        $this->_transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->logHelper = $logHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->logger = $context->getLogger();
        $this->messageManager = $messageManager;
        $this->queue = $queueModel;
        $this->queueColl = $queueModelColl;
        $this->emailTemplateModel = $emailTemplateModelColl;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->magentoEventsCollection = $magentoEventsCollection;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
        $this->emailTemplate = $emailTemplate;
        $this->magentoProductAttributeColl = $magentoProductAttributeColl;
        $this->Emarsyseventmapping = $Emarsyseventmapping;
        $this->emarsysEventCollectonFactory = $emarsysEventCollectonFactory;
        $this->eventsResourceModel = $eventsResourceModel;
        $this->EmarsyseventsModel = $EmarsyseventsModel;
        $this->session = $session;
        $this->templateFactory = $templateFactory;
        $this->EmarsyseventsModelFactory = $EmarsyseventsModelFactory;
        $this->_emarsysApiUrl = 'https://trunk-int.s.emarsys.com/api/v2/';
        $this->api = $api;
        $this->registry = $registry;
        $this->modelApi = $modelApi;
        $this->_storeManager = $storeManager;
        $this->storeCollection = $storeCollection;
        $this->moduleListInterface = $moduleListInterface;
    }


    /**
     * Check for duplicate job
     * @param $entity
     * @return boolean
     */
    public function chkforDuplicateJob($entity)
    {

        $syncDirectory = BP . "/emarsys/lock/";
        $lockFile = $syncDirectory . $entity . ".lock";
        if (file_exists($lockFile)) {
            # check if it's stale
            $lockingPID = trim(file_get_contents($lockFile));

            # Get all active PIDs.
            $pids = explode("\n", trim("ps -e | awk '{print $1}'"));

            # If PID is still active, return true
            if (in_array($lockingPID, $pids)) {
                return true;
            } else {
                # Lock-file is stale, so kill it.  Then move on to re-creating it.
                // echo "Removing stale lock file.\n";
                unlink($lockFile);
                return false;
            }
        }
    }

    /**
     * 
     * @param type $username
     * @param type $password
     * @param type $endpoint
     * @param type $url
     */
    public function assignApiCredentials($username, $password, $endpoint, $url = null)
    {
        $this->_username = $username;
        $this->_secret = $password;
        if ($endpoint == 'custom') {
            $this->_emarsysApiUrl = rtrim($url, '/') . "/";
        } elseif ($endpoint == 'cdn') {
            $this->_emarsysApiUrl = "https://api-cdn.emarsys.net/api/v2/";
        } elseif ($endpoint == 'default') {
            $this->_emarsysApiUrl = "https://api.emarsys.net/api/v2/";
        }
    }


    public function getClient()
    {
        $url = $this->_emarsysApiUrl;
        $username = $this->_username;
        $password = $this->_secret;
        $config = [
            'api_url' => $url,
            'api_username' => $username,
            'api_password' => $password,
        ];
        $this->modelApi->_construct($config);
    }


    /**
     * 
     * @param type $storeId
     */
    public function getEmarsysAPIDetails($storeId)
    {
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        $scope = 'websites';
        $username = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_username', $scope, $websiteId);
        if ($username == '' && $websiteId == 1) {
            $username = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_username');
        }
        $password = $this->_secret = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_password', $scope, $websiteId);
        if ($password == '' && $websiteId == 1) {
            $password = $this->_secret = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_password');
        }
        $endpoint = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_endpoint', $scope, $websiteId);
        if ($endpoint == '' && $websiteId == 1) {
            $endpoint = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_endpoint');
        }
        if ($endpoint == 'custom') {
            $url = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_custom_url', $scope, $websiteId);
            if ($url == '' && $websiteId == 1) {
                $url = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_custom_url');
            }
            $this->_emarsysApiUrl = rtrim($url, '/') . "/";
        } elseif ($endpoint == 'cdn') {
            $this->_emarsysApiUrl = "https://api-cdn.emarsys.net/api/v2/";
        } elseif ($endpoint == 'default') {
            $this->_emarsysApiUrl = "https://api.emarsys.net/api/v2/";
        }
        $this->_username = $username;
        $this->_secret = $password;
    }

    /**
     * @param $requestType
     * @param $endPoint
     * @param string $requestBody
     * @return mixed|string
     * @throws Exception
     */
    public function send($requestType, $endPoint = null, $requestBody = '')
    {
        if ($endPoint == 'custom') {
            $endPoint = '';
        }


        if (!in_array($requestType, ['GET', 'POST', 'PUT', 'DELETE'])) {
            throw new Exception('Send first parameter must be "GET", "POST", "PUT" or "DELETE"');
        }
        // if($this->_username == "" && $this->_secret == "")
        //         $this->getEmarsysAPIDetails();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        switch ($requestType) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
        }
        curl_setopt($ch, CURLOPT_HEADER, false);

        $requestUri = $this->_emarsysApiUrl . $endPoint;
        curl_setopt($ch, CURLOPT_URL, $requestUri);

        /**
         * We add X-WSSE header for authentication.
         * Always use random 'nonce' for increased security.
         * timestamp: the current date/time in UTC format encoded as
         * an ISO 8601 date string like '2010-12-31T15:30:59+00:00' or '2010-12-31T15:30:59Z'
         * passwordDigest looks sg like 'MDBhOTMwZGE0OTMxMjJlODAyNmE1ZWJhNTdmOTkxOWU4YzNjNWZkMw=='
         */

        $nonce = md5(time());
        $timestamp = gmdate("c");
        $passwordDigest = base64_encode(sha1($nonce . $timestamp . $this->_secret, false));


        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-WSSE: UsernameToken ' .
                'Username="' . $this->_username . '", ' .
                'PasswordDigest="' . $passwordDigest . '", ' .
                'Nonce="' . $nonce . '", ' .
                'Created="' . $timestamp . '"',
                'Content-type: application/json;charset="utf-8"',
            ]);
        $output = curl_exec($ch);
        curl_close($ch);

        //echo "<pre>"; print_r($output);exit;

        return $output;
    }


    /**
     * 
     * @param type $storeId
     */
    public function insertFirstime($storeId)
    {
        $magentoEvents = $this->magentoEventsCollection->create();
        foreach ($magentoEvents as $magentoEvent) {
            $magentoEvent->getId();
            $eventMappingModel = $this->Emarsyseventmapping->create();
            $eventMappingModel->setMagentoEventId($magentoEvent->getId());
            $eventMappingModel->setEmarsysEventId('');
            $eventMappingModel->setStoreId($storeId);
            $eventMappingModel->save();
        }
    }

    /**
     * 
     * @return array
     */

    public function getReadonlyMagentoEventIds()
    {

        return [1, 2];
    }

    /**
     * Checking readonly magento events
     * @param int $id
     */
    public function isReadonlyMagentoEventId($id = 0)
    {

        if (in_array($id, $this->getReadonlyMagentoEventIds())) {
            return true;
        }
        return false;
    }

    /**
     * 
     * @param type $logId
     * @return array
     */
    public function importEvents($logId =null)
    {
        $logsArray['id'] = $logId;
        $logsArray['emarsys_info'] = 'Update Schema';
        try{
        $storeId = "";
        if ($this->session->getStoreId()) {
            $storeId = $this->session->getStoreId();
        }
        //get emarsys events and store it into array
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $apiEvents = $this->getEvents($storeId, $logId);
        if (count($apiEvents) == 0) {
            return;
        }
        // print_r($apiEvents);exit;
        $eventArray = [];
        foreach ($apiEvents as $key => $value) {
            $eventArray[$key] = $value;
        }
        //print_r($eventArray);exit;
        //Delete unwanted events exist in database
        $emarsysEvents = $this->emarsysEventCollectonFactory->create();
        foreach ($emarsysEvents as $emarsysEvent) {
            if (!array_key_exists($emarsysEvent->getEventId(), $eventArray)) {
                $this->eventsResourceModel->deleteEvent($emarsysEvent->getEventId(), $storeId);
            }
        }
        //Update & create new events found in Emarsys
        foreach ($eventArray as $key => $value) {
            //echo $key.'--->'.$value."<br>";
            $emarsysEvents = $this->emarsysEventCollectonFactory->create();
            $emarsysEvents->addFieldToFilter("event_id", $key)
                ->addFieldToFilter("store_id", $storeId)
                ->getFirstItem();
            if ($emarsysEvents->getSize()) {
                $model = $this->EmarsyseventsModel->load($key, "event_id");
                $model->setEmarsysEvent($value);
                $model->setStoreId($storeId);
                $model->save();
            } else {
                $model = $this->EmarsyseventsModelFactory->create();
                $model->setEventId($key);
                $model->setEmarsysEvent($value);
                $model->setStoreId($storeId);
                $model->save();
            }
        }
    }catch(\Exception $e){
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Update Schema';
            $logsArray['store_id'] = $storeId;
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'True';
            $logsArray['website_id'] = $websiteId;
            $this->logHelper->logs($logsArray);
            return;
        }
    }

    /**
     * 
     * @param type $storeId
     * @param type $logId
     * @return array
     */
    public function getEvents($storeId, $logId=null)
    {
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        $logsArray['id'] = $logId;
        $logsArray['store_id'] = $storeId;
        $logsArray['emarsys_info'] = 'Update Schema';
        try{
        $result = [];
        $this->api->setWebsiteId($websiteId);
        $response = $this->api->sendRequest('GET', 'event');
            if($response['status']==200){
        if (!empty($response['body']['data'])) {
            foreach ($response['body']['data'] as $item) {
                $result[$item['id']] = $item['name'];
            }
            $logsArray['description'] = print_r($result,true);
            $logsArray['action'] = 'Update Schema';
            $logsArray['message_type'] = 'Success';
            $logsArray['log_action'] = 'True';
            $logsArray['website_id'] = $websiteId;
            $this->logHelper->logs($logsArray);
        }
            }else{
                $logsArray['description'] = $response['body']['replyText'];
                $logsArray['action'] = 'Update Schema';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'True';
                $logsArray['website_id'] = $websiteId;
                $this->logHelper->logs($logsArray);
                return;
            }

        return $result;
        }catch (\Exception $e){
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Mail Sent';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'True';
            $logsArray['website_id'] = $websiteId;
            $this->logHelper->logs($logsArray);
        }
    }

    /**
     * 
     * @param type $websiteId
     * @return array
     */
    public function getContactUniqueField($websiteId)
    {
        return $this->scopeConfigInterface->getValue('contacts_synchronization/emarsys_emarsys/unique_field', 'default', 0);
    }

    /**
     * Get Log file path from configuration
     */
    public function getLogPath()
    {
        $path = $this->customerResourceModel->getDataFromCoreConfig('logs/log_setting/download_logpath', null, null);
        if (!empty($path)) {
            return BP . '/' . ltrim($path, '/');
        } else {
            return '';
        }
    }
    
    /**
     * 
     * @return array
     */
    public function getPhpInfoArray()
    {
        try {
            ob_start();
            phpinfo(INFO_ALL);

            $pi = preg_replace(
                [
                    '#^.*<body>(.*)</body>.*$#m', '#<h2>PHP License</h2>.*$#ms',
                    '#<h1>Configuration</h1>#', "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
                    "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                    '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                    '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                    '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                    "# +#", '#<tr>#', '#</tr>#'],
                [
                    '$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
                    '<h2>PHP Configuration</h2>' . "\n" . '<tr><td>PHP Version</td><td>$2</td></tr>' .
                    "\n" . '<tr><td>PHP Egg</td><td>$1</td></tr>',
                    '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                    '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
                    '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'
                ],
                ob_get_clean()
            );

            $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
            unset($sections[0]);

            $pi = [];
            foreach ($sections as $section) {
                $n = substr($section, 0, strpos($section, '</h2>'));
                preg_match_all(
                    '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                    $section,
                    $askapache,
                    PREG_SET_ORDER
                );
                foreach ($askapache as $m) {
                    if (!isset($m[0]) || !isset($m[1]) || !isset($m[2])) {
                        continue;
                    }
                    $pi[$n][$m[1]] = (!isset($m[3]) || $m[2] == $m[3]) ? $m[2] : array_slice($m, 2);
                }
            }
        } catch (Exception $exception) {
            return [];
        }

        return $pi;
    }

    /**
     * 
     * @return array
     */
    public function getPhpSettings()
    {
        return [
            'memory_limit' => $this->getMemoryLimit(),
            'max_execution_time' => @ini_get('max_execution_time'),
            'phpinfo' => $this->getPhpInfoArray()
        ];
    }


    public function getMemoryLimit($inMegabytes = true)
    {
        $memoryLimit = trim(ini_get('memory_limit'));

        if ($memoryLimit == '') {
            return 0;
        }

        $lastMemoryLimitLetter = strtolower(substr($memoryLimit, -1));
        switch ($lastMemoryLimitLetter) {
            case 'g':
                $memoryLimit *= 1024;
            case 'm':
                $memoryLimit *= 1024;
            case 'k':
                $memoryLimit *= 1024;
        }

        if ($inMegabytes) {
            $memoryLimit /= 1024 * 1024;
        }

        return $memoryLimit;
    }

    public function getPhpVersion()
    {
        $phpVersion = @phpversion();
        $array = explode("-", $phpVersion);
        return $array[0];
    }

    public function getVersion($asArray = false)
    {
        $version = '';
        if($this->productMetadataInterface->getVersion()){
            $version = $this->productMetadataInterface->getVersion();
        }
        return $version;
    }


    public function getRequirementsInfo($storeId = null)
    {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $host = '';
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $aggregatePortStatus = '';
        $scope = 'websites';
        //print_r($mem[1]/1024);
        if ($storeId) {
            $storeID = $storeId;
        } else {
            $storeID = $this->_storeManager->getStore()->getId();
        }
        $websiteId = $this->_storeManager->getStore($storeID)->getWebsiteId();
        $aggregatePortStatus = $this->openPortStatus($storeID);
        $smartInsight = $this->getCheckSmartInsight($websiteId);

        $clientPhpData = $this->getPhpSettings();


        if ($clientPhpData['phpinfo']['soap'] ['Soap Client'] == 'enabled') :
            $soapenable = 'Yes';
        else :
            $soapenable = 'No';
        endif;
        if ($clientPhpData['phpinfo']['curl'] ['cURL support'] == 'enabled') :
            $curlenable = 'Yes';
        else :
            $curlenable = 'No';
        endif;

        $requirements = [

            'php_version' => [
                'title' => 'PHP Version',
                'condition' => [
                    'sign' => '>=',
                    'value' => '5.6.0'
                ],
                'current' => [
                    'value' => $this->getPhpVersion(),
                    'status' => true
                ]
            ],

            'memory_limit' => [
                'title' => 'Memory Limit',
                'condition' => [
                    'sign' => '>=',
                    'value' => '512 MB'
                ],
                'current' => [
                    'value' => round($mem[1] / 1024) . ' MB',
                    'status' => true
                ]
            ],

            'magento_version' => [
                'title' => 'Magento Version',
                'condition' => [
                    'sign' => '>=',
                    'value' => '2.1',
                ],
                'current' => [
                    'value' => $this->getVersion(false),
                    'status' => true
                ]
            ],

            'emarsys_extension_version' => [
                'title' => 'Emarsys Extension Version',
                'condition' => [
                    'sign' => '>=',
                    'value' => '1.0.0',
                ],
                'current' => [
                    'value' => $this->getEmarsysVersion(),
                    'status' => true
                ]
            ],

            'curl_enabled' => [
                'title' => 'Curl Enabled',
                'condition' => [
                    'sign' => '',
                    'value' => 'Yes',
                ],
                'current' => [
                    'value' => $curlenable,
                    'status' => true
                ]
            ],
            'tcp_21' => [
                'title' => 'TCP21,TCP32000-35000Open',
                'condition' => [
                    'sign' => '',
                    'value' => 'Yes'
                ],
                'current' => [
                    'value' => $aggregatePortStatus,
                    'status' => true
                ]
            ],
            'soap_enabled' => [
                'title' => 'SOAP Enabled',
                'condition' => [
                    'sign' => '',
                    'value' => 'Yes'
                ],
                'current' => [
                    'value' => $soapenable,
                    'status' => true
                ]
            ],

            'smart_insight' => [
                'title' => 'Smart Insight',
                'condition' => [
                    'sign' => '',
                    'value' => 'Enable'
                ],
                'current' => [
                    'value' => $smartInsight,
                    'status' => true
                ]
            ],


            'real_tome_sync' => [
                'title' => 'Realtime Synchronization',
                'condition' => [
                    'sign' => '',
                    'value' => 'Enable'
                ],
                'current' => [
                    'value' => $this->getRealTimeSync($websiteId),
                    'status' => true
                ]
            ],

            'emarsys_connection_setting' => [
                'title' => 'Emarsys Connection Setting',
                'condition' => [
                    'sign' => '',
                    'value' => 'Enable'
                ],
                'current' => [
                    'value' => $this->getEmarsysConnectionSetting($websiteId),
                    'status' => true
                ]
            ],

        ];

        foreach ($requirements as $key => &$requirement) {
            // max execution time is unlimited
            if ($key == 'max_execution_time' && $clientPhpData['max_execution_time'] == 0) {
                continue;
            }

            $requirement['current']['status'] = version_compare(
                $requirement['current']['value'],
                $requirement['condition']['value'],
                $requirement['condition']['sign']
            );
        }

        return $requirements;
    }

    public function openPortStatus($storeID){

        $websiteId = $this->_storeManager->getStore($storeID)->getWebsiteId();
        $scope = 'websites';
        if ($scope && $websiteId) {
            $host = $this->scopeConfigInterface->getValue('emarsys_settings/ftp_settings/hostname', $scope, $websiteId);
        } else {
            $host = $this->scopeConfigInterface->getValue('emarsys_settings/ftp_settings/hostname');
        }
        $ports = array(21, rand(32000, 32500), rand(32000, 32500));
        $portStatus = array();
        foreach ($ports as $port) {
            $errno = null;
            $errstr = null;

            $connection = @fsockopen($host, $port, $errno, $errstr);

            if (is_resource($connection)) {
                $portStatus[] = 'open';
                fclose($connection);
            } else {
                $portStatus[] = 'closed';
            }
        }
        if (in_array('closed', $portStatus)) {
            $aggregatePortStatus = 'No';
        } else {
            $aggregatePortStatus = 'Yes';
        }
      return $aggregatePortStatus;
    }


    public function syncFail($customerId, $websiteId, $storeId, $cron, $entityType)
    {

        $queueColl = $this->queueColl->create()->getCollection();
        $queueModel = $this->queue;
        $queueColl->addFieldToFilter('entity_id', $customerId);
        $queueColl->addFieldToFilter('website_id', $websiteId);

        if ($queueColl->getSize() > 0) {
            $data = $queueColl->getData()[0];
            $queueModel->load($data['id']);
            if ($cron == 1) {
                $queueModel->setHitCount($data['hit_count'] + 1);
            } else {
                $queueModel->setHitCount(0);
            }
            $queueModel->save();
        } else {
            $queueModel->setEntityId($customerId);
            $queueModel->setWebsiteId($websiteId);
            $queueModel->setStoreId($storeId);
            $queueModel->setEntityTypeId($entityType);
            $queueModel->save();
        }
    }

    public function syncSuccess($customerId, $websiteId, $storeId, $cron)
    {

        $queueColl = $this->queueColl->create()->getCollection();
        $queueModel = $this->queue;

        $queueColl->addFieldToFilter('entity_id', $customerId);
        $queueColl->addFieldToFilter('website_id', $websiteId);

        if ($queueColl->getSize() > 0) {
            $data = $queueColl->getData()[0];
            $queueModel->load($data['id']);
            $queueModel->delete();
        }
    }


    public function insertFirstimeMappingPlaceholders($mapping_id, $store_id)
    {
        $magentoPlaceholderarray = [];
        $emarsysEventMappingColl = $this->Emarsyseventmapping->create()->getCollection()->addFieldToFilter('id', $mapping_id);
        $magentoEventColl = $this->magentoEventsCollection->create()->addFieldToFilter('id', $emarsysEventMappingColl->getData()[0]['magento_event_id']);
        $value = $this->scopeConfigInterface->getValue($magentoEventColl->getData()[0]['config_path'], 'default', 0);
        if ($value == "") {
            return '';
        }
        if (is_numeric($value)) {
            $emailTemplateModelColl = $this->emailTemplateModel->create()->getCollection()->addFieldToFilter('template_id', $value);
            $emailText = $emailTemplateModelColl->getData()[0]['template_text'];
        } else {
            $template = $this->emailTemplate->initTemplate('id');
            $templateId = $value;
            $template->setForcedArea($templateId);
            $template->loadDefault($templateId);

            $emailText = $template->getData()['template_text'];
        }

        $array = [];
        $i = 0;
        while ($variable = $this->substringBetween($emailText)) {
            $emailText = str_replace($variable, '', $emailText);
            if (!strstr($variable, '{{trans')) {
                $emarsysVariable = $this->getPlacheloderName($variable);
            }
            $emarsysVariable = $this->getPlacheloderName($variable);



            if (strstr($variable, '{{trans')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlacheloderName($variable);
            }


            if (!empty($emarsysVariable)) {
                $array[$i]["event_mapping_id"] = $mapping_id;
                $array[$i]["magento_placeholder_name"] = $variable;
                $array[$i]["emarsys_placeholder_name"] = $emarsysVariable;
                $array[$i]["store_id"] = $store_id;
                $i++;
            }
                $emarsysVariable = "";
        }

        foreach ($array as $key => $value) {
            if (in_array($value['magento_placeholder_name'], $magentoPlaceholderarray)) {
                continue;
            }
            $placeholderModel = $this->emarsysEventPlaceholderMappingFactory->create();
            $placeholderModel->setEventMappingId($value['event_mapping_id']);
            $placeholderModel->setMagentoPlaceholderName($value['magento_placeholder_name']);
            $placeholderModel->setEmarsysPlaceholderName($value['emarsys_placeholder_name']);
            $placeholderModel->setStoreId($value['store_id']);
            $magentoPlaceholderarray[] =$value['magento_placeholder_name'];
            $placeholderModel->save();
        }
        return 'success';
    }

    public function refreshPlaceholders($mapping_id, $store_id)
    {

        $emarsysEventMappingCollFromDbArray = [];
        $emarsysEventMappingCollFromDb = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection()->addFieldToFilter('event_mapping_id', $mapping_id);
        foreach ($emarsysEventMappingCollFromDb as $_emarsysEventMappingCollFromDb) {
            $emarsysEventMappingCollFromDbArray[$_emarsysEventMappingCollFromDb['id']] = $_emarsysEventMappingCollFromDb['magento_placeholder_name'];
        }

        $emarsysEventMappingColl = $this->Emarsyseventmapping->create()->getCollection()->addFieldToFilter('id', $mapping_id);
        $magentoEventColl = $this->magentoEventsCollection->create()->addFieldToFilter('id', $emarsysEventMappingColl->getData()[0]['magento_event_id']);


        $value = $this->scopeConfigInterface->getValue($magentoEventColl->getData()[0]['config_path'], 'default', 0);
        if (is_numeric($value)) {
            $emailTemplateModelColl = $this->emailTemplateModel->create()->getCollection()->addFieldToFilter('template_id', $value);
            $emailText = $emailTemplateModelColl->getData()[0]['template_text'];
        } else {
            $template = $this->emailTemplate->initTemplate('id');
            $templateId = $value;
            $template->setForcedArea($templateId);
            $template->loadDefault($templateId);

            $emailText = $template->getData()['template_text'];
        }


        $templatePlaceholderArray = [];
        $array = [];
        $i = 0;
        while ($variable = $this->substringBetween($emailText)) {
            $emailText = str_replace($variable, '', $emailText);
            if (!strstr($variable, '{{trans')) {
                $emarsysVariable = $this->getPlacheloderName($variable);
            }
            $emarsysVariable = $this->getPlacheloderName($variable);

            // if (1)
            // {

            if (strstr($variable, '{{trans')) {
                $variable = $this->substringBetweenTransVar($variable);
                $emarsysVariable = $this->getPlacheloderName($variable);
            }


            if (!empty($emarsysVariable)) {
                $array[$i]["event_mapping_id"] = $mapping_id;
                $array[$i]["magento_placeholder_name"] = $variable;
                $array[$i]["emarsys_placeholder_name"] = $emarsysVariable;
                $array[$i]["store_id"] = $store_id;
                $i++;
            }
            $emarsysVariable = "";
            // }
        }


        $deleteFromDbArray = array_diff($emarsysEventMappingCollFromDbArray, $templatePlaceholderArray);
        foreach ($deleteFromDbArray as $key => $_deleteFromDbArray) {
            $placeholderModel = $this->emarsysEventPlaceholderMappingFactory->create()->load($key);
            $placeholderModel->delete();
        }
        $insertNewFromTemplate = array_diff($templatePlaceholderArray, $emarsysEventMappingCollFromDbArray);
        foreach ($insertNewFromTemplate as $key => $_insertNewFromTemplate) {
            $placeholderModel = $this->emarsysEventPlaceholderMappingFactory->create();
            $placeholderModel->setEventMappingId($mapping_id);
            $placeholderModel->setMagentoPlaceholderName($_insertNewFromTemplate);
            $placeholderModel->setEmarsysPlaceholderName($key);
            $placeholderModel->setStoreId($store_id);
            $placeholderModel->save();
        }
    }


    public function substringBetween($haystack, $start = "{{", $end = "}}")
    {
        try {
            if (strpos($haystack, $start) === false || strpos($haystack, $end) === false) {
                return false;
            } else {
                $start_position = strpos($haystack, $start) + strlen($start);
                $end_position = strpos($haystack, $end);
                $string = substr($haystack, $start_position, $end_position - $start_position);
                return $start . $string . $end;
            }
        } catch (Exception $e) {
            //  Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }


    public function substringBetweenTransVar($haystack, $start = "=$", $end = "}}")
    {
        try {
            if (strpos($haystack, $start) === false || strpos($haystack, $end) === false) {
                return false;
            } else {
                $start_position = strpos($haystack, $start) + strlen($start);
                $end_position = strpos($haystack, $end);
                $string = substr($haystack, $start_position, $end_position - $start_position);
                return "{{var " . $string . "}}";
            }
        } catch (Exception $e) {
            //Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    public function getPlacheloderName($variable = '')
    {
        try {
            if (empty($variable)) {
                return;
            }

            $skipVars = ["{{/if}}", "{{/depend}}", "{{else}}", "{{var non_inline_styles}}"];
            if (in_array($variable, $skipVars)) {
                return;
            }

            $skipMatchingVars = ["template", "inlinecss"];
            foreach ($skipMatchingVars as $skipMatchingVar) {
                if (strstr($variable, $skipMatchingVar)) {
                    return;
                }
            }

            $replaceWords = [];
            $replaceWords[] = ['find' => "var ", 'replace' => ''];
            $replaceWords[] = ['find' => " ", 'replace' => '_'];
            $replaceWords[] = ['find' => ".get", 'replace' => '_'];
            $replaceWords[] = ['find' => ".", 'replace' => '_'];
            $replaceWords[] = ['find' => "{{", 'replace' => ''];
            $replaceWords[] = ['find' => "}}", 'replace' => ''];
            $replaceWords[] = ['find' => "()", 'replace' => ''];
            $replaceWords[] = ['find' => "('", 'replace' => '_'];
            $replaceWords[] = ['find' => "(", 'replace' => '_'];
            $replaceWords[] = ['find' => "')", 'replace' => ''];
            $replaceWords[] = ['find' => ")", 'replace' => ''];
            $replaceWords[] = ['find' => '="', 'replace' => '_'];
            $replaceWords[] = ['find' => '"_', 'replace' => '_'];
            $replaceWords[] = ['find' => '"', 'replace' => '_'];
            $replaceWords[] = ['find' => '=$', 'replace' => '_'];
            $replaceWords[] = ['find' => '|', 'replace' => '_'];
            $replaceWords[] = ['find' => '/', 'replace' => '_'];
            $replaceWords[] = ['find' => '=', 'replace' => '_'];
            $replaceWords[] = ['find' => 'trans', 'replace' => ''];
            $replaceWords[] = ['find' => '%', 'replace' => '_'];
            $replaceWords[] = ['find' => '$', 'replace' => '_'];
            $replaceWords[] = ['find' => ',', 'replace' => '_'];
            $replaceWords[] = ['find' => '[', 'replace' => '_'];
            $replaceWords[] = ['find' => ']', 'replace' => '_'];
            $replaceWords[] = ['find' => ':', 'replace' => '_'];
            $replaceWords[] = ['find' => '\'', 'replace' => '_'];
            $replaceWords[] = ['find' => '__', 'replace' => '_'];
            $replaceWords[] = ['find' => '___', 'replace' => '_'];
            $replaceWords[] = ['find' => '____', 'replace' => '_'];
            $replaceWords[] = ['find' => '_____', 'replace' => '_'];
            $replaceWords[] = ['find' => '__', 'replace' => '_'];
            $emarsysVariable = strtolower($variable);

            foreach ($replaceWords as $replaceWord) {
                $emarsysVariable = str_replace($replaceWord['find'], $replaceWord['replace'], $emarsysVariable);
            }

            return trim(trim($emarsysVariable, "_"));
        } catch (\Exception $e) {
            $storeId = $this->_storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'getPlacheloderName');
        }
    }


    public function emarsysDefaultPlaceholders()
    {
        try {
            $order['product_purchases']['unitary_price_exc_tax'] = strtoupper("unitary_price_exc_tax");
            $order['product_purchases']['unitary_price_inc_tax'] = strtoupper("unitary_price_inc_tax");
            $order['product_purchases']['unitary_tax_amount'] = strtoupper("unitary_tax_amount");
            $order['product_purchases']['line_total_price_exc_tax'] = strtoupper("line_total_price_exc_tax");
            $order['product_purchases']['line_total_tax_amount'] = strtoupper("line_total_tax_amount");
            $order['product_purchases']['unitary_price_inc_tax'] = strtoupper("unitary_price_inc_tax");
            $order['product_purchases']['product_id'] = strtoupper("product_id");
            $order['product_purchases']['product_type'] = strtoupper("product_type");
            $order['product_purchases']['base_original_price'] = strtoupper("base_original_price");
            $order['product_purchases']['sku'] = strtoupper("sku");
            $order['product_purchases']['product_name'] = strtoupper("product_name");
            $order['product_purchases']['product_weight'] = strtoupper("product_weight");
            $order['product_purchases']['qty_ordered'] = strtoupper("qty_ordered");
            $order['product_purchases']['original_price'] = strtoupper("original_price");
            $order['product_purchases']['price'] = strtoupper("price");
            $order['product_purchases']['base_price'] = strtoupper("base_price");
            $order['product_purchases']['tax_percent'] = strtoupper("tax_percent");
            $order['product_purchases']['tax_amount'] = strtoupper("tax_amount");
            $order['product_purchases']['discount_amount'] = strtoupper("discount_amount");
            $order['product_purchases']['price_line_total'] = strtoupper("price_line_total");
            $order['product_purchases']['_external_image_url'] = strtoupper("_external_image_url");
            $order['product_purchases']['_url'] = strtoupper("_url");
            $order['product_purchases']['_url_name'] = strtoupper("_url_name");
            $order['product_purchases']['product_description'] = strtoupper("product_description");
            $order['product_purchases']['short_description'] = strtoupper("short_description");
            $order['product_purchases']['additional_data'] = strtoupper("additional_data");
            $order['product_purchases']['full_options'] = strtoupper("full_options");

            $attributesColl = $this->magentoProductAttributeColl->create();

            foreach ($attributesColl as $attribute) {
                if ($attribute['attribute_code'] == "gallery") {
                    continue;
                }
                $order['product_purchases']['attribute_' . $attribute['attribute_code']] = strtoupper('attribute_' . $attribute['attribute_code']);
            }

            return $order;
        } catch (\Exception $e) {
            $storeId = $this->_storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog(htmlentities($e->getMessage()),$storeId,'emarsysDefaultPlaceholders');
        }
    }


    public function insertFirstimeHeaderMappingPlaceholders($mapping_id, $store_id)
    {
        $headerPlaceholderArray = [];
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create()->addFieldToFilter('config_path', 'design/email/header_template');
            $emarsysEventMappingColl = $this->Emarsyseventmapping->create()->getCollection()->addFieldToFilter('magento_event_id', $magentoEventsCollection->getData()[0]['id']);
            $emarsysEventMappingColl->addFieldToFilter('store_id', $store_id);
            return $this->insertFirstimeMappingPlaceholders($emarsysEventMappingColl->getFirstItem()->getMagentoEventId(), $store_id);
        } catch (Exception $e) {
        }
    }


    public function insertFirstimeFooterMappingPlaceholders($mapping_id, $store_id)
    {
        $headerPlaceholderArray = [];
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create()->addFieldToFilter('config_path', 'design/email/footer_template');
            $emarsysEventMappingColl = $this->Emarsyseventmapping->create()->getCollection()->addFieldToFilter('magento_event_id', $magentoEventsCollection->getData()[0]['id']);
            $emarsysEventMappingColl->addFieldToFilter('store_id', $store_id);
            return $this->insertFirstimeMappingPlaceholders($emarsysEventMappingColl->getFirstItem()->getMagentoEventId(), $store_id);
        } catch (Exception $e) {
        }
    }


    public function emarsysHeaderPlaceholders($mapping_id, $store_id)
    {

        $headerPlaceholderArray = [];
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create()->addFieldToFilter('config_path', 'design/email/header_template');
            $emarsysEventMappingColl = $this->Emarsyseventmapping->create()->getCollection()->addFieldToFilter('magento_event_id', $magentoEventsCollection->getData()[0]['id']);
            $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection()->addFieldToFilter('event_mapping_id', $emarsysEventMappingColl->getData()[0]['id']);
            if (count($emarsysEventPlaceholderMappingColl->getData())) {
                foreach ($emarsysEventPlaceholderMappingColl->getData() as $_emarsysEventPlaceholderMappingColl) {
                    $headerPlaceholderArray[$_emarsysEventPlaceholderMappingColl['emarsys_placeholder_name']] = $_emarsysEventPlaceholderMappingColl['magento_placeholder_name'];
                }
            }
            return $headerPlaceholderArray;
        } catch (Exception $e) {
        }

        return $headerPlaceholderArray;
    }


    public function emarsysFooterPlaceholders($mapping_id, $store_id)
    {
        $footerPlaceholderArray = [];
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create()->addFieldToFilter('config_path', 'design/email/footer_template');
            $emarsysEventMappingColl = $this->Emarsyseventmapping->create()->getCollection()->addFieldToFilter('magento_event_id', $magentoEventsCollection->getData()[0]['id']);
            $emarsysEventPlaceholderMappingColl = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection()->addFieldToFilter('event_mapping_id', $emarsysEventMappingColl->getData()[0]['id']);
            if (count($emarsysEventPlaceholderMappingColl->getData())) {
                foreach ($emarsysEventPlaceholderMappingColl->getData() as $_emarsysEventPlaceholderMappingColl) {
                    $footerPlaceholderArray[$_emarsysEventPlaceholderMappingColl['emarsys_placeholder_name']] = $_emarsysEventPlaceholderMappingColl['magento_placeholder_name'];
                }
            }
            return $footerPlaceholderArray;
        } catch (Exception $e) {
        }
        return $footerPlaceholderArray;
    }


    public function getCheckSmartInsight($websiteId)
    {
        $smartInsight = 'Disable';

        if($websiteId){
            if($this->scopeConfigInterface->getValue('smart_insight/smart_insight/smartinsight_enabled', 'websites', $websiteId)){
                $smartInsight = 'Enable';
            }
        }else{
            if($this->scopeConfigInterface->getValue('smart_insight/smart_insight/smartinsight_enabled')){
                $smartInsight = 'Enable';
            };
        }
        return $smartInsight;
    }


    public function getRealTimeSync($websiteId)
    {
        $realTimeSync = 'Disable';

        if($websiteId){
            if($this->scopeConfigInterface->getValue('contacts_synchronization/emarsys_emarsys/realtime_sync', 'websites', $websiteId)){
                $realTimeSync = 'Enable';
            }
        }else{
            if($this->scopeConfigInterface->getValue('contacts_synchronization/emarsys_emarsys/realtime_sync', 'websites', $websiteId)){
                $realTimeSync = 'Enable';
            }
        }
        return $realTimeSync;
    }

    public function getEmarsysConnectionSetting($websiteId)
    {
        $emarsys = 'Disable';
        if($websiteId) {
            if($this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/enable', 'websites', $websiteId)){
                $emarsys = 'Enable';
            }
        } else {
            if($this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/enable')){
               $emarsys = 'Enable';
            }
         }

        return $emarsys;
    }


    public function getMagentoEventId($templateId, $storeScope)
    {
        $event_id = "";
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create();
            foreach ($magentoEventsCollection as $magentoEvent) {
                if ($this->scopeConfigInterface->getValue($magentoEvent->getConfigPath(), $storeScope) == $templateId) {
                    $event_id = $magentoEvent->getId();
                }
            }
        } catch (Exception $e) {
        }
        return $event_id;
    }

    public function getEmarsysEventMappingId($magentoEventId)
    {
        $emarsysEventMappingId = "";
        try {
            $emarsysEventsMappingCollection = $this->Emarsyseventmapping->create()->getCollection()->addFieldToFilter('magento_event_id', $magentoEventId);
            if (count($emarsysEventsMappingCollection->getData())) {
                $emarsysEventMappingId = $emarsysEventsMappingCollection->getData()[0]['id'];
            }

            return $emarsysEventMappingId;
        } catch (Exception $e) {
        }
        return $emarsysEventMappingId;
    }

    public function getEmarsysEventApiId($magentoEventId)
    {
        $emarsysEventApiId = "";
        try {
            $emarsysEventsMappingCollection = $this->Emarsyseventmapping->create()->getCollection()->addFieldToFilter('magento_event_id', $magentoEventId);
            $emarsysEventsColl = $this->EmarsyseventsModelFactory->create()->getCollection()->addFieldToFilter('id', $emarsysEventsMappingCollection->getData()[0]['emarsys_event_id']);
            if (count($emarsysEventsColl->getData())) {
                $emarsysEventApiId = $emarsysEventsColl->getData()[0]['event_id'];
            }
            return $emarsysEventApiId;
        } catch (Exception $e) {
        }
        return $emarsysEventApiId;
    }

    protected function getTemplateInstance()
    {
        return $this->templateFactory->create();
    }

    public function getPlaceHolders($emarsysEventMappingId)
    {
        $placeHolders = [];
        try {
            $emarsysPlaceholderCollection = $this->emarsysEventPlaceholderMappingFactory->create()->getCollection()->addFieldToFilter('event_mapping_id', $emarsysEventMappingId);
            $variables = [];
            if (count($emarsysPlaceholderCollection->getData())) {
                foreach ($emarsysPlaceholderCollection->getData() as $value) {
                    $variables[$value['emarsys_placeholder_name']] = $value['magento_placeholder_name'];
                }
                return $variables;
            }
        } catch (Exception $e) {
        }
        return $placeHolders;
    }


    public function getConfigValue($path, $storeCode, $storeId)
    {
        if ($storeCode == "default") {
            return $this->scopeConfigInterface->getValue($path, 'default', 0);
        } else {
            return $this->scopeConfigInterface->getValue($path, $storeCode, $storeId);
        }
    }

    /**
     * @return string
     * Technical support email for error logs
     */
    public function logErrorSenderEmail()
    {
        $data = [
            'name' => 'Technical Support',
            'email' => 'support@kensium.com'
        ];
        return $data;
    }

    /**
     * @param $title
     * @param $errorMsg
     * To send error log Email
     */


    public function getFirstStoreId()
    {
        $firstStoreId = $this->_storeManager->getStore()->getId();
        $listOfStores = $this->storeCollection->create()->addFieldToFilter('store_id', ['neq' => 0]);
        if ($listOfStores) {
            $firstStoreId = $listOfStores->getFirstItem()->getStoreId();
        }
        return $firstStoreId;
    }

    public function getFirstStoreIdOfWebsite($websiteId)
    {
        $firstStoreId = $this->_storeManager->getStore()->getId();
        $listOfStores = $this->storeCollection->create()
                                ->addFieldToFilter('website_id', $websiteId)
                                ->addFieldToFilter('store_id', ['neq' => 0]);

        if ($listOfStores) {
            $firstStoreId = $listOfStores->getFirstItem()->getStoreId();
        }
        return $firstStoreId;
    }

    public function getMagentoEventIdAndPath($templateId, $storeScope)
    {
        $event_id = "";
        $configPath = '';
        try {
            $magentoEventsCollection = $this->magentoEventsCollection->create();
            foreach ($magentoEventsCollection as $magentoEvent) {
                if ($this->scopeConfigInterface->getValue($magentoEvent->getConfigPath(), $storeScope) == $templateId) {
                    $event_id = $magentoEvent->getId();
                    $configPath = $magentoEvent->getConfigPath();
                }
            }
        } catch (Exception $e) {
        }
        return [$event_id, $configPath];
    }

    public function getEmarsysNewsLetterEventId($emarsysEventApiID, $handle, $configPath)
    {
        if (in_array($configPath, ['newsletter/subscription/confirm_email_template', 'newsletter/subscription/success_email_template'])) {
            if ($handle == 'checkout_onepage_success') {
                $optInType = $this->scopeConfigInterface->getValue('opt_in/subscription_checkout_process/opt_in_strategy');  // return single / double opt-in
                if ($optInType == 'singleOptIn') {
                    $emarsysEventApiID = $this->scopeConfigInterface->getValue('opt_in/subscription_checkout_process/external_eventid_after_opt_in_confirmation');
                } elseif ($optInType == 'doubleOptIn') {
                    $emarsysEventApiID = $this->scopeConfigInterface->getValue('opt_in/subscription_checkout_process/external_eventid_double_opt_in');
                }
            } elseif ($handle == 'customer_account_createpost') {
                $optInType = $this->scopeConfigInterface->getValue('opt_in/subscription_customer_homepage/opt_in_strategy');  // return single / double opt-in
                if ($optInType == 'singleOptIn') {
                    $emarsysEventApiID = $this->scopeConfigInterface->getValue('opt_in/subscription_customer_homepage/external_eventid_after_opt_in_confirmation');
                } elseif ($optInType == 'doubleOptIn') {
                    $emarsysEventApiID = $this->scopeConfigInterface->getValue('opt_in/subscription_customer_homepage/external_eventid_double_opt_in');
                }
            } else { //$handle =='newsletter_subscriber_new'
                $optInType = $this->scopeConfigInterface->getValue('opt_in/subscription_newsletter_everypage/opt_in_strategy');  // return single / double opt-in
                if ($optInType == 'singleOptIn') {
                    $emarsysEventApiID = $this->scopeConfigInterface->getValue('opt_in/subscription_newsletter_everypage/external_eventid_after_opt_in_confirmation');
                } elseif ($optInType == 'doubleOptIn') {
                    $emarsysEventApiID = $this->scopeConfigInterface->getValue('opt_in/subscription_newsletter_everypage/external_eventid_double_opt_in');
                }
            }
        }
        return $emarsysEventApiID;
    }

    public function getDateTimeInLocalTimezone($dateTime = '')
    {

        $toTimezone = $this->timezone->getConfigTimezone();
        $returnDateTime = $this->timezone->date($dateTime);
        $returnDateTime->setTimezone(new \DateTimeZone($toTimezone));
        $returnDateTime = $returnDateTime->format('Y-m-d H:i:s');

        return $returnDateTime;
    }

    /**
     * @param null $websiteId
     * @return string
     * Checks whether emarsys is enabled or not.
     */
    public function isEmarsysEnabled($websiteId = null)
    {
        $emarsysEnabled = 'false';
        if ($websiteId) {
            $emarsysUserName = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_username', 'websites', $websiteId);
            $emarsysPassword = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_password', 'websites', $websiteId);
            $emarsysFlag = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/enable', 'websites', $websiteId);
            if ($emarsysUserName && $emarsysPassword && $emarsysFlag) {
                $emarsysEnabled = 'true';
            }
        } else {
            $emarsysUserName = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_username');
            $emarsysPassword = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/emarsys_api_password');
            $emarsysFlag = $this->scopeConfigInterface->getValue('emarsys_settings/emarsys_setting/enable');
            if ($emarsysUserName && $emarsysPassword && $emarsysFlag) {
                $emarsysEnabled = 'true';
            }
        }
        return $emarsysEnabled;
    }

    public function getEmarsysVersion(){
        return $this->moduleListInterface->getOne(self::MODULE_NAME)['setup_version'];
    }
}
