<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Testconnection;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class Testconnection
 * @package Emarsys\Emarsys\Controller\Adminhtml\Testconnection
 */
abstract class TestConnection extends Action
{
    /**
     * Testconnection constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }
}
