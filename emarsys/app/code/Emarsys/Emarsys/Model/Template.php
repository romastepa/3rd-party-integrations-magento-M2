<?php

namespace Emarsys\Emarsys\Model;

class Template extends \Magento\Email\Model\Template
{
    public function getProcessedVariable($placeholder = '')
    {
        $variables = $this->_getVars();

        $processor = $this->getTemplateFilter()
            ->setUseSessionInUrl(false)
            ->setPlainTemplateMode($this->isPlain())
            ->setIsChildTemplate($this->isChildTemplate())
            ->setTemplateProcessor([$this, 'getTemplateContent']);

        $variables['this'] = $this;

        $isDesignApplied = $this->applyDesignConfig();

        // Set design params so that CSS will be loaded from the proper theme
        $processor->setDesignParams($this->getDesignParams());

        if (isset($variables['subscriber'])) {
            $storeId = $variables['subscriber']->getStoreId();
        } else {
            $storeId = $this->getDesignConfig()->getStore();
        }
        $processor->setStoreId($storeId);

        // Populate the variables array with store, store info, logo, etc. variables
        $variables = $this->addEmailVariables($variables, $storeId);
        $processor->setVariables($variables);

        try {
            $result = $processor->filter($placeholder);
        } catch (\Exception $e) {
            $this->cancelDesignConfig();
            throw new \LogicException(__($e->getMessage()), $e);
        }
        if ($isDesignApplied) {
            $this->cancelDesignConfig();
        }
        return $result;
    }


    public function checkOrder()
    {
        $variables = $this->_getVars();
        $variables['this'] = $this;
        if (isset($variables['order'])) {
            return $variables['order'];
        } else {
            return false;
        }
    }
}
