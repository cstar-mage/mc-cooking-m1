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
class Aitoc_Aitexporter_Model_Mysql4_Export_Order extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize resource
     *
     */
    protected function _construct()
    {
        $this->_init('aitexporter/export_order', 'order_id');
    }
    
    /**
    * Delete old exported orders ids from log table to be able to export same orders again
    * 
    * @param int $profile_id
    * @return Aitoc_Aitexporter_Model_Mysql4_Export_Order
    */
    public function truncateByProfileId( $profile_id )
    {
        $deleteSql = ' UPDATE `'.$this->getMainTable().'` SET profile_id = 0'.
            ' WHERE profile_id = '.(int)$profile_id.' ';
        $this->_getWriteAdapter()->query($deleteSql);

        return $this;
    }

    /**
     * Works with loaded object 
     * Checks $this->getOrdersCount(). If 0 retreives orders and adds them to the aitexperter_export_order table. Updates orders_count
     * Loads orders collection
     * 
     * @return Aitoc_Aitexport_Model_Mysql4_Export_Order_Collection
     */
    public function assignOrders(Aitoc_Aitexporter_Model_Export $export)
    {
        $deleteSql = ' DELETE FROM `'.$this->getMainTable().'` '.
            ' WHERE export_id = '.$this->_getWriteAdapter()->quote($export->getId()).' ';
        $this->_getWriteAdapter()->query($deleteSql);

        $filter         = $export->getConfig()->getFilter();
        $auto           = $export->getConfig()->getAuto();
        $mainTableAlias = Mage::helper('aitexporter/version')->collectionMainTableAlias();

        $exportOrderCollection = Mage::getModel('sales/order')->getCollection();
        /* @var $exportOrderCollection Mage_Sales_Model_Mysql4_Order_Collection */
        $selectSql = $exportOrderCollection->getSelect();
        /* @var $selectSql Varien_Db_Select */

        if (!empty($filter['product_id_from']) || !empty($filter['product_id_to']))
        {
            $selectSql->join(array('order_item_table' => $this->getTable('sales/order_item')), 
                                    $mainTableAlias.'.entity_id=order_item_table.order_id', 
                                    array());
        }

        if (!empty($filter['product_id_from']))
        {
            $selectSql->where('order_item_table.product_id >= ?', $filter['product_id_from']);
        }

        if (!empty($filter['product_id_to']))
        {
            $selectSql->where('order_item_table.product_id <= ?', $filter['product_id_to']);
        }

        if ($export->getStoreId())
        {
            $exportOrderCollection->addAttributeToFilter('store_id', $export->getStoreId());
        }

        if (!empty($filter['orderstatus']))
        {
            $exportOrderCollection->addAttributeToFilter('status', $filter['orderstatus']);
        }
        
        if (!empty($filter['date_from']))
        {
            $date = new Zend_Date($filter['date_from'], null, Mage::app()->getLocale()->getLocale());
            $exportOrderCollection->addAttributeToFilter('created_at', 
                array('gteq' => $date->toString('yyyy-MM-dd HH:mm:ss')));
        }

        if (!empty($filter['date_to']))
        {
            $date = new Zend_Date($filter['date_to'], null, Mage::app()->getLocale()->getLocale());
            $exportOrderCollection->addAttributeToFilter('created_at', 
                array('lteq' => $date->toString('yyyy-MM-dd HH:mm:ss')));
        }

        if (!empty($filter['order_id']))
        {
            $exportOrderCollection->addAttributeToFilter('entity_id', $filter['order_id']);
        }

        if (!empty($filter['order_id_from']))
        {
            $orderFrom = Mage::getModel('sales/order')->loadByIncrementId($filter['order_id_from']);
            if ($orderFrom->getId())
            {
                $exportOrderCollection->addAttributeToFilter('entity_id', array('gteq' => $orderFrom->getId()));
            }
        }

        if (!empty($filter['order_id_to']))
        {
            $orderTo = Mage::getModel('sales/order')->loadByIncrementId($filter['order_id_to']);
            if ($orderTo->getId())
            {
                $exportOrderCollection->addAttributeToFilter('entity_id', array('lteq' => $orderTo->getId()));
            }
        }

        if (!empty($auto['bind_order_id']) && $export->getProfileId()!=0)
        {
            $selectSql->joinLeft(array('aeo' => $this->getTable('aitexporter/export_order')), 
                                    'aeo.profile_id = '.(int)$export->getProfileId().' AND '.$mainTableAlias.'.entity_id=aeo.order_id', 
                                    array());
            $selectSql->where('aeo.order_id is NULL');
        }

        if (!empty($filter['customer_id_from']))
        {
            $exportOrderCollection->addAttributeToFilter('customer_id', array('gteq' => $filter['customer_id_from']));
        }

        if (!empty($filter['customer_id_to']))
        {
            $exportOrderCollection->addAttributeToFilter('customer_id', array('lteq' => $filter['customer_id_to']));
        }

        $selectSql->setPart(Zend_Db_Select::COLUMNS, array())->columns(array('order_id' => ' DISTINCT('.$mainTableAlias.'.entity_id)', 'export_id' => '('.$export->getId().')', 'profile_id' => '('.(int)$export->getProfileId().')'));

        $insertSql = ' INSERT INTO `'.$this->getTable('aitexporter/export_order').'` (order_id, export_id, profile_id) '.$selectSql->assemble().' ';

        $this->_getWriteAdapter()->query($insertSql);

        return $this;
    }
}