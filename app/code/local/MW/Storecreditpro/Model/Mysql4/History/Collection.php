<?php

class MW_Storecreditpro_Model_Mysql4_History_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('storecreditpro/history');
    }
}