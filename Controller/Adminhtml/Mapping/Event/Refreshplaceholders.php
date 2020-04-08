<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;

class Refreshplaceholders extends Action
{
    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * Refreshplaceholders constructor.
     *
     * @param Context $context
     * @param EmarsysHelper $emarsysHelper
     */
    public function __construct(
        Context $context,
        EmarsysHelper $emarsysHelper
    ) {
        parent::__construct($context);
        $this->emarsysHelper = $emarsysHelper;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $placeholderData = $this->getRequest()->getParams();
        $store = $this->getRequest()->getParam('store');
        if (!$store) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
            $returnUrl = $this->getUrl(
                '*/*/refreshplaceholders',
                ['mapping_id' => $placeholderData['mapping_id'], 'store' => $storeId]
            );
            return $this->resultRedirectFactory->create()->setUrl($returnUrl);
        }
        $this->emarsysHelper->refreshPlaceholders(
            $placeholderData['mapping_id'],
            $this->getRequest()->getParam('store')
        );

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
