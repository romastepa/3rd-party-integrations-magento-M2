<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Block\Adminhtml\Customformfield\Edit\Renderer;

use Magento\Framework\Data\Form\Element\Factory as Factory;

class CustomRenderer extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * CustomRenderer constructor.
     *
     * @param Factory $factoryElement
     * @param \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\App\Request\Http $http
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\Category $category
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Request\Http $http,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\Category $category,
        array $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->http = $http;
        $this->categoryFactory = $categoryFactory;
        $this->category = $category;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getElementHtml()
    {
        $rootCategoryId = 1;
        list($catTree, $selectedCats) = $this->getTreeCategories($rootCategoryId);
        $html = '<div class="emarsys-search">'
            . '<div class="category-multi-select">'
            . ' <div class="admin__field">'
            . '<div class="admin__field-control">'
            . '<div class="admin__action-multiselect-wrap action-select-wrap admin__action-multiselect-tree">'
            . '<div class="admin__action-multiselect" id="selectedCategories">' . $selectedCats . '</div>'
            . '</div></div></div>'
            . '<ul><li>'
            . '<div class= "catg-sub-">'
            . '<input type="checkbox" disabled="disabled" name="dummy-checkbox"/>Root Category</div>'
            . $catTree
            . "</div></div>";
        return $html;
    }

    /**
     * @param $parentId
     * @param int $level
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTreeCategories($parentId, $level = 1)
    {
        $storeId = $this->http->getParam('store');
        $store = $this->storeManagerInterface->getStore($storeId);
        $categoriesExcluded = $store->getConfig('emarsys_predict/feed_export/excludedcategories');
        $categoriesExcluded = explode(',', $categoriesExcluded);
        $allCategories = $this->categoryFactory->create()
            ->getCollection()
            ->addAttributeToFilter('parent_id', ['eq' => $parentId]);
        $selectedCats = '';
        $html = '<ul class="category-' . $level . '">';
        foreach ($allCategories as $cat) {
            $checked = '';
            $category = $this->category->load($cat->getId());
            if (in_array($cat->getId(), $categoriesExcluded)) {
                $checked = ' checked=checked';
            }
            $level = $category->getLevel();
            $disable = '';
            $name = 'checkbox';
            if ($level == 1) {
                $disable = " disabled=disabled";
                $name = 'dummy-checkbox';
            }
            $subcats = $category->getChildren();
            $html .= '<li class="catg-sub-$categoryLevel' . $category->getLevel() . '">'
                . '<div class="catg-sub-' . $level . '"><input type="checkbox"'
                . $checked
                . $disable
                . ' name="' . $name . '"'
                . ' onclick="categoryClick(this.value, \'' . $category->getName() . '\')"'
                . ' id="catCheckBox_' . $category->getId()
                . ' value="' . $category->getId() . '">' . $category->getName() . '</div>';
            if ($checked != '') {
                $selectedCats .= '<span id="' . $category->getId() . '"'
                    . ' onclick="Unchecked(' . $category->getId() . ')"'
                    . ' class="admin__action-multiselect-crumb">' . $category->getName() . '</span>';
            }
            if (count(array_filter(explode(",", $subcats))) > 0) {
                list($catTree, $selectedSubCats) = $this->getTreeCategories($category->getId(), $level);
                $html .= $catTree;
                $selectedCats .= $selectedSubCats;
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        return [$html, $selectedCats];
    }
}
