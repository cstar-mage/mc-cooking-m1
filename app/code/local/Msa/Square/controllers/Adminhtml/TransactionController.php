<?php
class Msa_Square_Adminhtml_TransactionController extends Mage_Adminhtml_Controller_Action
{
  public function indexAction()
  {
    $this->loadLayout();
    
    $this->_addContent($this->getLayout()->createBlock('msa_square/adminhtml_transaction'));

    $this->renderLayout();
  }
    
  public function runAction()
  {
    $r = new Msa_Square_Model_Observer();
    $r->getSquareTransactions();

    $this->_redirect('*/*/index');
  }
}