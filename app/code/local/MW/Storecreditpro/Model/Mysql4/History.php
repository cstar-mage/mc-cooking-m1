<?php

class MW_Storecreditpro_Model_Mysql4_History extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('storecreditpro/history', 'history_id');
    }
}