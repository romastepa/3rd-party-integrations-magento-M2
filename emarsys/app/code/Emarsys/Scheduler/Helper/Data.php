<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Scheduler\Helper;

use Magento\Framework\Stdlib\DateTime\Timezone as TimeZone;

/**
 * Class Data
 * @package Emarsys\Scheduler\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var TimeZone
     */
    protected $timezone;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param TimeZone $timezone
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        TimeZone $timezone
    ) {
    
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * @param $value
     * @return string
     */
    public function decorateTimeFrameCallBack($value)
    {
        if ($value) {
            return $this->decorateTime($value, false, null);
        }
    }

    /**
     * @param $value
     * @return string
     */
    public function decorateTime($value)
    {
        $formattedDateTime =  $this->timezone->date($value)->format('M d, Y h:i:s A');
        return $formattedDateTime;
    }
}
