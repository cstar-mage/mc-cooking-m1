<?php
class MW_Storecreditpro_Model_Checkout extends Mage_Core_Model_Abstract
{
    public function placeAfter($argv)
    {
    	if(Mage::helper('storecreditpro')->moduleEnabled())
		{
			
			$order = $argv->getOrder();
			$quote = $argv->getQuote();
			
            if (!$quote instanceof Mage_Sales_Model_Quote) {
                $quote = Mage::getModel('sales/quote')
                        ->setSharedStoreIds(array($order->getStoreId()))
                        ->load($order->getQuoteId());
            }
            
			$customer_id = $order->getCustomerId();
			$store_id = Mage::app()->getStore()->getId();
		
			$order_id = $order->getId();
			$order_incrementId = $order->getIncrementId();
					
	    	if($customer_id){
	    		
	    		Mage::helper('storecreditpro')->checkAndInsertCustomerId($customer_id);	
				$_customer = Mage::getModel('storecreditpro/customer')->load($customer_id);
				$credit = $order->getMwStorecredit();
				$mw_credit_buy = $order->getMwStorecreditBuyCredit();

				if($credit > 0)
            	{
	            	$_customer->addCredit(-$credit);
	            	$transaction_detail = Mage::helper('storecreditpro')->__('Use to checkout order #%s',$order->getIncrementId());
	           		$historyData = array('customer_id'=>$_customer->getId(),
			    					     'transaction_type'=>MW_Storecreditpro_Model_Type::USE_TO_CHECKOUT, 
								    	 'amount'=>$credit,
								    	 'balance'=>$_customer->getCreditBalance(), 
								    	 'transaction_params'=>$order_incrementId,
			    	                     'transaction_detail'=>$transaction_detail,
			    	                     'order_id'=>$order_id,
								    	 'transaction_time'=>now(), 
		    							 'expired_time'=>null,
		            					 'remaining_credit'=>0,
								    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
	    	
    				Mage::getModel('storecreditpro/history')->setData($historyData)->save();
					Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($_customer->getId(),$historyData, $store_id);
            	}
	    		if($mw_credit_buy > 0)
            	{
            		$transaction_detail = Mage::helper('storecreditpro')->__('You buy credit in order #%s',$order->getIncrementId());
	           		$historyData = array('customer_id'=>$_customer->getId(),
			    					     'transaction_type'=>MW_Storecreditpro_Model_Type::BUY_CREDIT, 
								    	 'amount'=>$mw_credit_buy,
								    	 'balance'=>$_customer->getCreditBalance(), 
								    	 'transaction_params'=>$order_incrementId,
			    	                     'transaction_detail'=>$transaction_detail,
			    	                     'order_id'=>$order_id,
								    	 'transaction_time'=>now(), 
		    							 'expired_time'=>null,
		            					 'remaining_credit'=>0,
								    	 'status'=>MW_Storecreditpro_Model_Statushistory::PENDING);
	    	
    				Mage::getModel('storecreditpro/history')->setData($historyData)->save();
					
            	}
	    	}
		}
    }
    
    // canceled payment
 	public function cancelOrder($arvgs)
    {
    	$order = $arvgs->getOrder();
    	$order_id = $order->getId();
    	$order_incrementId = $order->getIncrementId();
    	$customer_id = $order->getCustomerId();
    	$store_id = Mage::getModel('customer/customer') ->load($customer_id)->getStoreId();
    	$_customer = Mage::getModel('storecreditpro/customer')->load($customer_id);
    	
    	
    	$_transactions = Mage::getModel('storecreditpro/history')->getCollection()
									->addFieldToFilter('customer_id',$customer_id)
									//->addFieldToFilter('order_id',$order_id) 
									->addFieldToFilter('transaction_params',$order_incrementId)
									->addOrder('transaction_time','ASC')
									->addOrder('history_id','ASC');
		
		foreach($_transactions as $_transaction)
		{
			switch($_transaction->getTransactionType())
			{
				case MW_Storecreditpro_Model_Type::USE_TO_CHECKOUT:
					
					if($_transaction->getTransactionParams() != $order_incrementId) continue;
					
					$transactions_refund = Mage::getModel('storecreditpro/history')->getCollection()
											->addFieldToFilter('customer_id',$customer_id)
											->addFieldToFilter('order_id',$order_id)
											->addFieldToFilter('transaction_type',MW_Storecreditpro_Model_Type::ORDER_CANCELLED_ADD_CREDIT);
											
					if(sizeof($transactions_refund) >0) continue;
					
					$_customer->addCredit($_transaction->getAmount());	
					$transaction_detail = Mage::helper('storecreditpro')->__('Restore spent credit for cancelled order #%s',$order->getIncrementId());
					$historyData = array('customer_id'=>$_customer->getId(),
			    					     'transaction_type'=>MW_Storecreditpro_Model_Type::ORDER_CANCELLED_ADD_CREDIT, 
								    	 'amount'=>$_transaction->getAmount(),
								    	 'balance'=>$_customer->getCreditBalance(), 
								    	 'transaction_params'=>$order_incrementId,
			    	                     'transaction_detail'=>$transaction_detail,
			    	                     'order_id'=>$order_id,
								    	 'transaction_time'=>now(), 
		    							 'expired_time'=>null,
		            					 'remaining_credit'=>0,
								    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
    
    				Mage::getModel('storecreditpro/history')->setData($historyData)->save();
    				Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($_customer->getId(),$historyData, $store_id);
					break;
					
				case MW_Storecreditpro_Model_Type::BUY_CREDIT:
					
					if($_transaction->getTransactionParams() != $order_incrementId) continue;
					
					$status = MW_Storecreditpro_Model_Statushistory::CANCELLED;
					$_transaction->setTransactionTime(now())->setStatus($status)->save();
					break;
			}
		}
    }
    public function orderSaveAfter($arvgs)
    {
    	$order = $arvgs->getOrder();
    	$store_id = $order->getStoreId();
	  
    	if($order->getStatus() == 'canceled')
		{
			$this ->cancelOrder($arvgs);
		}
    	if($order->getStatus() == 'complete')
		{
			$this ->completeOrder($arvgs);
		}
	
		if($order->getStatus() == 'closed')
		{
			$this ->refundOrder($arvgs);	
		}
    }
	public function completeOrder($arvgs)
    {
    	$order = $arvgs->getOrder();
    	$order_id = $order->getId();
    	$order_incrementId = $order->getIncrementId();
    	$customer_id = $order->getCustomerId();
    	$store_id = Mage::getModel('customer/customer') ->load($customer_id)->getStoreId();
    	$_customer = Mage::getModel('storecreditpro/customer')->load($customer_id);
    	
    	
    	$_transactions = Mage::getModel('storecreditpro/history')->getCollection()
									->addFieldToFilter('customer_id',$customer_id)
									//->addFieldToFilter('order_id',$order_id)
									->addFieldToFilter('transaction_params',$order_incrementId)  
									->addOrder('transaction_time','ASC')
									->addOrder('history_id','ASC');
		
		foreach($_transactions as $_transaction)
		{
			switch($_transaction->getTransactionType())
			{
				case MW_Storecreditpro_Model_Type::USE_TO_CHECKOUT:
					
					if($_transaction->getTransactionParams() != $order_incrementId) continue;
					$_transaction->setTransactionTime(now())->setBalance($_customer->getCreditBalance())->save();
					break;
					
				case MW_Storecreditpro_Model_Type::BUY_CREDIT:
					
					$status = MW_Storecreditpro_Model_Statushistory::COMPLETE;
					if($_transaction->getStatus() == $status) continue;
					if($_transaction->getTransactionParams() != $order_incrementId) continue;
					
					$_customer->addCredit($_transaction->getAmount());	
					$_transaction->setTransactionTime(now())->setBalance($_customer->getCreditBalance())->setStatus($status)->save();
					
					$historyData = array('customer_id'=>$_customer->getId(),
			    					     'transaction_type'=>MW_Storecreditpro_Model_Type::BUY_CREDIT, 
								    	 'amount'=>$_transaction->getAmount(),
								    	 'balance'=>$_customer->getCreditBalance(), 
								    	 'transaction_params'=>$order_incrementId,
			    	                     'transaction_detail'=>$_transaction->getTransactionDetail(),
			    	                     'order_id'=>$order_id,
								    	 'transaction_time'=>now(), 
		    							 'expired_time'=>null,
		            					 'remaining_credit'=>0,
								    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
    
    				Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($_customer->getId(),$historyData, $store_id);
					break;
			}
		}
					
    }
    public function refundOrder($arvgs)
    {
    	$order = $arvgs->getOrder();
    	$order_id = $order->getId();
    	$order_incrementId = $order->getIncrementId();
    	$customer_id = $order->getCustomerId();
    	$store_id = Mage::getModel('customer/customer') ->load($customer_id)->getStoreId();
    	$restore_spent_credits = Mage::helper('storecreditpro')->getRestoreSpentCreditsWhenRefundConfigStore($store_id);
    	$subtract_earn_credits = Mage::helper('storecreditpro')->getSubtractEarnCreditsWhenRefundConfigStore($store_id);
    	$_customer = Mage::getModel('storecreditpro/customer')->load($customer_id);
    	
    	$_transactions = Mage::getModel('storecreditpro/history')->getCollection()
									->addFieldToFilter('customer_id',$customer_id)
									//->addFieldToFilter('order_id',$order_id)
									->addFieldToFilter('transaction_params',$order_incrementId) 
									->addOrder('transaction_time','ASC')
									->addOrder('history_id','ASC');
		
		foreach($_transactions as $_transaction)
		{
			switch($_transaction->getTransactionType())
			{
				case MW_Storecreditpro_Model_Type::USE_TO_CHECKOUT:
					
					$transactions_refund = Mage::getModel('storecreditpro/history')->getCollection()
											->addFieldToFilter('customer_id',$customer_id)
											->addFieldToFilter('order_id',$order_id)
											->addFieldToFilter('transaction_type',MW_Storecreditpro_Model_Type::REFUND_ORDER_ADD_CREDIT_CHECKOUT);
											
					if(sizeof($transactions_refund) >0) continue;
					
					if($_transaction->getTransactionParams() != $order_incrementId || !$restore_spent_credits) continue;
					
					$_customer->addCredit($_transaction->getAmount());	
					$transaction_detail = Mage::helper('storecreditpro')->__('Restore spent credit for refunded order #%s',$order->getIncrementId());
					$historyData = array('customer_id'=>$_customer->getId(),
			    					     'transaction_type'=>MW_Storecreditpro_Model_Type::REFUND_ORDER_ADD_CREDIT_CHECKOUT, 
								    	 'amount'=>$_transaction->getAmount(),
								    	 'balance'=>$_customer->getCreditBalance(), 
								    	 'transaction_params'=>$order_incrementId,
			    	                     'transaction_detail'=>$transaction_detail,
			    	                     'order_id'=>$order_id,
								    	 'transaction_time'=>now(), 
		    							 'expired_time'=>null,
		            					 'remaining_credit'=>0,
								    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
    
    				Mage::getModel('storecreditpro/history')->setData($historyData)->save();
    				Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($_customer->getId(),$historyData, $store_id);
					break;
					
				
				case MW_Storecreditpro_Model_Type::BUY_CREDIT:
					
					$status_complete = MW_Storecreditpro_Model_Statushistory::COMPLETE;
					
					$transactions_refund = Mage::getModel('storecreditpro/history')->getCollection()
											->addFieldToFilter('customer_id',$customer_id)
											->addFieldToFilter('order_id',$order_id)
											->addFieldToFilter('transaction_type',MW_Storecreditpro_Model_Type::REFUND_ORDER_SUBTRACT_CREDIT);
											
					if(sizeof($transactions_refund) >0) continue;
					
					if($_transaction->getTransactionParams() != $order_incrementId || !$subtract_earn_credits || $_transaction->getStatus() != $status_complete) continue;
					
					$_customer->addCredit(-$_transaction->getAmount());	
					$transaction_detail = Mage::helper('storecreditpro')->__('Subtract earn credit for refunded order #%s',$order->getIncrementId());
					$historyData = array('customer_id'=>$_customer->getId(),
			    					     'transaction_type'=>MW_Storecreditpro_Model_Type::REFUND_ORDER_SUBTRACT_CREDIT, 
								    	 'amount'=>$_transaction->getAmount(),
								    	 'balance'=>$_customer->getCreditBalance(), 
								    	 'transaction_params'=>$order_incrementId,
			    	                     'transaction_detail'=>$transaction_detail,
			    	                     'order_id'=>$order_id,
								    	 'transaction_time'=>now(), 
		    							 'expired_time'=>null,
		            					 'remaining_credit'=>0,
								    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
    
    				Mage::getModel('storecreditpro/history')->setData($historyData)->save();
    				Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($_customer->getId(),$historyData, $store_id);
    				
					break;
			}
		}
    }
}