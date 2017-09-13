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

class ChangeValue extends \Magento\Backend\App\Action
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
     * @param Context $context
     * @param \Magento\Backend\Model\Session $session
     * @param \Emarsys\Emarsys\Model\EventFactory $eventFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\EventFactory $eventFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Event $eventResourceModel,
        \Emarsys\Emarsys\Model\EmarsyseventmappingFactory $EmarsyseventmappingFactory,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->eventResourceModel = $eventResourceModel;
        $this->EmarsyseventmappingFactory = $EmarsyseventmappingFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->eventFactory = $eventFactory;
        $this->_urlInterface = $context->getUrl();
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $magento_event_id = $this->getRequest()->getParam('magentoeventId');
        $emarsys_event_id = $this->getRequest()->getParam('emarsyseventId');
        $id = $this->getRequest()->getParam('Id');
        $gridSession = $this->session->getMappingGridData();
        $gridSession[$id]['magento_event_id'] = $magento_event_id;
        $gridSession[$id]['emarsys_event_id'] = $emarsys_event_id;
        $this->session->setMappingGridData($gridSession);
    }
}
