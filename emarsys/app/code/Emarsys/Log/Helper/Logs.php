<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Log
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Log\Helper;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;

class Logs extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Emarsys\Log\Model\CronScheduleFactory
     */
    protected $cronScheduleFactory;

    /**
     * @var null
     */
    protected $cronSchedule = null;

    /**
     * @var \Emarsys\Log\Model\CategoryFactory
     */
    protected $logsFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;
    /**
     * @var
     */
    protected $logsModel;
    /**
     * @var \Emarsys\Emarsys\Helper\Data
     */

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Emarsys\Log\Model\CronScheduleFactory $cronScheduleFactory
     * @param \Emarsys\Log\Model\LogsFactory $logsFactory
     * @param \Emarsys\Log\Model\Logs $logsModel
     * @param DateTime $date
     * @param ObjectManagerInterface $objectManagerInterface
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Log\Model\CronScheduleFactory $cronScheduleFactory,
        \Emarsys\Log\Model\LogsFactory $logsFactory,
        \Emarsys\Log\Model\Logs $logsModel,
        DateTime $date,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Registry $registry,
        ObjectManagerInterface $objectManagerInterface
    ) {
    
        $this->cronScheduleFactory = $cronScheduleFactory;
        $this->logsFactory = $logsFactory;
        $this->date = $date;
        $this->registry = $registry;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->_objectManager = $objectManagerInterface;
        $this->logsModel = $logsModel;
        parent::__construct($context);
    }

    /**
     * @param array $logsArray
     * @return string
     */
    public function manualLogs($logsArray = [], $exportCron = 0)
    {
        if (!$this->cronSchedule || $exportCron) {
            $this->cronSchedule = $this->cronScheduleFactory->create();
        }
        try {
            if (isset($logsArray['job_code'])) {
                $this->cronSchedule->setJobCode($logsArray['job_code']);
            }

            if (isset($logsArray['schedule_id'])) {
                $this->cronSchedule->setScheduleId($logsArray['schedule_id']);
            }

            if (isset($logsArray['status'])) {
                $this->cronSchedule->setStatus($logsArray['status']);
            }

            if (isset($logsArray['messages'])) {
                $this->cronSchedule->setMessages($logsArray['messages']);
            }

            if (isset($logsArray['created_at'])) {
                $this->cronSchedule->setCreatedAt($logsArray['created_at']);
            }

            if (isset($logsArray['executed_at'])) {
                $this->cronSchedule->setExecutedAt($logsArray['executed_at']);
            }

            if (isset($logsArray['finished_at'])) {
                $this->cronSchedule->setFinishedAt($logsArray['finished_at']);
            }

            if (isset($logsArray['run_mode'])) {
                $this->cronSchedule->setRunMode($logsArray['run_mode']);
            }

            if (isset($logsArray['auto_log'])) {
                $this->cronSchedule->setAutoLog($logsArray['auto_log']);
            }

            if (isset($logsArray['store_id'])) {
                $this->cronSchedule->setStoreId($logsArray['store_id']);
            }

            $this->cronSchedule->save();
            return $this->cronSchedule->getId();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /*
     * 
     */

    /**
     * function for updating latest saving manual log record in cron scheduler
     * @param type $logsArray
     * @return string
     */
    public function manualLogsUpdate($logsArray = [])
    {
        $collection = $this->cronScheduleFactory->create()->getCollection();
        $collection->addFieldToFilter('id', $logsArray['id']);
        $result = $collection->getFirstItem();

        if (null !== $result->getId()) {
            $collection = $this->cronScheduleFactory->create()->load($logsArray['id']);
            try {
                if (isset($logsArray['status'])) {
                    $collection->setStatus($logsArray['status']);
                }

                if (isset($logsArray['messages'])) {
                    $collection->setMessages($logsArray['messages']);
                }

                if (isset($logsArray['scheduled_at'])) {
                    $collection->setScheduledAt($logsArray['scheduled_at']);
                }

                if (isset($logsArray['executed_at'])) {
                    $collection->setExecutedAt($logsArray['executed_at']);
                }

                if (isset($logsArray['finished_at'])) {
                    $collection->setFinishedAt($logsArray['finished_at']);
                }

                $collection->save();
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        } else {
            return "Log Record not found";
        }
    }

    /**
     * For saving Logs
     * @param array $logsArray
     */
    public function logs($logsArray = [])
    {
        $currentDate = $this->date->date('Y-m-d H:i:s', time());
        $schedulerId = $logsArray['id'];

        $logsModel = $this->_objectManager->create('\Emarsys\Log\Model\Logs');
        $logsModel->setLogExecId($schedulerId)
            ->setCreatedAt($currentDate)
            ->setEmarsysInfo($logsArray['emarsys_info'])
            ->setDescription($logsArray['description'])
            ->setAction($logsArray['action'])
            ->setMessageType($logsArray['message_type'])
            ->setStoreId($logsArray['store_id'])
            ->setWebsiteId($logsArray['website_id'])
            ->setLogAction($logsArray['log_action']);

        $logsModel->save();

        $websiteId = $logsArray['website_id'];
        if ($websiteId == 0) {
            $scopeType = 'default';
        } else {
            $scopeType = 'websites';
        }

        $sendLogReport = $this->scopeConfigInterface->getValue('logs/log_setting/log_report', $scopeType, $websiteId);
        if ($sendLogReport == '' && $websiteId == 0) {
            $sendLogReport = $this->scopeConfigInterface->getValue('logs/log_setting/log_report');
        }
        if ($sendLogReport && $logsArray['message_type'] == 'Error') {
            if ($sendLogReport) {
                $title = ucfirst($logsArray['job_code'])." : Store - ".$logsArray['store_id'];
                $description = $logsArray['description'];
                $this->errorLogEmail($title, $description);
            }
        }
    }

    /**
     * 
     * @param type $title
     * @param type $errorMsg
     * @return array
     */
    public function errorLogEmail($title, $errorMsg)
    {
        $customerVar = 'emarsys_send_email';
        if ($this->registry->registry($customerVar) == 'sent') {
            return;
        }
        $this->registry->register($customerVar, 'sent');

        $sendLogReport = $this->scopeConfigInterface->getValue('logs/log_setting/log_report');
        $emailReceipient = $this->scopeConfigInterface->getValue('logs/log_setting/log_email_recipient');
        $explodedemailReceipient = explode(",", $emailReceipient);

        if ($sendLogReport == 1) {
            $templateOptions = [
                'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                'store' => $this->storeManager->getStore()->getId()];
            $templateVars = [
                'store' => $this->storeManager->getStore(),
                'sync_name' => $title,
                'message' => $errorMsg
            ];
            $from = [
                'email' => "support@kensium.com",
                'name' => "Support"
            ];
            $this->inlineTranslation->suspend();

            if (is_array($explodedemailReceipient)) {
                for ($i = 0; $i < count($explodedemailReceipient); $i++) {
                    $to = $explodedemailReceipient[$i];
                    $transport = $this->_transportBuilder->setTemplateIdentifier('error_log_email_template')
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
                    $transport->sendMessage();
                }
            }
            $this->inlineTranslation->resume();
        }
    }

}
