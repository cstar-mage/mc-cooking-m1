<?php
class MW_Storecreditpro_Adminhtml_Storecreditpro_CustomerController extends Mage_Adminhtml_Controller_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
       /* if (!Mage::helper('storecredit')->moduleEnabled() && $this->getRequest()->getActionName() != 'noroute') {
            $this->_forward('noroute');
        }*/
        return $this;
    }

    /**
     * History Ajax Action
     */
    public function historyAction()
    {
        $customerId = $this->getRequest()->getParam('id', 0);
        $history = $this->getLayout()
            ->createBlock('storecreditpro/adminhtml_customer_edit_tab_storecredit_history', '',
                array('customer_id' => $customerId));
        $this->getResponse()->setBody($history->toHtml());
    }

    /**
     * History Grid Ajax Action
     *
     */
    public function historyGridAction()
    {
        $customerId = $this->getRequest()->getParam('id', 0);
        $history = $this->getLayout()
            ->createBlock('storecreditpro/adminhtml_customer_edit_tab_storecredit_history_grid', '',
                array('customer_id' => $customerId));
        $this->getResponse()->setBody($history->toHtml());
    }
    protected function _isAllowed()
    {
    	return true;
    }
}
