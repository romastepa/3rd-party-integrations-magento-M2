<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Model\ResourceModel\Emarsyseventmapping\CollectionFactory as EmarsysEventMappingCollectionFactory;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory as EmarsysMagentoEventsCollectionFactory;

/**
 * Class Placeholders
 *
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event
 */
class Placeholders extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var EmarsysMagentoEventsCollectionFactory
     */
    protected $emarsysMagentoEventsCollection;

    /**
     * @var EmarsysEventMappingCollectionFactory
     */
    protected $emarsysEventMappingCollection;

    /**
     * Placeholders constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param EmarsysMagentoEventsCollectionFactory $emarsysMagentoEventsCollection
     * @param EmarsysEventMappingCollectionFactory $emarsysEventMappingCollection
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        EmarsysMagentoEventsCollectionFactory $emarsysMagentoEventsCollection,
        EmarsysEventMappingCollectionFactory $emarsysEventMappingCollection
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysMagentoEventsCollection = $emarsysMagentoEventsCollection;
        $this->emarsysEventMappingCollection = $emarsysEventMappingCollection;
    }

    /**
     * Index action
     *
     * @return Page
     */
    public function execute()
    {
        /**
         * @var Page $resultPage
         */
        $resultPage = $this->resultPageFactory->create();
        $magentoEvent = $result = false;
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex7');
        $mappingId = $this->getRequest()->getParam('mapping_id');
        $storeId = $this->getRequest()->getParam('store');

        $result = $this->emarsysMagentoEventsCollection->create()
            ->addFieldToFilter('id', $mappingId)
            ->getFirstItem();

        if ($result && $result->getId()) {
            $magentoEvent = $result->getMagentoEvent();
        }

        $placeholderHints = $magentoEvent . ' - Placeholders Mapping';
        $resultPage->addBreadcrumb(__('Emarsys - Event Mapping'), __('Emarsys - Event Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__($placeholderHints));

        return $resultPage;
    }
}
