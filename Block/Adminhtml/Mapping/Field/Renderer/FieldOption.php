<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Mapping\Field\Renderer;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\ResourceModel\Customer\CollectionFactory;
use Emarsys\Emarsys\Model\ResourceModel\Field;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class FieldOption
 * @package Emarsys\Emarsys\Block\Adminhtml\Mapping\Field\Renderer
 */
class FieldOption extends AbstractRenderer
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer\Collection
     */
    protected $collectionFactory;

    /**
     * @var Data
     */
    protected $backendHelper;

    /**
     * @var Field
     */
    protected $resourceModelField;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * FieldOption constructor.
     * @param Session $session
     * @param CollectionFactory $collectionFactory
     * @param Data $backendHelper
     * @param Field $resourceModelField
     * @param StoreManagerInterface $storeManager
     * @param EmarsysHelper $emarsysHelper
     */
    public function __construct(
        Session $session,
        CollectionFactory $collectionFactory,
        Data $backendHelper,
        Field $resourceModelField,
        StoreManagerInterface $storeManager,
        EmarsysHelper $emarsysHelper
    ) {
        $this->session = $session;
        $this->collectionFactory = $collectionFactory;
        $this->backendHelper = $backendHelper;
        $this->resourceModelField = $resourceModelField;
        $this->_storeManager = $storeManager;
        $this->emarsysHelper = $emarsysHelper;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $optionId = $row->getData('option_id');
        $url = $this->backendHelper->getUrl('*/*/saveRow');
        $columnAttr = 'emarsys_field_option';
        $html = '<select name="emarsys_field_option" class="admin__control-select" style="width:350px;" onchange="changeValue(\'' . $url . '\', \'' . $optionId . '\', \'' . $columnAttr . '\', this.value)";>';
        $html .= '<option value=" ">Please Select</option>';
        $session = $this->session->getData();
        if (isset($session['store'])) {
            $storeId = $session['store'];
        } else {
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
        $emarsysContactFields = $this->resourceModelField->getEmarsysFieldOption($storeId);
        foreach ($emarsysContactFields as $field) {
            $chkSelected = $this->resourceModelField->checkSelectedOption($optionId, $field['option_id'], $field['emarsys_field_id'], $storeId);
            $selected = '';
            if ($chkSelected == 1) {
                $selected = 'selected=selected';
            }
            $html .= '<option value="' . $field['emarsys_field_id'] . '-' . $field['option_id'] . '" ' . $selected . '>' . $field['type'] . '-' . $field['name'] . ':' . $field['option_name'] . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
