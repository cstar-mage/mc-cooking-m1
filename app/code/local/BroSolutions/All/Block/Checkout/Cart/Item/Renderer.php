<?php
class BroSolutions_All_Block_Checkout_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{


    public function getProductThumbnailForCart()
    {
        $product = $this->getProduct();
        if($product && $product->getTypeId() == 'giftcards'){
            $item = $this->getItem();
            if($item){
                $itemOptions = $item->getOptions();
                foreach($itemOptions as $itemOption){
                    if($itemOption){
                        $code = $itemOption->getCode();
                        if($code && $this->_validateImageOptionCode($code)){
                            $optionModel = Mage::getModel('catalog/product_option_value')->load($itemOption->getValue());
                            $optionTypeId = $optionModel->getOptionTypeId();
                            if($optionTypeId){
                                $image = Mage::helper('giftcards')->getImageByOptionTypeId($optionTypeId);
                                if($image){
                                    return $image;
                                }
                            }
                        }
                    }
                }
            }
        }

        return parent::getProductThumbnail()->resize(75);
    }

    protected function _validateImageOptionCode($code)
    {
        if(strpos($code, 'option_') !== false){
            $postfix = str_replace('option_', '', $code);
            if(is_numeric($postfix)){
                return true;
            }
        }
        return false;
    }
}