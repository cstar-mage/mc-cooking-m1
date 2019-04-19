<?php
class BroSolutions_All_Block_Checkout_Onepage_Payment_Methods extends Mage_Checkout_Block_Onepage_Payment_Methods
{
    const FREE_PAYMENT_METHOD_CODE = 'free';
    protected function _canUseMethod($method)
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $subtotal = $quote->getSubtotal();
        $giftCardsDiscount = $quote->getGiftcardsDiscount();
        if($subtotal <= $giftCardsDiscount){
            $methodCode = $method->getCode();
            if($methodCode != self::FREE_PAYMENT_METHOD_CODE){
                return false;
            }
        }
        return $method && $method->canUseCheckout() && parent::_canUseMethod($method);
    }
}