<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Symfony\Component\Config\Definition\Exception\Exception;

class Createfields extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Emarsys\Emarsys\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $resourceModelCustomer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * 
     * @param Context $context
     * @param \Emarsys\Emarsys\Model\CustomerFactory $customerFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Emarsys\Model\Api\Api $api
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\CustomerFactory $customerFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\Api\Api $api
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->customerFactory = $customerFactory;
        $this->getRequest = $request;
        $this->_storeManager = $storeManager;
        $this->api = $api;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            // Note: Change websiteId to dynamic after adding store switcher in Install checklist
            $websiteId = $this->getRequest->getParam('website');
            $this->api->setWebsiteId($websiteId);
            $mandatoryFields = $this->getRequest->getParam('customerfields');
            if ($mandatoryFields) {
                $mandatoryFields = explode(',', $mandatoryFields);

                foreach ($mandatoryFields as $mandatoryField) {
                    $data['name'] = $mandatoryField;
                    $data['application_type'] = "shorttext";
                    $this->api->sendRequest('POST', 'field', $data);
                }
                $this->messageManager->addSuccess('Fileds created');
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            }
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
