<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
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
            ['value' => 'archive', 'label' => 'Archive'],
        ];
    }
}
