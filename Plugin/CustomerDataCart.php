<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2019 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Plugin;

use Magento\Checkout\CustomerData\Cart;
use Magento\Catalog\Model\Product;

/**
 * Class CustomerData
 * @package Emarsys\Emarsys\Plugin
 */
class CustomerDataCart
{

    /**
     * CustomerDataCart constructor.
     *
     * @param Product $session
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @param OriginalCustomerDataCart $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetSectionData(Cart $subject, $result)
    {
        if (!empty($result['items'])) {
            foreach ($result['items'] as &$item) {
                $item['product_real_id'] = $this->product->getIdBySku($item['product_sku']);
            }
        }

        return $result;
    }
}
