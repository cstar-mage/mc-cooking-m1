<?php
class MW_Storecreditpro_Model_Type extends Varien_Object
{
	const BUY_CREDIT				        = 1;
	const USE_TO_CHECKOUT			        = 2;
	const ADMIN_ADDITION			        = 3;
	const ADMIN_SUBTRACT			        = 4;
	const RECEIVE_FROM_FRIEND		        = 5;
	const SEND_TO_FRIEND			        = 6;
	const REFUND_ORDER_ADD_CREDIT 	        = 7;
	const REFUND_ORDER_ADD_CREDIT_CHECKOUT 	= 8;
	const ORDER_CANCELLED_ADD_CREDIT 	    = 9;
	const REFUND_ORDER_SUBTRACT_CREDIT 	    = 10;
    
	static public function getTransactionDetail($type, $transaction_params = null, $order_id=null,$is_admin= false)
    {
    	$result = "";
    	switch($type)
    	{
    		case self::BUY_CREDIT:
    			
    			$order = Mage::getModel("sales/order")->loadByIncrementId($transaction_params);
    			$url = Mage::getUrl('sales/order/view',array('order_id'=>$order->getId()));
    			if($is_admin) $url = Mage::getUrl('adminhtml/sales_order/view',array('order_id'=>$order->getId()));
    			$result = Mage::helper('storecreditpro')->__("You earned credit from order <b><a href='%s'>#%s</a></b>",$url,$order->getIncrementId());
    			break;
    		
    		case self::USE_TO_CHECKOUT:
    			
    			$order = Mage::getModel("sales/order")->loadByIncrementId($transaction_params);
    			$url = Mage::getUrl('sales/order/view',array('order_id'=>$order->getId()));
    			if($is_admin) $url = Mage::getUrl('adminhtml/sales_order/view',array('order_id'=>$order->getId()));
    			$result = Mage::helper('storecreditpro')->__("Use to checkout order <b><a href='%s'>#%s</a></b>",$url,$order->getIncrementId());
    			break;
    			
    		case self::ADMIN_ADDITION:
    			
    			$detail = $transaction_params;
    			if($detail == '') $detail = Mage::helper('storecreditpro')->__('Updated by Admin');
    			$result = Mage::helper('storecreditpro')->__("%s",$detail);
    			break;
    			
    		case self::ADMIN_SUBTRACT:
    			
    			$detail = $transaction_params;
    			if($detail == '') $detail = Mage::helper('storecreditpro')->__('Updated by Admin');
    			$result = Mage::helper('storecreditpro')->__("%s",$detail);
    			break;
    		
    		case self::RECEIVE_FROM_FRIEND:
    			
    			$email = Mage::getModel('customer/customer')->load($transaction_params)->getEmail();
				$result = Mage::helper('storecreditpro')->__('Receive credit from friend %s',$email);
    			break;
    			
    		case self::SEND_TO_FRIEND:
    			
    			$email = Mage::getModel('customer/customer')->load($transaction_params)->getEmail();
				$result = Mage::helper('storecreditpro')->__('Send credit to friend %s',$email);
    			break;
    		
    		
    		case self::REFUND_ORDER_ADD_CREDIT:
    			
				$order = Mage::getModel("sales/order")->load($order_id);
    			$url = Mage::getUrl('sales/order/view',array('order_id'=>$order->getId()));
    			if($is_admin) $url = Mage::getUrl('adminhtml/sales_order/view',array('order_id'=>$order->getId()));
    			$result = Mage::helper('storecreditpro')->__("Restore credit for refunded order <b><a href='%s'>#%s</a></b>",$url,$order->getIncrementId());
    			break;
    			
    		case self::REFUND_ORDER_ADD_CREDIT_CHECKOUT:
    			
				$order = Mage::getModel("sales/order")->load($order_id);
    			$url = Mage::getUrl('sales/order/view',array('order_id'=>$order->getId()));
    			if($is_admin) $url = Mage::getUrl('adminhtml/sales_order/view',array('order_id'=>$order->getId()));
    			$result = Mage::helper('storecreditpro')->__("Restore spent credit for refunded order <b><a href='%s'>#%s</a></b>",$url,$order->getIncrementId());
    			break;
    			
    		case self::ORDER_CANCELLED_ADD_CREDIT:
    			
				$order = Mage::getModel("sales/order")->load($order_id);
    			$url = Mage::getUrl('sales/order/view',array('order_id'=>$order->getId()));
    			if($is_admin) $url = Mage::getUrl('adminhtml/sales_order/view',array('order_id'=>$order->getId()));
    			$result = Mage::helper('storecreditpro')->__("Restore spent credit for cancelled order <b><a href='%s'>#%s</a></b>",$url,$order->getIncrementId());
    			break;
    			
    		case self::REFUND_ORDER_SUBTRACT_CREDIT:
    			
    			$order = Mage::getModel("sales/order")->load($order_id);
    			$url = Mage::getUrl('sales/order/view',array('order_id'=>$order->getId()));
    			if($is_admin) $url = Mage::getUrl('adminhtml/sales_order/view',array('order_id'=>$order->getId()));
    			$result = Mage::helper('storecreditpro')->__("Subtract earned credit for refunded order <b><a href='%s'>#%s</a></b>",$url,$order->getIncrementId());
    			break;
    			

    	}
    	return $result;
    	
    }
    
    static public function getAmountWithSign($amount, $type)
    {
    	//$amount = Mage::helper('core')->currency($amount,false,false);
    	$amount = Mage::helper('core')->currency($amount);
    	$result = $amount;
    	switch ($type)
    	{
    		case self::BUY_CREDIT:
    		case self::ADMIN_ADDITION:
    		case self::RECEIVE_FROM_FRIEND:
    		case self::REFUND_ORDER_ADD_CREDIT:
    		case self::ORDER_CANCELLED_ADD_CREDIT:
    		case self::REFUND_ORDER_ADD_CREDIT_CHECKOUT:
				$result = "+".$amount;
				break;
    		case self::SEND_TO_FRIEND:
    		case self::USE_TO_CHECKOUT:
    		case self::ADMIN_SUBTRACT:
    		case self::REFUND_ORDER_SUBTRACT_CREDIT:
    			$result = "-".$amount;
    		break;
    	}
    	return $result;
    }
}