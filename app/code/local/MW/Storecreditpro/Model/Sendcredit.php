<?php
class MW_Storecreditpro_Model_Sendcredit extends Varien_Object
{
	public function updateWhenLogin($argv)
	{
		$customer_id = $argv->getModel()->getId();
		$customer = Mage::getModel('customer/customer') ->load($customer_id);
		$store_id = $customer ->getStoreId();
		$email = $customer ->getEmail();
		$this->processUpdate($customer_id,$email,$store_id);
	}
	
	public function updateWhenRegister($argv)
	{
		$customer_id = $argv->getCustomer()->getId();
		$customer = Mage::getModel('customer/customer') ->load($customer_id);
		$store_id = $customer ->getStoreId();
		$email = $customer ->getEmail();
		$this->processUpdate($customer_id,$email,$store_id);
		
	}
	public function processUpdate($customer_id,$email,$store_id)
	{
		Mage::helper('storecreditpro')->checkAndInsertCustomerId($customer_id);
		$customer = Mage::getModel('storecreditpro/customer')->load($customer_id);
        
		$_transactions = Mage::getModel('storecreditpro/history')->getCollection()
					->addFieldToFilter('transaction_params',$email)
					->addFieldToFilter('transaction_type',MW_Storecreditpro_Model_Type::SEND_TO_FRIEND)
					->addFieldToFilter('status',MW_Storecreditpro_Model_Statushistory::PENDING);

		if(sizeof($_transactions) > 0)
		{
			 foreach($_transactions as $_transaction)
			{
				$credit = $_transaction->getAmount();
				$customer->addCredit($credit);
							
				$email = Mage::getModel('customer/customer')->load($_transaction->getCustomerId())->getEmail();
				$transaction_detail = Mage::helper('storecreditpro')->__('Receive credit from friend %s',$email);
				$historyData = array('customer_id'=>$customer_id,
		    					     'transaction_type'=>MW_Storecreditpro_Model_Type::RECEIVE_FROM_FRIEND, 
							    	 'amount'=>$credit,
							    	 'balance'=>$customer->getCreditBalance(), 
							    	 'transaction_params'=>$_transaction->getCustomerId(),
		    	                     'transaction_detail'=>$transaction_detail,
		    	                     'order_id'=>0,
							    	 'transaction_time'=>now(), 
	    							 'expired_time'=>null,
	            					 'remaining_credit'=>0,
							    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
    
    			Mage::getModel('storecreditpro/history')->setData($historyData)->save();
			    
			    $_transaction->setStatus(MW_Storecreditpro_Model_Statushistory::COMPLETE)->setTransactionParams($customer_id)->save();
			    
				Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($customer_id,$historyData, $store_id);
			}
		}
		
		
	}
	
}