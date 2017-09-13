<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Support\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;


    const Version = '2.1';

    /**
     * @param Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
    
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->emarsysHelper = $emarsysHelper;
        $this->messageManager = $messageManager;
        $this->scopeConfigInterface = $context->getScopeConfig();
    }

    /**
     * @return array
     */
    public function getRequirementsInfo()
    {
        $clientPhpData = $this->getPhpSettings();

        if ($clientPhpData['phpinfo']['soap'] ['Soap Client']=='enabled') :
            $soapenable='Yes';
        else :
            $soapenable='No';
        endif;
        if ($clientPhpData['phpinfo']['curl'] ['cURL support']=='enabled') :
            $curlenable='Yes';
        else :
            $curlenable='No';
        endif;

        $requirements =  [

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
                    'value' =>(int)$clientPhpData['memory_limit'] . ' MB',
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
                    'value' => $this->emarsysHelper->getVersion(),
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
                    'value' => $this->emarsysHelper->getEmarsysVersion(),
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
                    'value' =>$curlenable,
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
                    'value' =>$soapenable,
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

    /**
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

    /**
     * @return array|mixed
     */
    public function getPhpInfoArray()
    {
        try {
            ob_start();
            phpinfo(INFO_ALL);

            $pi = preg_replace(
                [
                    '#^.*<body>(.*)</body>.*$#m', '#<h2>PHP License</h2>.*$#ms',
                    '#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
                    "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                    '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                    '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                    '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                    "# +#", '#<tr>#', '#</tr>#'],
                [
                    '$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
                    '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
                    "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
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
                    $pi[$n][$m[1]]=(!isset($m[3]) || $m[2]==$m[3])?$m[2]:array_slice($m, 2);
                }
            }
        } catch (\Exception $exception) {
            return [];
        }

        return $pi;
    }

    /**
     * @param bool $inMegabytes
     * @return int|string
     */
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

    /**
     * @return string
     */
    public function getPhpVersion()
    {
        $phpVersion = @phpversion();
        $array = explode("-", $phpVersion);
        return $array[0];
    }

    /**
     * @return string
     */
    public function getPhpApiName()
    {
        return @php_sapi_name();
    }

    /**
     * @param bool $asArray
     * @return array|string
     */
    public function getVersion($asArray = false)
    {
        $versionString = self::Version;//\Magento\Framework\AppInterface::VERSION;
        return $asArray ? explode('.', $versionString) : $versionString;
    }

    /**
     * @return string
     */
    public function getRevision()
    {
        return 'undefined';
    }

    /**
     * @return bool
     */
    public function isGoEdition()
    {
        return class_exists('Saas_Db', false);
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return str_replace('index.php/', '', $baseUrl);
    }
}
