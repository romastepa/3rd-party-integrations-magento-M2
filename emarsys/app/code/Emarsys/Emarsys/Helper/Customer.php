<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Emarsys Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Webapi\Soap;
use SoapClient;
use SoapFault;
use Magento\Framework\Locale\ListsInterface;
use Magento\Config\Model\ResourceModel\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Emarsys\Emarsys\Helper\Data;

class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $dataHelper;

    public function __construct(
        Context $context,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        Data $dataHelper
    ) {
    
        ini_set('default_socket_timeout', 1000);
        $this->context = $context;
        $this->dataHelper = $dataHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->logger = $context->getLogger();
    }

    /**
     * @throws \Zend_Json_Exception
     */
    public function getEmarsysCustomerSchema($storeId)
    {
        try {
            $this->dataHelper->getEmarsysAPIDetails($storeId);
            $response = $this->dataHelper->send('GET', 'field/translate/en');
            $jsonDecode = \Zend_Json::decode($response);
            return $jsonDecode;
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(htmlentities($e->getMessage()),$storeId,'getEmarsysCustomerSchema');
            return false;
        }
    }
}
