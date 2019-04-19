<?php
class BroSolutions_RecreateOrder_Model_Observer
{
    public function setDiscount($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $origOrderId = $quote->getOrigOrderId();
        if($origOrderId){
            $grandTotal = $quote->getGrandTotal();
            $payment = $quote->getPayment();
            $discountAmount = 0 ;
            $quoteId = $quote->getId();
            $isFreePayment = false;
            if(($payment instanceof Mage_Sales_Model_Quote_Payment) && $payment->getMethod() == 'freepayment'){
                $isFreePayment = true;
                $discountAmount = - Mage::helper('recreateorder')->getDifferrenceWithPrevOrder($grandTotal);
            }
            if($quoteId && $isFreePayment) {
                $total = $quote->getBaseSubtotal();
                $quote->setSubtotal(0);
                $quote->setBaseSubtotal(0);
                $quote->setSubtotalWithDiscount(0);
                $quote->setBaseSubtotalWithDiscount(0);
                $quote->setGrandTotal(0);
                $quote->setBaseGrandTotal(0);
                $canAddItems = $quote->isVirtual()? ('billing') : ('shipping');
                foreach ($quote->getAllAddresses() as $address) {
                    $address->setSubtotal(0);
                    $address->setBaseSubtotal(0);
                    $address->setGrandTotal(0);
                    $address->setBaseGrandTotal(0);
                    $address->collectTotals();
                    $quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
                    $quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());
                    $quote->setSubtotalWithDiscount(
                        (float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount()
                    );
                    $quote->setBaseSubtotalWithDiscount(
                        (float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount()
                    );
                    $quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
                    $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());
                    $quote->save();
                    $quote->setGrandTotal($quote->getBaseSubtotal()-$discountAmount)
                        ->setBaseGrandTotal($quote->getBaseSubtotal()-$discountAmount)
                        ->setSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
                        ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
                        ->save();


                    if($address->getAddressType() == $canAddItems) {
                        $address->setSubtotalWithDiscount((float) $address->getSubtotalWithDiscount()-$discountAmount);
                        $address->setGrandTotal((float) $address->getGrandTotal()-$discountAmount);
                        $address->setBaseSubtotalWithDiscount((float) $address->getBaseSubtotalWithDiscount()-$discountAmount);
                        $address->setBaseGrandTotal((float) $address->getBaseGrandTotal()-$discountAmount);
                        if($address->getDiscountDescription()){
                            $address->setDiscountAmount(-($address->getDiscountAmount()-$discountAmount));
                            $address->setDiscountDescription($address->getDiscountDescription().', Basing on previous order');
                            $address->setBaseDiscountAmount(-($address->getBaseDiscountAmount()-$discountAmount));
                        }else {
                            $address->setDiscountAmount(-($discountAmount));
                            $address->setDiscountDescription('Basing on previous order');
                            $address->setBaseDiscountAmount(-($discountAmount));
                        }
                        $address->save();
                    }
                }

                foreach($quote->getAllItems() as $item){
                    $rat = $item->getPriceInclTax() / $total;
                    $ratdisc = $discountAmount * $rat;
                    $item->setDiscountAmount(($item->getDiscountAmount()+$ratdisc) * $item->getQty());
                    $item->setBaseDiscountAmount(($item->getBaseDiscountAmount()+$ratdisc) * $item->getQty())->save();

                }
            }


        }
    }
}