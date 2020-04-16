<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\System\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Logger extends Button
{
    /**
     * Path to block template
     */
    const TEST_CONNECTION_TEMPLATE = 'system/config/button/logger.phtml';

    /**
     * Test Connection Button Label
     *
     * @var string
     */
    protected $_testConnectionButtonLabel = 'Download Log';

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $websiteId = $this->getRequest()->getParam('website');
        if ($websiteId == '') {
            $websiteId = 1;
        }
        $originalData = $element->getOriginalData();
        $ajaxUrl = $this->_urlBuilder->getUrl("emarsys_emarsys/log/emarsysLogger", ["website" => $websiteId]);
        $buttonLabel = !empty($originalData['button_label'])
            ? $originalData['button_label']
            : $this->_testConnectionButtonLabel;
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $ajaxUrl,
            ]
        );

        return $this->_toHtml();
    }
}
