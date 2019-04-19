<?php

class Webtex_Giftcards_Block_Adminhtml_Print extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('webtex/giftcards/print.phtml');
    }

    public function getGiftcard() {
        if (($cardId = $this->getRequest()->getParam('id')) > 0) {
            $card = Mage::getModel('giftcards/giftcards')->load($cardId);
            return $card;
        }
        return false;
    }


    public function getPicture()
    {
        $card = $this->getGiftcard();
        $order = Mage::getModel('sales/order')->load($card->getOrderId());
        if($order && $order->getId()){
            $productId = $card->getProductId();
            $orderItem = $this->_getOrderItemByOrderAndProductId($order, $productId);
            $itemOptions = $orderItem->getProductOptions();
            if(isset($itemOptions['options']) && !empty($itemOptions['options'])){
                foreach($itemOptions['options'] as $option){
                    if(isset($option['option_type']) && isset($option['option_value']) && isset($option['option_type']) && $option['option_type'] == 'drop_down'){
                        $picture = Mage::helper('giftcards')->getImageByOptionTypeId($option['option_value']);
                        return $picture;
                    }
                }
            }
        }
        return false;
    }

    protected function _getOrderItemByOrderAndProductId($order, $productId)
    {
        foreach ($order->getAllItems() as $item){
            $itemProductId = $item->getProductId();
            if($itemProductId == $productId){
                return $item;
            }
        }
        return false;
    }
}
