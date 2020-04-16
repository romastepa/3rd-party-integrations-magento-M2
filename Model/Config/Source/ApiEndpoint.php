<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\Config\Source;

class ApiEndpoint
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'default', 'label' => 'Default'],
            ['value' => 'cdn', 'label' => 'CDN'],
            ['value' => 'custom', 'label' => 'Custom URL'],
        ];
    }
}
