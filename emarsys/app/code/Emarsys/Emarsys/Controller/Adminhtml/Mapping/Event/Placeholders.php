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

class Placeholders extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * 
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsyseventmapping\CollectionFactory $emarsysEventmappingCollection
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $emarsysMagentoeventsCollection
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory $emarsysEventCollection
     * @param \Magento\Email\Model\TemplateFactory $emailTemplateModelColl
     * @param \Magento\Email\Block\Adminhtml\Template\Edit $edit
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsyseventmapping\CollectionFactory $emarsysEventmappingCollection,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $emarsysMagentoeventsCollection,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysevents\CollectionFactory $emarsysEventCollection,
        \Magento\Email\Model\TemplateFactory $emailTemplateModelColl,
        \Magento\Email\Block\Adminhtml\Template\Edit $edit
    ) {
    
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysEventmappingCollection = $emarsysEventmappingCollection;
        $this->emarsysMagentoeventsCollection = $emarsysMagentoeventsCollection;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->emarsysEventCollection = $emarsysEventCollection;
        $this->emailTemplateModel = $emailTemplateModelColl;
        $this->edit = $edit;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $meventId = '';
        $emarsysEventId = '';
        $emarsysEvent = '';
        $magentoEvent = '';
        $TemplateName = '';
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex7');
        $mappingId = $this->getRequest()->getParam("mapping_id");
        $storeId = $this->getRequest()->getParam("store_id");
        $eventMapping = $this->emarsysEventmappingCollection->create()->addFieldToFilter("id", $mappingId)->getFirstItem();
        if ($eventMapping->getId()) {
            $meventId = $eventMapping->getMagentoEventId();
            $emarsysEventId = $eventMapping->getEmarsysEventId();
            $emarsysEvent = $this->emarsysEventCollection->create()
                ->addFieldToFilter("id", $emarsysEventId)
                ->getFirstItem()->getEmarsysEvent();
        }
        $result = $this->emarsysMagentoeventsCollection->create()->addFieldToFilter("id", $meventId)
            ->getFirstItem();
        if ($result && $result->getId()) {
            $magentoEvent = $result->getMagentoEvent();
            //print_r($result->getConfigPath());exit;
            $templateId = $this->scopeConfigInterface->getValue($result->getConfigPath());
        }
        if (is_numeric($templateId)) {
            $result = $this->emailTemplateModel->create()->getCollection()->addFieldToFilter('template_id', $templateId)->getFirstItem();
            $TemplateName = $result->getTemplateCode();
            //$opt = $this->edit->_getDefaultTemplatesAsOptionsArray();
        } else {
        }
        /* This code has to be separated as it is repeating the same many times */
        //  $emailTemplates = $this->emailTemplateModelColl::getDefaultTemplatesAsOptionsArray();
        // $emailTemplateLabels = array();
        //foreach ($emailTemplates as $emailTemplate) {
        //  $emailTemplateLabels[$emailTemplate['value']] = $emailTemplate['label'];
        //  }

        //$placeholderHints = $magentoEvent . ' - Placeholders Mapping Magento Event:  ' . $magentoEvent . 'Emarsys Event: ' . $emarsysEvent . 'Magento Template: ' . $TemplateName;
        $placeholderHints = $magentoEvent . ' - Placeholders Mapping';
        $resultPage->addBreadcrumb(__('Emarsys - Event Mapping'), __('Emarsys - Event Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__($placeholderHints));
        return $resultPage;
    }
}
