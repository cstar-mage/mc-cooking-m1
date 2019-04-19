<?php
class BroSolutions_RecreateOrder_Block_Adminhtml_Sales_Order_Create_Items_Grid extends Mage_Adminhtml_Block_Sales_Order_Create_Items_Grid
{
    public function getItems()
    {
        $items = $this->getParentBlock()->getItems();
        $oldSuperMode = $this->getQuote()->getIsSuperMode();
        $this->getQuote()->setIsSuperMode(false);
        foreach ($items as $key => $item) {
            $item->setQty($item->getQty());
            $product = $item->getProduct();
            $productResource = $product->getResource();
            $defaultFrontendStoreId = $this->_getDefaultFrontendStoreId();
            $cancelReason = $productResource->getAttributeRawValue($product->getId(), 'cancel_reason', $defaultFrontendStoreId);
            if($cancelReason){
                unset($items[$key]);
            }
            $stockItem = $product->getStockItem();
            if ($stockItem instanceof Mage_CatalogInventory_Model_Stock_Item) {
                if ($item->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                    $item->setMessage(Mage::helper('adminhtml')->__('This product is currently disabled.'));
                    $item->setHasError(true);
                }
            }
        }
        $this->getQuote()->setIsSuperMode($oldSuperMode);
        return $items;
    }

    protected function _getDefaultFrontendStoreId()
    {
        $defaultStoreId = Mage::app()
            ->getWebsite(true)
            ->getDefaultGroup()
            ->getDefaultStoreId();
        return $defaultStoreId;
    }
}