<?php

class BroSolutions_All_Block_Order_Email_Items_Giftcards extends Mage_Sales_Block_Order_Email_Items_Order_Default
{
    public function getProductThumbnailForEmail()
    {
        $item = $this->getItem();
        if ($item) {
            $product = $item->getProduct();
            if ($product && $product->getTypeId() == 'giftcards') {
                $itemOptions = $item->getProductOptions();
                if(isset($itemOptions['options']) && !empty($itemOptions['options'])){
                    foreach($itemOptions['options'] as $option){
                        if(isset($option['option_type']) && isset($option['option_value']) && isset($option['option_type']) && $option['option_type'] == 'drop_down'){
                            $image = Mage::helper('giftcards')->getImageByOptionTypeId($option['option_value']);
                            return $image;
                        }
                    }
                }
            }
        }
        return false;
    }

    protected function _validateImageOptionCode($code)
    {
        if (strpos($code, 'option_') !== false) {
            $postfix = str_replace('option_', '', $code);
            if (is_numeric($postfix)) {
                return true;
            }
        }
        return false;
    }

}