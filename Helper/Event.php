<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Helper;

use Magento\{
    Framework\App\Helper\AbstractHelper,
    Framework\App\Helper\Context,
    Store\Model\StoreManagerInterface,
    AdminNotification\Model\InboxFactory
};
use Emarsys\Emarsys\{
    Model\ResourceModel\Event as EmarsysResourceModelEvent,
    Model\Logs as EmarsysModelLogs,
    Model\EmarsyseventsFactory
};

/**
 * Class Event
 * @package Emarsys\Emarsys\Helper
 */
class Event extends AbstractHelper
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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Event constructor.
     * @param Data $dataHelper
     * @param EmarsysResourceModelEvent $resourceModelEvent
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param EmarsysModelLogs $emarsysLogs
     * @param InboxFactory $adminNotification
     * @param EmarsyseventsFactory $emarsysEvents
     */
    public function __construct(
        Data $dataHelper,
        EmarsysResourceModelEvent $resourceModelEvent,
        Context $context,
        StoreManagerInterface $storeManager,
        EmarsysModelLogs $emarsysLogs,
        InboxFactory $adminNotification,
        EmarsyseventsFactory $emarsysEvents
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
     * @return bool|mixed
     */
    public function getEventSchema()
    {
        try {
            $response = $this->dataHelper->send('GET', 'event');
            $jsonDecode = \Zend_Json::decode($response);
            return $jsonDecode;
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getEventSchema');
            return false;
        }
    }

    /**
     * @return bool|mixed
     */
    public function getEventTemplateSchema()
    {
        try {
            $response = $this->dataHelper->send('GET', 'email/templates');
            $jsonDecode = \Zend_Json::decode($response);
            return $jsonDecode;
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getEventTemplateSchema');
            return false;
        }
    }

    /**
     * @return bool
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
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'saveEmarsysEventSchemaNotification');
            return false;
        }
        return true;
    }

    /**
     * @param $websiteId
     * @return array|bool
     */
    public function getLocalEmarsysEvents($websiteId)
    {
        $emarsysLocalIds = [];
        try {
            $defaultStore = $this->storeManager->getWebsite($websiteId)->getDefaultStore();
            if ($defaultStore) {
                $defaultStore = $defaultStore->getId();
            } else {
                throw new \Exception(__('There is no default store selected for website id %1', $websiteId));
            }
            $emarsysContactFields = $this->resourceModelEvent->getEmarsysEvents($defaultStore);

            foreach ($emarsysContactFields as $_emarsysContactField) {
                $emarsysLocalIds[] = $_emarsysContactField['event_id'];
            }
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getLocalEmarsysEvents');
        }
        return $emarsysLocalIds;
    }

    /**
     * @return bool
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
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'getEmar');
            return false;
        }
    }
}
