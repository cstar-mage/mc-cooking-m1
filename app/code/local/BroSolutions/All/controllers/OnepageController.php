<?php
require_once 'Mage'.DS.'Checkout'.DS.'controllers'.DS.'OnepageController.php';
class BroSolutions_All_OnepageController extends Mage_Checkout_OnepageController
{

    public function indexAction()
    {
        Mage::getSingleton('customer/session')->setWasRedirectedFromCheckout(true);
        $customerLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        if(!$customerLoggedIn){
            Mage::getSingleton('core/session')->addNotice('Please login first.');
            $this->_redirect('customer/account/login');
            return;
        }
        return parent::indexAction();
    }
}
