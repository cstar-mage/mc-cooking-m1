<?php
class BroSolutions_RecreateOrder_Block_Adminhtml_Sales_Order_View_Tab_Renderer_Changeclasslink extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row) {
        $html = '<a href="'.$this->getUrl('*/sales_order_edit/start', array('order_id' => $row->getOrderId(), 'orig_order_id' => $row->getOrderId())).'">'.
            $this->__('Change class').'</a>';
        return $html;
    }

}