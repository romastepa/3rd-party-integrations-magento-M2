<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Emarsys Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Webapi\Soap;
use SoapClient;
use SoapFault;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\Locale\ListsInterface;
use Magento\Config\Model\ResourceModel\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Emarsys\Emarsys\Helper\Data;

class Event extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * 
     * @param Data $dataHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Event $resourceModelEvent
     * @param Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Log\Model\Logs $emarsysLogs
     * @param \Magento\AdminNotification\Model\InboxFactory $adminNotification
     * @param \Emarsys\Emarsys\Model\EmarsyseventsFactory $emarsysEvents
     */
    public function __construct(
        Data $dataHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Event $resourceModelEvent,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Magento\AdminNotification\Model\InboxFactory $adminNotification,
        \Emarsys\Emarsys\Model\EmarsyseventsFactory $emarsysEvents
    ) {
    
        ini_set('default_socket_timeout', 1000);
        $this->logger = $context->getLogger();
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->resourceModelEvent = $resourceModelEvent;
        $this->adminNotification = $adminNotification;
        $this->emarsysEvents = $emarsysEvents;
    }

    /**
     * @throws \Zend_Json_\Exception
     */
    public function getEventSchema()
    {
        try {
            $response = $this->dataHelper->send('GET', 'event');
            $jsonDecode = \Zend_Json::decode($response);
            return $jsonDecode;
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'getEventSchema');
            return false;
        }
    }

    /**
     * 
     * @return boolean
     */
    public function getEventTemplateSchema()
    {
        try {
            $response = $this->dataHelper->send('GET', 'email/templates');
            $jsonDecode = \Zend_Json::decode($response);
            return $jsonDecode;
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'getEventTemplateSchema');
            return false;
        }
    }


    /**
     * 
     * @return boolean
     */
    public function saveEmarsysEventSchemaNotification()
    {
        try {
            $adminNotiColl = $this->adminNotification->create();
            $adminNotiColl->setSeverity(4);
            $adminNotiColl->setTitle('Emarsys Events Updates');
            $adminNotiColl->setDescription('Emarsys events has been update, Please update the emarsys event schema');
            $adminNotiColl->save();
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'saveEmarsysEventSchemaNotification');
            return false;
        }
    }


    /**
     * 
     * @return boolean
     */
    public function getLocalEmarsysEvents()
    {
        try {
            $emarsysLocalIds = [];
            $emarsysContactFields = $this->resourceModelEvent->getEmarsysEvents(1);

            foreach ($emarsysContactFields as $_emarsysContactField) {
                $emarsysLocalIds[] = $_emarsysContactField['event_id'];
            }
            return $emarsysLocalIds;
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'getLocalEmarsysEvents');
            return false;
        }
    }

    /**
     * 
     * @return boolean
     */
    public function getEmar()
    {
        try {
            $adminNotiColl = $this->emarsysEvents->create()->getCollection();
            print_r($adminNotiColl->getData());
            foreach ($adminNotiColl as $_adminNotiColl) {
                print_r($_adminNotiColl->getData());
            }
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'getEmar');
            return false;
        }
    }
}
