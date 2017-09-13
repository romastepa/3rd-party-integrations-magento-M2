<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

class Sleektheme implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [

            ['value' => 'Realtime-failsafe', 'label' => __('Realtime-failsafe')],
            ['value' => 'Background only', 'label' => __('Background only')]
        ];
    }
}
