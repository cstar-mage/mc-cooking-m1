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
class Aitoc_Aitexporter_Model_Mysql4_Profile_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('aitexporter/profile');
    }
    
    public function loadByStore( $store_id )
    {
        $this->getSelect()
            ->where('store_id = ?', $store_id);
        return $this->load();
    }
    
    public function loadLast( $store_id = 0 )
    {
        if($store_id != 0) {
            $this->addFieldToFilter('store_id', $store_id);
        }
        $this->setOrder('date', 'DESC');
        
        $this->getSelect()
            ->limit(1, 0);
        return $this;
    }     
    
    /**
    * Load colleection where flag contains $id
    * 
    * @param int $id
    * @param mixed $store_id
    * @return Aitoc_Aitexporter_Model_Mysql4_Profile_Collection
    */
    public function loadByFlag( $id, $store_id = null )
    {
        $this->getSelect()
            ->where('flag_auto = ? ', $id);
        if(!is_null($store_id)) {
            if(is_array($store_id)) {
                $store_id[] = 0;
                #$store_id = implode(',', array_unique($store_id));
            }
            $this->getSelect()
                ->where('store_id in (?)', $store_id);
        }
        return  $this;
    }
}