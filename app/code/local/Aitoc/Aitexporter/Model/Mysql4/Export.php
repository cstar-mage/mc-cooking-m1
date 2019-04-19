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
class Aitoc_Aitexporter_Model_Mysql4_Export extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize resource
     *
     */
    protected function _construct()
    {
        $this->_init('aitexporter/export', 'export_id');
    }

    public function prepareOrderCollection(Aitoc_Aitexporter_Model_Export $export, Varien_Data_Collection_Db $collection)
    {
        $select    = $collection->getSelect();
        $from      = $select->getPart(Varien_Db_Select::FROM);
        $mainTable = Mage::helper('aitexporter/version')->collectionMainTableAlias();

        $select
            ->setPart(Varien_Db_Select::FROM, array())
            ->from(array('eo' => $this->getTable('aitexporter/export_order')))
            ->join(array($mainTable => $from[$mainTable]['tableName']), 'eo.order_id = '.$mainTable.'.entity_id', array())
            ->where('eo.export_id = ?', $export->getId());
    }
}