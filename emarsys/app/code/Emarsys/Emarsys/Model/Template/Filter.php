<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Emarsys\Emarsys\Model\Template;


use Symfony\Component\Config\Definition\Exception\Exception;

class Filter extends \Magento\Email\Model\Template\Filter
{

    public function __construct(\Magento\Framework\Stdlib\StringUtils $string,
                                \Psr\Log\LoggerInterface $logger,
                                \Magento\Framework\Escaper $escaper,
                                \Magento\Framework\View\Asset\Repository $assetRepo,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Variable\Model\VariableFactory $coreVariableFactory,
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
                                \Magento\Framework\View\LayoutInterface $layout,
                                \Magento\Framework\View\LayoutFactory $layoutFactory,
                                \Magento\Framework\App\State $appState,
                                \Magento\Framework\UrlInterface $urlModel,
                                \Pelago\Emogrifier $emogrifier,
                                \Magento\Email\Model\Source\Variables $configVariables,
                                \Magento\Framework\Pricing\Helper\Data $priceHelper,
                                \Emarsys\Emarsys\Model\Logs $emarsysLogs
    )
    {

        parent::__construct($string, $logger, $escaper, $assetRepo, $scopeConfig, $coreVariableFactory, $storeManager, $layout, $layoutFactory, $appState, $urlModel, $emogrifier, $configVariables);
        $this->_modifiers['currency'] = [$this, 'modifierCurrency'];
        $this->priceHelper = $priceHelper;
        $this->emarsysLogs = $emarsysLogs;
    }


    public function modifierCurrency($value, $type = 'html')
    {
        try {

            return $this->priceHelper->currency($value);
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $this->_storeManager->getStore()->getId(), 'modifierCurrency');
        }
        return $value;
    }
}
