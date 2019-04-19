<?php
class BroSolutions_All_Model_Cateloginventory_Observer extends Mage_CatalogInventory_Model_Observer
{
    public function refundOrderInventory($observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $items = array();
        $return = false;
        foreach ($creditmemo->getAllItems() as $item) {
            $return = false;
            if ($item->hasBackToStock()) {
                if ($item->getBackToStock() && $item->getQty()) {
                    $return = true;
                }
            } elseif (Mage::helper('cataloginventory')->isAutoReturnEnabled()) {
                $return = true;
            }
            if ($return) {
                $parentOrderId = $item->getOrderItem()->getParentItemId();
                $parentItem = $parentOrderId ? $creditmemo->getItemByOrderId($parentOrderId) : false;
                $qty = $parentItem ? ($parentItem->getQty() * $item->getQty()) : $item->getQty();
                if (isset($items[$item->getProductId()])) {
                    $items[$item->getProductId()]['qty'] += $qty;
                } else {
                    $items[$item->getProductId()] = array(
                        'qty'  => $qty,
                        'item' => null,
                    );
                }
            }
        }
        Mage::getSingleton('cataloginventory/stock')->revertProductsSale($items);
        if($return){
            foreach ($creditmemo->getAllItems() as $item) {
                if(isset($items[$item->getProductId()])){
                    if(isset($items[$item->getProductId()]['qty'])){
                        if($items[$item->getProductId()]['qty'] > 0){
                            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProductId());
                            $stockItem->setData('is_in_stock', true);
                            $stockItem->save();
                        }
                    }
                }
            }
        }
    }
}