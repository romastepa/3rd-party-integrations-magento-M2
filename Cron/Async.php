<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\{
    Helper\Data\Proxy as EmarsysHelper,
    Helper\Logs\Proxy as EmarsysLogsHelper,
    Model\ResourceModel\Async\CollectionFactory,
    Model\Api\Api as EmarsysModelApiApi,
    Helper\Cron as EmarsysCronHelper
};

use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class Async
 * @package Emarsys\Emarsys\Cron
 */
class Async
{
    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var EmarsysLogsHelper
     */
    protected $logsHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var EmarsysModelApiApi
     */
    protected $api;

    /**
     * @var CollectionFactory
     */
    protected $asyncCollection;

    /**
     * Async constructor.
     *
     * @param EmarsysCronHelper $cronHelper
     * @param EmarsysHelper $emarsysHelper
     * @param EmarsysLogsHelper $logsHelper
     * @param DateTime $date
     * @param EmarsysModelApiApi $api
     * @param CollectionFactory $asyncCollection
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        EmarsysHelper $emarsysHelper,
        EmarsysLogsHelper $logsHelper,
        DateTime $date,
        EmarsysModelApiApi $api,
        CollectionFactory $asyncCollection
    ) {
        $this->cronHelper = $cronHelper;
        $this->emarsysHelper = $emarsysHelper;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->api = $api;
        $this->asyncCollection = $asyncCollection;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                \Emarsys\Emarsys\Helper\Cron::CRON_JOB_CATALOG_BULK_EXPORT
            );

            if (!$currentCronInfo) {
                return;
            }

            $collection = $this->asyncCollection->create();

            /** @var \Emarsys\Emarsys\Model\Async $item */
            foreach ($collection as $item) {
                $this->api->setWebsiteId($item->getWebsiteId());

                if ($item->getEndpoint() == 'contact/?create_if_not_exists=1') {
                    $response = $this->api->createContactInEmarsys(\Zend_Json::decode($item->getRequestBody()));
                } else {
                    list($buildRequest, $requestBody) = \Zend_Json::decode($item->getRequestBody());
                    $response = $this->api->createContactInEmarsys($buildRequest);
                    if (isset($response['status']) && ($response['status'] == 200)
                        || ($response['status'] == 400 && isset($response['body']['replyCode']) && $response['body']['replyCode'] == 2009)
                    ) {
                        //contact synced to emarsys successfully
                        $response = $this->api->sendRequest('POST', $item->getEndpoint(), $requestBody);
                    }
                }
                if (isset($response['status']) && ($response['status'] = 200)) {
                    $item->delete();
                }
            }
        } catch (\Exception $e) {
            $this->logsHelper->addErrorLog(
                'ProductBulkExport',
                $e->getMessage(),
                0,
                'ProductBulkExport::execute()'
            );
        }
    }
}
