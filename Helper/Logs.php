<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Helper;

use Emarsys\Emarsys\Model\LogScheduleFactory;
use Emarsys\Emarsys\Model\LogsFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;

class Logs extends AbstractHelper
{
    /**
     * @var LogScheduleFactory
     */
    protected $logScheduleFactory;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var LogsFactory
     */
    protected $logsFactory;

    /**
     * @var null
     */
    protected $cronSchedule = null;

    /**
     * Logs constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param LogScheduleFactory $logScheduleFactory
     * @param LogsFactory $logsFactory
     * @param DateTime $date
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        LogScheduleFactory $logScheduleFactory,
        LogsFactory $logsFactory,
        DateTime $date,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        Registry $registry
    ) {
        $this->logScheduleFactory = $logScheduleFactory;
        $this->date = $date;
        $this->registry = $registry;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->logsFactory = $logsFactory;
        parent::__construct($context);
    }

    /**
     * @param array $logsArray
     * @param int $exportCron
     * @return string
     */
    public function manualLogs($logsArray = [], $exportCron = 0)
    {
        try {
            if (!$this->scopeConfigInterface->getValue('logs/log_setting/enable')) {
                return;
            }
            if (!$this->cronSchedule || $exportCron) {
                $this->cronSchedule = $this->logScheduleFactory->create();
            }

            if (isset($logsArray['job_code'])
                && (empty($this->cronSchedule->getJobCode())
                    || $this->cronSchedule->getJobCode() == 'Exception'
                    || $this->cronSchedule->getJobCode() == 'Notice')
            ) {
                $this->cronSchedule->setJobCode($logsArray['job_code']);
            }

            if (isset($logsArray['schedule_id'])) {
                $this->cronSchedule->setScheduleId($logsArray['schedule_id']);
            }

            if (isset($logsArray['status'])) {
                $this->cronSchedule->setStatus($logsArray['status']);
            }

            if (isset($logsArray['messages']) && empty($this->cronSchedule->getMessages())) {
                $this->cronSchedule->setMessages(str_replace(',"', ' ,"', $logsArray['messages']));
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

            $id = $this->cronSchedule->getId();

            $logsArray['id'] = $id;

            if ((isset($logsArray['emarsys_info']) && !empty($logsArray['emarsys_info']))
                || (isset($logsArray['description']) && !empty($logsArray['description']))
            ) {
                $this->logs($logsArray);
            }
        } catch (\Exception $e) {
            $id = null;
        }

        return $id;
    }

    /**
     * For saving Logs
     *
     * @param array $logsArray
     * @return bool|void
     */
    public function logs($logsArray = [])
    {
        try {
            if (!$this->scopeConfigInterface->getValue('logs/log_setting/enable')) {
                return;
            }
            $currentDate = $this->date->date('Y-m-d H:i:s', time());
            $schedulerId = $logsArray['id'] ?? null;

            $logsModel = $this->logsFactory->create();
            $logsModel->setLogExecId($schedulerId)
                ->setCreatedAt($currentDate)
                ->setEmarsysInfo(isset($logsArray['emarsys_info']) ? $logsArray['emarsys_info'] : '')
                ->setDescription(
                    isset($logsArray['description']) ? str_replace(
                        ',"',
                        ' ,"',
                        $logsArray['description']
                    ) : ''
                )
                ->setAction(isset($logsArray['action']) ? $logsArray['action'] : 'synced to emarsys')
                ->setMessageType(isset($logsArray['message_type']) ? $logsArray['message_type'] : '')
                ->setStoreId(isset($logsArray['store_id']) ? $logsArray['store_id'] : 0)
                ->setWebsiteId(isset($logsArray['website_id']) ? $logsArray['website_id'] : 0)
                ->setLogAction(isset($logsArray['log_action']) ? $logsArray['log_action'] : 'sync');

            $logsModel->save();

            $websiteId = isset($logsArray['website_id']) ? $logsArray['website_id'] : 0;
            if ($websiteId == 0) {
                $scopeType = 'default';
            } else {
                $scopeType = 'websites';
            }

            $sendLogReport = $this->scopeConfigInterface->getValue(
                'logs/log_setting/log_report',
                $scopeType,
                $websiteId
            );
            if ($sendLogReport == '' && $websiteId == 0) {
                $sendLogReport = $this->scopeConfigInterface->getValue('logs/log_setting/log_report');
            }
            if ($sendLogReport && ($logsArray['message_type'] ?? false) == 'Error') {
                if ($sendLogReport) {
                    $title = ucfirst(($logsArray['job_code'] ?? false)) . " : Store - " . ($logsArray['store_id'] ?? false);
                    $description = ($logsArray['description'] ?? false);
                    $this->errorLogEmail($title, $description);
                }
            }
        } catch (\Exception $e) {

        }
        return true;
    }

    /**
     * @param $title
     * @param $errorMsg
     * @return bool|void
     */
    public function errorLogEmail($title, $errorMsg)
    {
        try {
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
                    'store' => $this->storeManager->getStore()->getId(),
                ];
                $templateVars = [
                    'store' => $this->storeManager->getStore(),
                    'sync_name' => $title,
                    'message' => $errorMsg,
                ];
                $from = [
                    'email' => "support@emarsys.com",
                    'name' => "Support",
                ];
                $this->inlineTranslation->suspend();

                if (is_array($explodedemailReceipient)) {
                    for ($i = 0; $i < count($explodedemailReceipient); $i++) {
                        $to = $explodedemailReceipient[$i];
                        $transport = $this->_transportBuilder
                            ->setTemplateIdentifier('error_log_email_template')
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
        } catch (\Exception $e) {

        }

        return true;
    }
}
