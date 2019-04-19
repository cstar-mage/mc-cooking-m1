<?php
require_once(Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'CreateController.php');
class BroSolutions_RecreateOrder_Adminhtml_Sales_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController
{
    public function loadBlockAction()
    {
        $request = $this->getRequest();
        try {
            $this->_initSession()
                ->_processData();
        }
        catch (Mage_Core_Exception $e){
            $this->_reloadQuote();
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e){
            $this->_reloadQuote();
            $this->_getSession()->addException($e, $e->getMessage());
        }


        $asJson= $request->getParam('json');
        $block = $request->getParam('block');
        $update = $this->getLayout()->getUpdate();
        if ($asJson) {
            $update->addHandle('adminhtml_sales_order_create_load_block_json');
        } else {
            $update->addHandle('adminhtml_sales_order_create_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if(in_array('card_validation', $blocks) !== false){
                $blocks[] = 'totals';
            }
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                $update->addHandle('adminhtml_sales_order_create_load_block_' . $block);
            }
        }
        $this->loadLayoutUpdates()->generateLayoutXml()->generateLayoutBlocks();
        $result = $this->getLayout()->getBlock('content')->toHtml();
        if ($request->getParam('as_js_varname')) {
            Mage::getSingleton('adminhtml/session')->setUpdateResult($result);
            $this->_redirect('*/*/showUpdateResult');
        } else {
            $this->getResponse()->setBody($result);
        }
    }
}