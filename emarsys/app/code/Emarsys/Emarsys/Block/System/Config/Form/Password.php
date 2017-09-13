<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Block\System\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Password extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = "
                <input  type='password' id='emarsys_settings_emarsys_setting_emarsys_api_password' name='groups[emarsys_setting][fields][emarsys_api_password][value]'  onchange='javascript:confirmPassword(); return false;'>
            ";
        return $html;
    }
}
