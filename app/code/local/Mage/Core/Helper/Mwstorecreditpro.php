<?php
class Mage_Core_Helper_Mwstorecreditpro extends Mage_Core_Helper_Abstract
{
	public function moduleEnabled()
	{
		return Mage::getStoreConfig('storecreditpro/config/enabled');
	}
	
	public function formOnepageCheckoutCredits()
	{
		$enable = $this->moduleEnabled();
		$template = Mage::app()->getLayout()->createBlock('storecreditpro/storecredit')->setTemplate('mw_storecreditpro/checkout/onepage/credit.phtml')->toHtml();
		if(Mage::helper('core')->isModuleEnabled('MW_Storecreditpro') && $enable)  
			return '<div  id="mw-checkout-payment-storecredit">'.$template.'</div>';
		else return '';
	}
	public function formShoppingCartCredits()
	{
		$enable = $this->moduleEnabled();
		$template = Mage::app()->getLayout()->createBlock('storecreditpro/storecredit')->setTemplate('mw_storecreditpro/checkout/cart/credit.phtml')->toHtml();
		if(Mage::helper('core')->isModuleEnabled('MW_Storecreditpro') && $enable)  
			return $template;
		else return '';
	}
	public function buyCreditOnepageReview()
	{
		$enable = $this->moduleEnabled();
		if(Mage::helper('core')->isModuleEnabled('MW_Storecreditpro') && $enable)
			return Mage::app()->getLayout()->createBlock('core/template')->setTemplate('mw_storecreditpro/checkout/onepage/review/totals/buy_credit.phtml')->toHtml();
		else return '';
	}
	public function buyCreditShoppingCart()
	{
		$enable = $this->moduleEnabled();
		if(Mage::helper('core')->isModuleEnabled('MW_Storecreditpro') && $enable)
			return Mage::app()->getLayout()->createBlock('core/template')->setTemplate('mw_storecreditpro/checkout/cart/buy_credit.phtml')->toHtml();
		else return '';
	}
	public function buyCreditCreateOrderAmin()
	{
		$enable = $this->moduleEnabled();
		if(Mage::helper('core')->isModuleEnabled('MW_Storecreditpro') && $enable)
			return Mage::app()->getLayout()->createBlock('adminhtml/sales_order_create_totals')->setTemplate('mw_storecreditpro/sales/order/create/buy_credit.phtml')->toHtml();
		else return '';
	}
}