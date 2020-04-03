<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Support;

use Magento\{
    Backend\App\Action,
    Backend\App\Action\Context,
    Backend\Model\Auth\Session,
    Framework\App\Config\ScopeConfigInterface,
    Framework\Translate\Inline\StateInterface,
    Store\Model\StoreManagerInterface,
    Framework\Mail\Template\TransportBuilder
};
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
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
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * Save constructor.
     * @param Session $authSession
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManager
     * @param EmarsysHelper $emarsysHelper
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param Context $context
     * @param Logger $logger
     */
    public function __construct(
        Session $authSession,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        EmarsysHelper $emarsysHelper,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        Context $context,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->authSession = $authSession;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManager = $storeManager;
        $this->emarsysHelper = $emarsysHelper;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
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
                $templateVars = [];
                $templateVars['type'] = $type;
                $templateVars['name'] = $name;
                $templateVars['email'] = $email;
                $templateVars['subject'] = $type . ' - ' . $subject;
                $templateVars['priority'] = $priority;
                $templateVars['message'] = $message;
                $templateVars['store_name'] = $this->storeManager->getStore()->getName();;
                $templateVars['domain'] = $this->storeManager->getStore()->getBaseUrl();
                $templateVars['phpvalue'] = $req['php_version']['current']['value'];
                $templateVars['memoryvalue'] = $req['memory_limit']['current']['value'];
                $templateVars['magentovalue'] = $req['magento_version']['current']['value'];
                $templateVars['curlvalue'] = $req['curl_enabled']['current']['value'];
                $templateVars['soapvalue'] = $req['soap_enabled']['current']['value'];
                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );
                $user = $this->authSession->getUser();
                $from = [
                    'email' => $user->getEmail(),
                    'name' => $user->getUsername()
                ];
                $storeId = $this->emarsysHelper->getFirstStoreId();
                $emailRecievers = explode(',', $typeArray[1]);

                foreach ($emailRecievers as $emailReciever) {
                    $to = [
                        'email' => $emailReciever,
                        'name' => $name
                    ];
                    $templateOptions = [
                        'area' =>  \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                        'store' => $storeId
                    ];

                    $this->inlineTranslation->suspend();
                    $transport = $this->transportBuilder
                        ->setTemplateIdentifier('help_email_template_id')
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
                    $transport->sendMessage();
                    $this->inlineTranslation->resume();
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
