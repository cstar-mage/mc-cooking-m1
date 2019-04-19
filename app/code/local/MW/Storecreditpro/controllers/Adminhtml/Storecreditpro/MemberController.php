<?php

class MW_Storecreditpro_Adminhtml_Storecreditpro_MemberController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('customer/storecreditpro/member');
    }
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('customer/storecreditpro')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}

	public function editAction() {
		
		$id     =  $this->getRequest()->getParam('id');
		Mage::helper('storecreditpro')->checkAndInsertCustomerId($id);
		
		$model  = Mage::getModel('storecreditpro/customer')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('storecredit_data_member', $model);

			$this->loadLayout();
			$this->_setActiveMenu('customer/storecreditpro');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('storecreditpro/adminhtml_member_edit'))
				->_addLeft($this->getLayout()->createBlock('storecreditpro/adminhtml_member_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('storecreditpro')->__('Member does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function transactionAction()
	{
        $this->getResponse()->setBody($this->getLayout()->createBlock('storecreditpro/adminhtml_member_edit_tab_transaction')->toHtml());
	}
	public function saveAction() {
		$data = $this->getRequest()->getPost();
		if ($data) {
		    $data = $this->getRequest()->getPost();
			$member_id = $this->getRequest()->getParam('id');
			try {	
				if($member_id!=''){	
					 
					 $_customer = Mage::getModel('storecreditpro/customer')->load($member_id);
					 $store_id = Mage::getModel('customer/customer')->load($_customer->getId())->getStoreId();
    				 $oldCredit = $_customer->getCreditBalance();
    				 $amount = $data['mw_storecredit_amount'];
			    	 $action = $data['mw_storecredit_action'];
			    	 $comment = $data['mw_storecredit_comment'];
			    	 $newCredit = $oldCredit + $amount * $action;
			    	 
					if($newCredit < 0) $newCredit = 0;
			    	$amount = abs($newCredit - $oldCredit);
			    	
			    	if($amount > 0){
				    	$detail = $comment;
						$_customer->setData('credit_balance',$newCredit)->save();
				    	$balance = $_customer->getCreditBalance();
				    	
					
				    	$historyData = array('customer_id'=>$member_id,
				    					     'transaction_type'=>($action>0)?MW_Storecreditpro_Model_Type::ADMIN_ADDITION:MW_Storecreditpro_Model_Type::ADMIN_SUBTRACT, 
									    	 'amount'=>$amount,
									    	 'balance'=>$balance, 
									    	 'transaction_params'=>$detail,
				    	                     'transaction_detail'=>$detail,
				    	                     'order_id'=>0,
									    	 'transaction_time'=>now(), 
	    									 'expired_time'=>null,
		            						 'remaining_credit'=>0,
									    	 'status'=>MW_Storecreditpro_Model_Statushistory::COMPLETE);
				    	
				    	Mage::getModel('storecreditpro/history')->setData($historyData)->save();
				    	
						Mage::helper('storecreditpro')->sendEmailCustomerCreditChanged($_customer->getId(),$historyData, $store_id);
			    	}
    				 
				}	
				
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('storecreditpro')->__('The member has successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);
				
				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
					return;
				}
				$this->_redirect('*/*/');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('storecreditpro')->__('Unable to find member to save'));
        $this->_redirect('*/*/');
	}

    public function exportCsvAction()
    {
        $fileName   = 'storecredit_member.csv';
        $content    = $this->getLayout()->createBlock('storecreditpro/adminhtml_member_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'storecredit_member.xml';
        $content    = $this->getLayout()->createBlock('storecreditpro/adminhtml_member_grid')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
}