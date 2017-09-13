<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Productexport\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * @var \Magento\ImportExport\Model\Source\Export\FormatFactory
     */
    protected $_formatFactory;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Backend\Model\Auth\Session $session,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\Category $categoryModel,
        \Magento\Framework\Registry $registry,
        \Magento\ImportExport\Model\Source\Export\FormatFactory $formatFactory,
        \Magento\Framework\App\Request\Http $request,
        Extended $extended,
        array $data = []
    ) {
    
        parent::__construct($context, $registry, $formFactory, $data);
        $this->session = $session;
        $this->admin = $context->getBackendSession();
        $this->storeManager = $context->getStoreManager();
        $this->_formFactory = $formFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryModel = $categoryModel;
        $this->_registry = $registry;
        $this->extended = $extended;
        $this->getRequest = $request;
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->_formatFactory = $formatFactory;
    }

    /**
     * Init form
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $this->setForm($form);
        $fieldset = $form->addFieldset("support_form", ["legend" => '']);
        $values = [];
        $values['customer'] = 'Customer';
        $values['subscriber'] = 'Subscriber';
        $smartInsightEnable = $this->scopeConfigInterface->getValue('smart_insight/smart_insight/smartinsight_enabled');
        if ($smartInsightEnable == 1) {
            $values['order'] = 'Order';
        }
        $productExportStatus = $this->scopeConfigInterface->getValue('emarsys_predict/feed_export/enable_nightly_product_feed');
        if ($productExportStatus == 1 || $smartInsightEnable == 1) {
            $values['product'] = 'Product';
        }
        $fieldset->addField("entitytype", "select", [
            'label' => 'Export Entity Type',
            'name' => 'entity_type',
            'values' => $values,
            'style' => 'width:350px',
            'value' => 'product',
            'onchange' => "bulkExport(this.value)",
        ]);

        $fieldset->addField("include_bundle", "select", [
            'label' => 'Include Bundle Product',
            'name' => 'include_bundle',
            'values' => [
                '0' => 'No',
                '1' => 'Yes',
            ],
            'style' => 'width:350px'
        ]);

        //Get all categories Name and id Collection

        $optionArray = [];
        $storeId = $this->getRequest->getParam('store');
        $rootCategoryId = 1;        // this id is by default 1 for all default categories
        $defaultCategory = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $categoryCollection = $this->categoryFactory->create()
            ->getCollection()
            ->addFieldToFilter('path', ['like' => "$rootCategoryId/$defaultCategory/%"])
            ->setOrder('path', 'ASC')
            ->getData();

        foreach ($categoryCollection as $category) {
            $categoryData = $this->categoryModel->load($category['entity_id']);
            $catId = $categoryData->getId();
            if ($catId != $rootCategoryId && $catId != $defaultCategory) {
                $paths = explode('/', $categoryData->getPath());
                $catSpace = '';
                $i = 0;
                foreach ($paths as $path) {
                    if ($path == $rootCategoryId || $path == $defaultCategory) {
                        $catSpace = '';
                    } else {
                        if ($i == 0) {
                            $catSpace = '';
                        } else {
                            $catSpace = " _ _ " . $catSpace;
                        }
                        $i++;
                    }
                }
                $catName = $catSpace . " " . $categoryData->getName();
                $optionArray[] = [
                    'value' => $categoryData->getId(),
                    'label' => $catName
                ];
            }
        }

        $fieldset->addType(
            'text',
            '\Emarsys\Emarsys\Block\Adminhtml\Customformfield\Edit\Renderer\CustomRenderer'
        );
        $fieldset->addField(
            'text',
            'text',
            [
                'name' => 'text',
                'label' => __('Exclude Categories'),
                'title' => __('Exclude Categories'),

            ]
        );

        $fieldset->addField(
            'file_format',
            'select',
            [
                'name' => 'file_format',
                'style' => 'width:350px',
                'title' => __('Export To'),
                'label' => __('Export To'),
                'required' => false,
                'values' => $this->_formatFactory->create()->toOptionArray()
            ]
        );
        return parent::_prepareForm();
    }
}
