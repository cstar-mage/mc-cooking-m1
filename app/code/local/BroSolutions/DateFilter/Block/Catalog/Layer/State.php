<?php
class BroSolutions_DateFilter_Block_Catalog_Layer_State extends Mage_Catalog_Block_Layer_State
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('catalog/layer/state_with_date.phtml');
    }

}
