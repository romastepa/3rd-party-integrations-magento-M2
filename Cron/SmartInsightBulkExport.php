<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Order as EmarsysOrderModel;
use Emarsys\Emarsys\Helper\Cron as EmarsysCronHelper;
use Emarsys\Emarsys\Model\Logs;

/**
 * Class SmartInsightBulkExport
 * @package Emarsys\Emarsys\Cron
 */
class SmartInsightBulkExport
{
    /**
     * @var EmarsysCronHelper
     */
    protected $cronHelper;

    /**
     * @var EmarsysOrderModel
     */
    protected $emarsysOrderModel;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * SmartInsightBulkExport constructor.
     *
     * @param EmarsysCronHelper $cronHelper
     * @param EmarsysOrderModel $order
     * @param Logs $emarsysLogs
     */
    public function __construct(
        EmarsysCronHelper $cronHelper,
        EmarsysOrderModel $order,
        Logs $emarsysLogs
    ) {
        $this->cronHelper = $cronHelper;
        $this->emarsysOrderModel =  $order;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $currentCronInfo = $this->cronHelper->getCurrentCronInformation(
                \Emarsys\Emarsys\Helper\Cron::CRON_JOB_SI_BULK_EXPORT
            );

            if ($currentCronInfo) {
                $data = \Zend_Json::decode($currentCronInfo->getParams());

                $storeId = isset($data['storeId']) ? $data['storeId'] : 0;
                $fromDate = isset($data['fromDate']) ? $data['fromDate'] : null;
                $toDate = isset($data['toDate']) ? $data['toDate'] : null;

                $this->emarsysOrderModel->syncOrders(
                    $storeId,
                    \Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_MANUAL,
                    $fromDate,
                    $toDate
                );
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                'SmartInsightBulkExport',
                $e->getMessage(),
                0,
                'SmartInsightBulkExport::execute()'
            );
        }
    }
}
