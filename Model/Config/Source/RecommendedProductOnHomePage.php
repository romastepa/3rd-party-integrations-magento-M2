<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model\Config\Source;

class RecommendedProductOnHomePage
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '||', 'label' => 'Please select'],
            ['value' => 'HOME_||simple-tmpl', 'label' => 'Home'],
            ['value' => 'PERSONAL||personal-template', 'label' => 'Personal'],
        ];
    }
}
