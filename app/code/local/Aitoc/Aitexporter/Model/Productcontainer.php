<?php
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     xwB5BO6b8ADSZdWMZBvj82HAnNdHAZvfJuyG2JiSFP
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
/**
 * Singleton
 */
class Aitoc_Aitexporter_Model_Productcontainer extends Mage_Core_Model_Abstract
{
    protected $_products = array();

    /**
     * @param int $id
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct($id)
    {
        if(!isset($this->_products[$id])) {
            $product = Mage::getModel('catalog/product');
            $this->_products[$id] = $product->load($id);
        }
        return $this->_products[$id];
    }
}