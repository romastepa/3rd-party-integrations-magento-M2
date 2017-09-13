<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

class ArchiveData
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'delete', 'label' => 'Delete'],
            ['value' => 'archive', 'label' => 'Archive']
        ];
    }
}
