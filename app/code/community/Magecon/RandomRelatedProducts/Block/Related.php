<?php
/**
 * Open Biz Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://mageconsult.net/terms-and-conditions
 *
 * @category   Magecon
 * @package    Magecon_RandomRelatedProducts
 * @version    1.0.1
 * @copyright  Copyright (c) 2012 Open Biz Ltd (http://www.mageconsult.net)
 * @license    http://mageconsult.net/terms-and-conditions
 */

class Magecon_RandomRelatedProducts_Block_Related extends Mage_Catalog_Block_Product_List_Related
{

    protected function _prepareData()
    {
        $product = Mage::registry('product');

        $this->_itemCollection = $product->getRelatedProductCollection()
            ->addAttributeToSelect('required_options')
            ->addStoreFilter()
        ;
		
		$this->_itemCollection->getSelect()->order('RAND()');
		
		$rand_rel_products_count = Mage::getStoreConfig('catalog/backend/rand_rel_products_count');	
		$limit = ($rand_rel_products_count == null) ? 3 : $rand_rel_products_count;
		
		$this->_itemCollection->getSelect()->limit($limit);
		
        Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($this->_itemCollection,
            Mage::getSingleton('checkout/session')->getQuoteId()
        );
        $this->_addProductAttributesAndPrices($this->_itemCollection);

        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }
}