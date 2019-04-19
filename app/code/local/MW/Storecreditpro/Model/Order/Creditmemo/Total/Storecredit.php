<?php
class MW_Storecreditpro_Model_Order_Creditmemo_Total_Storecredit extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {

        $order = $creditmemo->getOrder();

        $totalDiscountAmount     = $order->getMwStorecreditDiscountShow();
        $baseTotalDiscountAmount = $order->getMwStorecreditDiscount();
        
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $totalDiscountAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $baseTotalDiscountAmount);
        
        $creditmemo->setMwStorecredit($order->getMwStorecredit());
        $creditmemo->setMwStorecreditBuyCredit($order->getMwStorecreditBuyCredit());
        $creditmemo->setMwStorecreditDiscount($baseTotalDiscountAmount);
        $creditmemo->setMwStorecreditDiscountShow($totalDiscountAmount);

        
        return $this;
    }
}
