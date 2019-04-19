<?php
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     xwB5BO6b8ADSZdWMZBvj82HAnNdHAZvfJuyG2JiSFP
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
class Aitoc_Aitexporter_Model_Observer
{
    protected $_order = null;


    protected function _getFreqStr($configParam)
    {
        $strToTimeArray = Mage::getModel('aitexporter/system_config_source_cron')->toStrToTimeArray();
        return isset($strToTimeArray[$configParam])?$strToTimeArray[$configParam]:false;
    }

    public function cronExport()
    {
        $processor = Mage::getSingleton('aitexporter/processor');
        if($processor->getProcess())
        {   //another process is running
            return false;
        }
        $collection = Mage::getModel('aitexporter/profile')
            ->loadCronCollection();
        $currentDate = Mage::getModel('core/date')->timestamp();
        $resultProfile = false;
            
        foreach ($collection as $profile)
        {
            $config = $profile->getConfig();
            $strTime = $this->_getFreqStr($config['auto']['cron_frequency']);
            if(!$strTime) {
                // Cron timeout is not set
                continue;
            }

            if ($profile->getCrondate() && (strtotime('-'.$strTime, $currentDate) < strtotime($profile->getCrondate()))) {
                // Too early to export
                continue;
            }
            if($resultProfile == false || $profile->getCrondate() < $resultProfile->getCrondate() ) {
                // Running profile that were not executed longer
                $resultProfile = $profile;
            }
        }
        if(!is_object($resultProfile)) {
            return false;
        }
        $expModel = Mage::getModel('aitexporter/export');
        $expModel
            ->setConfig($resultProfile->getConfig())
            ->setStoreId($resultProfile->getStoreId())
            ->setProfileId($resultProfile->getId())
            ->setIsCron(1)
            ->save();
        $options = array(
            'id' => $expModel->getId()
        );
        $resultProfile->updateDate( 'crondate' )->save();
        $processor->setProcess('export::initExport', $options)->save();
        
    }
    
    public function exportOrderAfterPlace(Varien_Event_Observer $observer)
    {
        if(version_compare(Mage::getVersion(),'1.4.0.0','<'))
        {
            if(($observer->getEvent()->getName() != 'sales_order_save_after') &&
                ($observer->getEvent()->getName() != 'aitcheckoutfields_aitexporter'))
            {
                return false;
            }
        }
        else
        {
            if(($observer->getEvent()->getName() != 'sales_order_save_commit_after') &&
                ($observer->getEvent()->getName() != 'aitcheckoutfields_aitexporter'))
            {
                return false;
            }
        }
        
        if(Mage::helper('aitexporter/aitcheckoutfields')->isEnabled())
        {
            if($observer->getEvent()->getName() != 'aitcheckoutfields_aitexporter')
            {
                return false;
            }
        }

        if (Mage::registry('current_import'))
        {
            return false;
        }
        
        if($this->_order !== null || is_object($this->_order)) {
            return false;
        }

        $order    = $observer->getEvent()->getOrder();
        
        /* @var $order Mage_Sales_Model_Order */
        $origData = $order->getOrigData();

               
        if (!empty($origData))
        {
            if(!Mage::helper('aitexporter/aitcheckoutfields')->isEnabled())
            {
                return false;
            }
        }
        
        //to process order on postdispatch action setting up flag that checkout action was active and order is created
        //some modules may add values to order data that are not set before first sales_order_save_commit_after action and
        //may call this action several times, because of that we store order to process it after all possible actions took place
        $this->_order = $order;
    }
    
    public function onControllerActionPostdispatch($observer)
    {

        if($this->_order == null) {
            //order is not set - we don't process it
            return false;
        }
        $order = $this->_order;
        /* @var $order Mage_Sales_Model_Order */
        
        if($order->getExportedAfterPlace())
        {
            return false;
        }

        $processor = Mage::getSingleton('aitexporter/processor_direct');
        // Prevent re-exporting on Mage_Core_Model_Resource_Transaction::_runCallbacks()
        $order->setExportedAfterPlace(true);
        $collection = Mage::getModel('aitexporter/profile')
            ->loadCheckoutCollection(array($order->getStoreId()));

        foreach ($collection as $profile)
        {
            /* @var $order Mage_Sales_Model_Order */
            $config = $profile->getConfig();

            $config['filter'] = array(
            	'order_id' => $order->getId(), 
                );
            $export = Mage::getModel('aitexporter/export');
            $export
                ->setConfig($config)
                ->setStoreId($profile->getStoreId())
                ->setProfileId($profile->getId())
                ->save();

            $options = array(
                'id' => $export->getId()
            );

            $processor->setProcess('export::initExport', $options)->setForward(1)->save()->run();
               
        }
    }

    public function exportOrderAfterInvoice(Varien_Event_Observer $observer)
    {
        if (Mage::registry('current_import'))
        {
            return false;
        }

        $invoice = $observer->getEvent()->getInvoice();
        /* @var $invoice Mage_Sales_Model_Order_Invoice */
        $order = $invoice->getOrder();
        /* @var $order Mage_Sales_Model_Order */
        $origData = $invoice->getOrigData();

        if (!empty($origData) || $order->getExportedAfterInvoice())
        {
            return false;
        }

        foreach ($order->getItemsCollection() as $item)
        {
            /* @var $item Mage_Sales_Model_Order_Item */
            if (!$item->isDummy() && $item->getQtyOrdered() && $item->getQtyOrdered() != $item->getQtyInvoiced())
            {
                return false;
            }
        }
        $processor = Mage::getSingleton('aitexporter/processor_direct');
        $order->setExportedAfterInvoice(true);
        $collection = Mage::getModel('aitexporter/profile')
            ->loadInvoiceCollection(array($order->getStoreId()));

        foreach ($collection as $profile)
        {
            $config = $profile->getConfig();

            $config['filter'] = array(
            	'order_id' => $order->getId(), 
                );
            $export = Mage::getModel('aitexporter/export');
            $export
                ->setConfig($config)
                ->setStoreId($profile->getStoreId())
                ->setProfileId($profile->getId())
                ->save();
            $options = array(
                'id' => $export->getId()
            );
            $processor->setProcess('export::initExport', $options)->setForward(1)->save()->run();
        }
    }
}