<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Support;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Helper\Email as EmarsysHelperEmail;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Save
 * @package Emarsys\Emarsys\Controller\Adminhtml\Support
 */
class Save extends Action
{
    /**
     * @var Session
     */
    protected $authSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var
     */
    protected $data;

    /**
     * @var
     */
    protected $email;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var EmarsysHelperEmail
     */
    protected $emailHelper;

    /**
     * Save constructor.
     * @param Session $authSession
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManager
     * @param EmarsysHelper $emarsysHelper
     * @param EmarsysHelperEmail $emailHelper
     * @param Context $context
     * @param Logger $logger
     */
    public function __construct(
        Session $authSession,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        EmarsysHelper $emarsysHelper,
        EmarsysHelperEmail $emailHelper,
        Context $context,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->authSession = $authSession;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManager = $storeManager;
        $this->emarsysHelper = $emarsysHelper;
        $this->emailHelper = $emailHelper;
        $this->logger = $logger;
        $this->session = $context->getSession();
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $req = $this->emarsysHelper->getRequirementsInfo();
        if ($this->getRequest()->getParams()) {
            $data = $this->getRequest()->getParams();
            try {
                $typeArray = explode('||', $data['type']);
                $type = trim($typeArray[0]);    // $typeArray[0] is the type
                $name = trim($data['firstname']);
                $email = trim($typeArray[1]);   // $typeArray[1] are email recievers
                $subject = trim($data['subject']);
                $priority = trim($data['priority']);
                $message = trim($data['message']);
                // it depends on the template variables
                $emailTemplateVariables = [];
                $emailTemplateVariables['type'] = $type;
                $emailTemplateVariables['name'] = $name;
                $emailTemplateVariables['email'] = $email;
                $emailTemplateVariables['subject'] = $type . ' - ' . $subject;
                $emailTemplateVariables['priority'] = $priority;
                $emailTemplateVariables['message'] = $message;
                $emailTemplateVariables['store_name'] = $this->storeManager->getStore()->getName();;
                $emailTemplateVariables['domain'] = $this->storeManager->getStore()->getBaseUrl();
                $emailTemplateVariables['phpvalue'] = $req['php_version']['current']['value'];
                $emailTemplateVariables['memoryvalue'] = $req['memory_limit']['current']['value'];
                $emailTemplateVariables['magentovalue'] = $req['magento_version']['current']['value'];
                $emailTemplateVariables['curlvalue'] = $req['curl_enabled']['current']['value'];
                $emailTemplateVariables['soapvalue'] = $req['soap_enabled']['current']['value'];
                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );
                $user = $this->authSession->getUser();
                $senderInfo = [
                    'email' => $user->getEmail(),
                    'name' => $user->getUsername()
                ];
                $storeId = $this->storeManager->getStore()->getId();
                $emailRecievers = explode(',', $typeArray[1]);
                foreach ($emailRecievers as $emailReciever) {
                    $recieverInfo = [
                        'email' => $emailReciever,
                        'name' => $name
                    ];
                    $this->emailHelper->sendEmail(
                        $storeId,
                        $emailTemplateVariables,
                        $senderInfo,
                        $recieverInfo
                    );
                }
                $data = $inputFilter->getUnescaped();
                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong item is specified.'));
                }
                $this->messageManager->addSuccessMessage(__('Request send succesfully'));
                $this->_redirect('emarsys_emarsys/support/index');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__($e->getMessage()));
                $this->logger->critical($e);
                $this->session->setPageData($data);
                $this->_redirect('emarsys_emarsys/support/index');
                return;
            }
        }
        $this->_redirect('emarsys_emarsys/support/index');
    }
}
