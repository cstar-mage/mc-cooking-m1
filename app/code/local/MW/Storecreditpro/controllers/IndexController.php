<?php
class MW_Storecreditpro_IndexController extends Mage_Core_Controller_Front_Action
{
	const EMAIL_TO_RECIPIENT_TEMPLATE_XML_PATH 	= 'storecreditpro/email_notifications/recipient_template';
    const XML_PATH_EMAIL_IDENTITY				= 'storecreditpro/email_notifications/email_sender';
    
 	public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        $action = $this->getRequest()->getActionName();
        if (!preg_match('/^(create|login|logoutSuccess|forgotpassword|forgotpasswordpost|confirm|confirmation)/i', $action)) {
            if (!Mage::getSingleton('customer/session')->authenticate($this)) {
                $this->setFlag('', 'no-dispatch', true);
            }
        } else {
            $this->_getSession()->setNoReferer(true);
        }
    }
    public function indexAction()
    {
		$customer_id = Mage::getSingleton("customer/session")->getCustomer()->getId();
		Mage::helper('storecreditpro')->checkAndInsertCustomerId($customer_id);
		$this->loadLayout();     
		$this->renderLayout();
    }
    
	public function emailAction()
	{
		$store_id = Mage::app()->getStore()->getId();
		if(!(Mage::helper('storecreditpro')->moduleEnabled()))
		{
			$this->norouteAction();
			return;
		}

		$customer_id = Mage::getSingleton("customer/session")->getCustomer()->getId();
		$credit_updated_notification = $this->getRequest()->getPost('credit_updated_notification');
		
		if($credit_updated_notification == null) $$credit_updated_notification = 0;
		Mage::getModel('storecreditpro/customer')->load($customer_id)->setCreditUpdatedNotification($credit_updated_notification)->save();
		
		//Mage::getSingleton('customer/session')->addSuccess($this->__("The Email Notification has been saved."));
		
		//$this->_redirect('storecreditpro/index/index');
	}
	
	public function sendAction()
	{
		$store_id = Mage::app()->getStore()->getId();
		$customer_id = Mage::getSingleton("customer/session")->getCustomer()->getId();
		if(!(Mage::helper('storecreditpro')->moduleEnabled()))
		{
			$this->norouteAction();
			return;
		}
		
		if(!Mage::helper('storecreditpro')->allowSendEmailNotifications($store_id)){
			$this->norouteAction();
			return;
		}
		$this->_initLayoutMessages('customer/session');
		$this->_initLayoutMessages('checkout/session');
		
		if($this->getRequest()->getPost()){
			  $_customer = Mage::getModel('storecreditpro/customer')->load($customer_id);	
			  $credit = (int)$this->getRequest()->getPost("amount");
			  if($_customer->getCreditBalance() >= $credit)
			  	{
				  	$website_id = Mage::getModel('core/store')->load($store_id)->getWebsiteId();
				  	$customer_friend = Mage::getModel('customer/customer')->setWebsiteId($website_id)->loadByEmail($this->getRequest()->getPost("email"));
				  	if($customer_friend->getId() != $_customer->getId())
					{
						if($customer_friend->getId()){
							Mage::helper('storecreditpro')->checkAndInsertCustomerId($customer_friend->getId());	
							$mwCustomer = Mage::getModel('storecreditpro/customer')->load($customer_friend->getId());
							$mwCustomer->addCredit($credit);
							
							$email = Mage::getModel('customer/customer')->load($_customer->getId())->getEmail();
							$transaction_detail = Mage::helper('storecreditpro')->__('Receive credit from friend %s',$email);
							
							$historyData = array('customer_id'=>$customer_friend->getId(),
					    					     'transaction_type'=>MW_Storecreditpro_Model_Type::RECEIVE_FROM_FRIEND, 
										    	 'amount'=>$credit,
										    	 'balance'=>$mwCustomer->getCreditBalance(), 
										    	 'transaction_params'=>$_customer->getId(),
					    	                     'transaction_detail'=>$transaction_detail,
					    	                     'order_id'=>0,
										    	 'transaction_time'=>now(), 
				    							 'expired_time'=>null,
				            					 'remaining_credit'=>0,
										    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
	    	
	    					Mage::getModel('storecreditpro/history')->setData($historyData)->save();

							Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($customer_friend->getId(),$historyData, $store_id);
							
							$_customer->addCredit(-$credit);
							
							$email_friend = Mage::getModel('customer/customer')->load($customer_friend->getId())->getEmail();
							$transaction_detail_friend = Mage::helper('storecreditpro')->__('Send credit to friend %s',$email_friend);
							
							$historyData = array('customer_id'=>$_customer->getId(),
					    					     'transaction_type'=>MW_Storecreditpro_Model_Type::SEND_TO_FRIEND, 
										    	 'amount'=>$credit,
										    	 'balance'=>$_customer->getCreditBalance(), 
										    	 'transaction_params'=>$customer_friend->getId(),
					    	                     'transaction_detail'=>$transaction_detail_friend,
					    	                     'order_id'=>0,
										    	 'transaction_time'=>now(), 
				    							 'expired_time'=>null,
				            					 'remaining_credit'=>0,
										    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
	    	
	    					Mage::getModel('storecreditpro/history')->setData($historyData)->save();
	    						
							
							Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($_customer->getId(),$historyData, $store_id);
							
							Mage::getSingleton('customer/session')->addSuccess($this->__("Your credits were sent successfuly"));
							$this->_redirect('storecreditpro/index/index');
						}else{
								
								$_customer->addCredit(-$credit);
								
								$email_friend = $this->getRequest()->getPost("email");
								$transaction_detail_friend = Mage::helper('storecreditpro')->__('Send credit to friend %s',$email_friend);
							
								$historyData = array('customer_id'=>$_customer->getId(),
						    					     'transaction_type'=>MW_Storecreditpro_Model_Type::SEND_TO_FRIEND, 
											    	 'amount'=>$credit,
											    	 'balance'=>$_customer->getCreditBalance(), 
											    	 'transaction_params'=>$this->getRequest()->getPost("email"),
						    	                     'transaction_detail'=>$transaction_detail_friend,
						    	                     'order_id'=>0,
											    	 'transaction_time'=>now(), 
					    							 'expired_time'=>null,
					            					 'remaining_credit'=>0,
											    	 'status'=>MW_Storecreditpro_Model_Statushistory::PENDING);
	    	
	    						Mage::getModel('storecreditpro/history')->setData($historyData)->save();
	
								
								Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($_customer->getId(),$historyData, $store_id);
								
								Mage::getSingleton('customer/session')->addSuccess($this->__("Your credits were sent successfully"));
						}
						
						if(Mage::helper('storecreditpro')->allowSendEmailNotifications($store_id))
						{
							//Send mail to frend
							$customer_name = Mage::getModel('customer/customer')->load($customer_id)->getName();
							$store_name = Mage::getStoreConfig('general/store_information/name', $store_id);
							$sender = Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $store_id);
							$mailto = $this->getRequest()->getPost('email');
							$name = $this->getRequest()->getPost('name');
							$template = self::EMAIL_TO_RECIPIENT_TEMPLATE_XML_PATH;
							$postObject = new Varien_Object();
							$postObject->setData($this->getRequest()->getPost());
							$postObject->setData('customer_name',$customer_name);
							$postObject->setData('login_link',Mage::app()->getStore($store_id)->getUrl('customer/account/login'));
							$postObject->setData('customer_link',Mage::app()->getStore($store_id)->getUrl('storecreditpro/index/index'));
							$postObject->setData('register_link',Mage::app()->getStore($store_id)->getUrl('customer/account/create'));
							$postObject->setStoreName($store_name);
							Mage::helper('storecreditpro')->_sendEmailTransaction($sender,$mailto, $name, $template, $postObject->getData(),$store_id);
						}
					
					}else
					{
						Mage::getSingleton('customer/session')->addError($this->__("You can not send credits to yourself"));
					}
			  	}else{
			  		
			  		Mage::getSingleton('customer/session')->addError($this->__("You do not have enough credit to send to your friend"));
			  	}
		}else{
			Mage::getSingleton('customer/session')->addError($this->__("You do not have permission!"));
		}
		$this->_redirect('storecreditpro/index/index');
	}
	
	public function dataAction()
	{
		$check = 0;
		if(Mage::getStoreConfig('mw_storecredit_id') == 1 ) $check = 1;
		if(Mage::helper('core')->isModuleEnabled('MW_Credit') && !Mage::helper('core')->isModuleEnabled('MW_Affiliate') && $check == 0)
		{
			$collections = Mage::getModel("credit/credithistory")->getCollection();
			
			foreach ($collections as $collection) {
				
				$order_id = 0;
				$customer_id = $collection->getCustomerId();
				$type_transaction = $collection->getTypeTransaction();
				
				$amount = $collection->getAmount();
				$end_transaction = $collection->getEndTransaction();
				$transaction_detail = $collection->getTransactionDetail();
				$transaction_time = $collection->getCreatedTime();
				$status = $collection->getStatus();
				
				if($status == MW_Credit_Model_OrderStatus::PENDING){
					
					$status = MW_Storecreditpro_Model_Statushistory::PENDING;
					
				}else if($status == MW_Credit_Model_OrderStatus::COMPLETE){
					
					$status = MW_Storecreditpro_Model_Statushistory::COMPLETE;
					
				}else if(status == MW_Credit_Model_OrderStatus::CANCELED){
					
					$status = MW_Storecreditpro_Model_Statushistory::CANCELLED;
					
				};
				
				if($type_transaction == MW_Credit_Model_TransactionType::ADMIN_CHANGE){
					if($amount < 0) {
						$type_transaction = MW_Storecreditpro_Model_Type::ADMIN_SUBTRACT;
						$transaction_detail = Mage::helper('storecreditpro')->__('Admin update credit');
						
					}else{
						$type_transaction = MW_Storecreditpro_Model_Type::ADMIN_ADDITION;
						$transaction_detail = Mage::helper('storecreditpro')->__('Admin update credit');
					}
				}else if($type_transaction == MW_Credit_Model_TransactionType::REFUND_PRODUCT){
					
					$order_id = Mage::getModel("sales/order")->loadByIncrementId($transaction_detail)->getId();
					$transaction_detail = Mage::helper('storecreditpro')->__('Restore spent credit for refunded order %s',$transaction_detail);
					$type_transaction = MW_Storecreditpro_Model_Type::REFUND_ORDER_ADD_CREDIT_CHECKOUT;
					
				}else if($type_transaction == MW_Credit_Model_TransactionType::RECEIVE_FROM_FRIEND){
					
					$email = Mage::getModel('customer/customer')->load($detail)->getEmail();
					$transaction_detail = Mage::helper('storecreditpro')->__('Receive credit from friend %s',$email);
					$type_transaction = MW_Storecreditpro_Model_Type::RECEIVE_FROM_FRIEND;
					
				}else if($type_transaction == MW_Credit_Model_TransactionType::SEND_TO_FRIEND){
					
					$email = Mage::getModel('customer/customer')->load($detail)->getEmail();
					$transaction_detail = Mage::helper('storecreditpro')->__('Send credit to friend %s',$email);
					$type_transaction = MW_Storecreditpro_Model_Type::SEND_TO_FRIEND;
					
				}else if($type_transaction == MW_Credit_Model_TransactionType::USE_TO_CHECKOUT){
					
					$order_id = Mage::getModel("sales/order")->loadByIncrementId($transaction_detail)->getId();
					$transaction_detail = Mage::helper('storecreditpro')->__('Use to checkout order %s',$transaction_detail);
					$type_transaction = MW_Storecreditpro_Model_Type::USE_TO_CHECKOUT;
					
				}else if($type_transaction == MW_Credit_Model_TransactionType::BUY_CREDIT){
					
					$order_id = Mage::getModel("sales/order")->loadByIncrementId($transaction_detail)->getId();
					$transaction_detail = Mage::helper('storecreditpro')->__('You buy credit in order %s',$transaction_detail);
					$type_transaction = MW_Storecreditpro_Model_Type::BUY_CREDIT;
					
				}
				
				$historyData = array('customer_id'=>$customer_id,
		    					     'transaction_type'=>$type_transaction, 
							    	 'amount'=>abs($amount),
							    	 'balance'=>$end_transaction, 
							    	 'transaction_params'=>'',
		    	                     'transaction_detail'=>$transaction_detail,
		    	                     'order_id'=>$order_id,
							    	 'transaction_time'=>$transaction_time, 
	    							 'expired_time'=>null,
	            					 'remaining_credit'=>0,
							    	 'status'=>$status);
    
    			Mage::getModel('storecreditpro/history')->setData($historyData)->save();
    			
			}
			
			$collection_customers = Mage::getModel('credit/creditcustomer')->getCollection();
			
			foreach ($collection_customers as $collection_customer) {
				$credit = $collection_customer->getCredit();
				if($credit > 0){
					$member_id = $collection_customer->getCustomerId();
					Mage::helper('storecreditpro')->checkAndInsertCustomerId($member_id);
					Mage::getModel('storecreditpro/customer')->load($member_id)->setCreditBalance($credit)->save();
					
				}
			}
			
			Mage::getModel('core/config')->saveConfig('mw_storecredit_id',1);
			Mage::getConfig()->reinit();
		}
	}
	
}