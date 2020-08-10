<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Schedular
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Scheduler;

use Emarsys\Emarsys\Block\Adminhtml\Scheduler\Renderer\StatusColor;
use Emarsys\Emarsys\Block\Adminhtml\Scheduler\Renderer\ViewButton;
use Exception;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Framework\App\Request\Http;
use Emarsys\Emarsys\Model\LogScheduleFactory;

class Grid extends Extended
{
    /**
     * @var Timezone
     */
    protected $timezone;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var LogScheduleFactory
     */
    protected $logScheduleFactory;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param Timezone $timezone
     * @param Http $request
     * @param LogScheduleFactory $logScheduleFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Timezone $timezone,
        Http $request,
        LogScheduleFactory $logScheduleFactory,
        $data = []
    ) {
        $this->timezone = $timezone;
        $this->request = $request;
        $this->logScheduleFactory = $logScheduleFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('LogsGrid');
        $this->setDefaultSort('frontend_label');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('frontend_label');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $filterCode = $this->getRequest()->getParam('filtercode');

        $storeId = $this->request->getParam('store');
        $collection = $this->logScheduleFactory->create()
            ->getCollection()
            ->setOrder('created_at', 'desc');

        if ($storeId) {
            $collection->addFieldToFilter('store_id', $storeId);
        }
        if ($filterCode != '') {
            $collection->addFieldToFilter('job_code', $filterCode);
        }
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            "job_code", [
                "header" => __("Code"),
                "align" => "left",
                'width' => '25',
                "index" => "job_code",
                'type' => 'options',
                'options' => [
                    'customer' => 'Customer',
                    'subscriber' => 'Subscriber',
                    'order' => 'Order',
                    'invoice' => 'Invoice',
                    'rma' => 'Rma',
                    'creditmemo' => 'Credit Memo',
                    'shipment' => 'Shipment',
                    'product' => 'Product',
                    'Customer Mapping' => 'Customer Mapping',
                    'Customer Filed Mapping' => 'Customer Filed Mapping',
                    'Order Mapping' => 'Order Mapping',
                    'Product Mapping' => 'Product Mapping',
                    'Event Mapping' => 'Event Mapping',
                    'transactional_mail' => 'Transactional Mail',
                    'Backgroud Time Based Optin Sync' => 'Background Time Based Optin Sync',
                    'Sync contact Export' => 'Sync contact Export',
                    'testconnection' => 'Test Connection',
                    'Exception' => 'Exception',
                ],
            ]
        );

        $this->addColumn(
            "created_at", [
                "header" => __("Created"),
                "align" => "left",
                "index" => "created_at",
                'width' => '150',
                'type' => 'timestamp',
                'frame_callback' => [$this, 'decorateTimeFrameCallBack'],
            ]
        );

        $this->addColumn(
            "executed_at", [
                "header" => __("Executed"),
                "align" => "left",
                "index" => "executed_at",
                'width' => '150',
                'type' => 'timestamp',
                'frame_callback' => [$this, 'decorateTimeFrameCallBack'],
            ]
        );

        $this->addColumn(
            "finished_at", [
                "header" => __("Finished"),
                "align" => "left",
                "index" => "finished_at",
                'width' => '150',
                'type' => 'timestamp',
                'frame_callback' => [$this, 'decorateTimeFrameCallBack'],
            ]
        );

        $this->addColumn(
            "run_mode", [
                "header" => __("Run Mode"),
                "align" => "left",
                "index" => "run_mode",
                'width' => '150',
                'type' => 'options',
                'options' => ['Automatic' => 'Automatic', 'Manual' => 'Manual'],
            ]
        );

        $this->addColumn(
            "auto_log", [
                "header" => __("Type"),
                "align" => "left",
                "index" => "auto_log",
                'width' => '150',
                'type' => 'options',
                'options' => ['Complete' => 'Complete', 'Individual' => 'Individual'],
            ]
        );

        $this->addColumn(
            "messages", [
                "header" => __("Messages"),
                "align" => "left",
                "index" => "messages",
                'width' => '150',
            ]
        );

        $this->addColumn(
            "status", [
                "header" => __("Status"),
                "align" => "center",
                "index" => "status",
                'width' => '150',
                'type' => 'options',
                'renderer' => StatusColor::class,
                'options' => [
                    'success' => 'success',
                    'error' => 'error',
                    'missed' => 'missed',
                    'running' => 'running',
                    'notice' => 'notice',
                    'started' => 'started',
                ],
            ]
        );

        $this->addColumn(
            "id", [
                "header" => __("Details"),
                "align" => "left",
                "index" => "id",
                'renderer' => ViewButton::class,
                'width' => '150',
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param  $value
     * @return string
     */
    public function decorateTimeFrameCallBack($value)
    {
        if ($value && $value != '0000-00-00 00:00:00') {
            return $this->timezone->formatDateTime($value, 2);
        } else {
            return '';
        }
    }
}
