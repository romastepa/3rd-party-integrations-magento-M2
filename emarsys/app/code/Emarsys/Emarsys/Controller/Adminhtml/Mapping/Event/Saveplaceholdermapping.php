<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Saveplaceholdermapping extends \Magento\Backend\App\Action
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
     * @var \Emarsys\Emarsys\Model\EventFactory
     */
    protected $eventFactory;

    /**
     * 
     * @param Context $context
     * @param \Emarsys\Emarsys\Model\EventFactory $eventFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Event $eventResourceModel
     * @param \Emarsys\Emarsys\Model\PlaceholdersFactory $emarsysEventPlaceholderMappingFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Emarsys\Emarsys\Model\EventFactory $eventFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Event $eventResourceModel,
        \Emarsys\Emarsys\Model\PlaceholdersFactory $emarsysEventPlaceholderMappingFactory,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->eventResourceModel = $eventResourceModel;
        $this->resultPageFactory = $resultPageFactory;
        $this->eventFactory = $eventFactory;
        $this->emarsysEventPlaceholderMappingFactory = $emarsysEventPlaceholderMappingFactory;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $placeholderData = $this->getRequest()->getPost('placeholderData');
            $decodedText = html_entity_decode($placeholderData);
            $myArray = json_decode($decodedText, true);

            foreach ($myArray as $value) {
                foreach ($value as $key => $value1) {
                    $emarsysEventPlaceholderMapping = $this->emarsysEventPlaceholderMappingFactory->create()->load($key);
                    $emarsysEventPlaceholderMapping->setEmarsysPlaceholderName($value1);
                    $emarsysEventPlaceholderMapping->save();
                }
            }

            $this->messageManager->addSuccess("Placeholders mapped successfully");
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        } catch (\Exception $e) {
            $this->messageManager->addError("Error occurred while mapping Event  ");
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
