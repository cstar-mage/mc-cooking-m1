<?php
class BroSolutions_RecreateOrder_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info
{

    public function getEditUrl()
    {
        $currentOrderId = Mage::app()->getRequest()->getParam('order_id', false);
        $editUrl = $this->getLayout()->getBlock('sales_order_edit')->getEditUrl();
        if($currentOrderId){
            return $editUrl.'orig_order_id/'.$currentOrderId;
        }
        return $editUrl;
    }
}