<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Model\Api\Contact;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Model\Logs as EmarsysModelLogs;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;

class CustomerSyncQueue
{
    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var Contact
     */
    protected $contactModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var EmarsysModelLogs
     */
    protected $emarsysLogs;

    /**
     * CustomerSyncQueue constructor.
     *
     * @param EmarsysCronHelper $cronHelper
     * @param Contact $contactModel
     * @param StoreManagerInterface $storeManagerInterface
     * @param EmarsysModelLogs $emarsysLogs
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        Contact $contactModel,
        StoreManagerInterface $storeManagerInterface,
        EmarsysModelLogs $emarsysLogs
    ) {
        $this->cronHelper = $cronHelper;
        $this->contactModel = $contactModel;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            $stores = $this->storeManagerInterface->getStores(false);

            foreach ($stores as $store) {
                if (!$store->getConfig(EmarsysHelper::XPATH_EMARSYS_ENABLED)
                    || !$store->getConfig(EmarsysHelper::XPATH_EMARSYS_ENABLE_CONTACT_FEED)
                ) {
                    continue;
                }
                $storeId = $store->getStoreId();
                $websiteId = $store->getWebsiteId();

                $data = [
                    'storeId' => $storeId,
                    'website' => $websiteId,
                ];

                $this->contactModel->syncFullContactUsingApi(
                    EmarsysCronHelper::CRON_JOB_CUSTOMER_SYNC_QUEUE,
                    $data
                );
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                \Emarsys\Emarsys\Helper\Data::LOG_MESSAGE_CUSTOMER,
                $e->getMessage(),
                0,
                'CustomerSyncQueue::execute()'
            );
        }
    }
}
