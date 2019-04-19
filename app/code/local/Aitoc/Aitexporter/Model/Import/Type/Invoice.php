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
class Aitoc_Aitexporter_Model_Import_Type_Invoice extends Aitoc_Aitexporter_Model_Import_Type_Complex implements Aitoc_Aitexporter_Model_Import_Type_Interface
{
    /**
     * 
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $config
     * @param string $orderIncrementId
     * @return boolean is valid 
     */
    public function validate(SimpleXMLElement $orderXml, $itemXpath, array $config, $orderIncrementId = '')
    {
        $isValid    = true;
        $invoiceXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($invoiceXml->asXML())))
        {
            return $isValid;
        }

        if (empty($invoiceXml->increment_id))
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setError(Mage::helper('aitexporter')->__('"increment_id" field does not exist in path %s. Invoice will not be created', $itemXpath))
                ->save();
            $isValid = false;
        }
        else 
        {
            $incrementId = (string)$invoiceXml->increment_id;
            $invoice     = Mage::getModel('sales/order_invoice')->loadByIncrementId($incrementId);

            if ($invoice->getId())
            {
                $order = Mage::getModel('sales/order')->load($invoice->getOrderId());

                if ($order->getIncrementId() != $orderIncrementId)
                {
                    Mage::getModel('aitexporter/import_error')
                        ->setOrderIncrementId($orderIncrementId)
                        ->setError(Mage::helper('aitexporter')->__('Invoice with increment_id "%s" already exists for order %s. Invoice will not be created', $incrementId, $order->getIncrementId()))
                        ->save();
                    $isValid = false;
                }
            }
        }

        $isChildredValid = $this->_validateChildren($orderXml, $itemXpath, $config, $orderIncrementId);
        if (!$isChildredValid)
        {
            $isValid = false;
        }          
        
        return $isValid;
    }

    /**
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $importConfig
     * @param Mage_Sales_Model_Order $parentItem
     */
    public function import(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem = null)
    {
        /* @var $parentItem Mage_Sales_Model_Order */
        $invoice = Mage::getModel('sales/order_invoice');
        /* @var $invoice Mage_Sales_Model_Order_Invoice */
        $invoiceXml = current($orderXml->xpath($itemXpath));
        /* @var $invoiceXml SimpleXMLElement */

        // empty items from CSV 
        if (!trim(strip_tags($invoiceXml->asXML())))
        {
            return false;
        }

        $incrementId = (string)$invoiceXml->increment_id;
        if ($incrementId) 
        {
            $invoiceCheck = Mage::getModel('sales/order_invoice')->loadByIncrementId($incrementId);

            // Invoice with increment Id exists for another order
            if ($invoiceCheck->getId())
            {
                //$incrementId = null;
                return Aitoc_Aitexporter_Model_Import_Type_Order::STATE_FAIL;
            }
        }

        $incrementPrefix = false;
        $incrementId = false;
        foreach ($invoiceXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'entity_id':
                case 'order_id':
                case 'invoice_status_id':
                case 'real_order_id':
                //case 'increment_id':
                    break;
                    
                case 'increment_id':
                    $incrementId = (string)$field;
                    $invoice->setData($field->getName(), (string)$field);
                    break;
                    
                case 'increment_prefix':
                    $incrementPrefix = (string)$field;
                    break;

                case 'shipping_address_id':
                case 'billing_address_id':
                case 'transaction_id':
                    $invoice->setData('old_'.$field->getName(), (string)$field);
                    break;

                case 'store_id':
                    $invoice->setStoreId($parentItem->getStoreId());
                    break;

                case 'customer_id':
                    $invoice->setCustomerId($parentItem->getCustomerId());
                    break;

                default:
                    $invoice->setData($field->getName(), (string)$field);
                    break;
            }
        }

        $importInvoices = $parentItem->getImportInvoices();
        if (empty($importInvoices))
        {
            $importInvoices = array();
        }
        $importInvoices[] = $invoice;
        $parentItem->setImportInvoices($importInvoices);

        $this->_importChildren($orderXml, $itemXpath, $config, $invoice);
        
        $this->_workWithIncrement($parentItem, 'invoice', $incrementPrefix, $incrementId);
    }

    public function getConfigPath()
    {
        return 'order/invoice';
    }

    public function getXpath()
    {
        return '/invoices/invoice';
    }

    /**
     * @see Aitoc_Aitexporter_Model_Import_Type_Interface::getErrorType()
     * return string
     */
    public function getErrorType()
    {
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    public function postProcess(Mage_Sales_Model_Order $order)
    {
        $importInvoices = $order->getImportInvoices();
        if (empty($importInvoices))
        {
            $importInvoices = array();
        }

        $addressIds = array();
        foreach ($order->getAddressesCollection() as $address)
        {
            if ($address->getOldEntityId())
            {
                $addressIds[$address->getOldEntityId()] = $address->getId();
            }
        }

        $orderItems    = $order->getItemsCollection();
        $orderItemsIds = array();
        foreach ($orderItems as $orderItem)
        {
            if ($orderItem->getOldItemId())
            {
                $orderItemsIds[$orderItem->getOldItemId()] = $orderItem;
            }
        }

        $shipments   = $order->getShipmentsCollection();
        $shipmentIds = array();
        foreach ($shipments as $shipment)
        {
            if ($shipment->getOldEntityId())
            {
                $shipmentIds[$shipment->getOldEntityId()] = $shipment->getId();
            }
        }

        foreach ($importInvoices as $invoice)
        {
            /* @var $invoice Mage_Sales_Model_Order_Invoice */
            $invoice->setCustomerId($order->getCustomerId());

            if ($invoice->getOldShippingAddressId() && isset($addressIds[$invoice->getOldShippingAddressId()]))
            {
                $invoice->setShippingAddressId($addressIds[$invoice->getOldShippingAddressId()]);
            }

            if ($invoice->getOldBillingAddressId() && isset($addressIds[$invoice->getOldBillingAddressId()]))
            {
                $invoice->setBillingAddressId($addressIds[$invoice->getOldBillingAddressId()]);
            }

            
            
            /* @var $invoice Mage_Sales_Model_Order_Invoice */
            foreach ($invoice->getItemsCollection() as $item)
            {
                if ($item->getOldShipmentId() && isset($shipmentIds[$item->getOldShipmentId()]))
                {
                    $item->setShipmentId($shipmentIds[$item->getOldShipmentId()]);
                }

                if ($item->getOldOrderItemId() && isset($orderItemsIds[$item->getOldOrderItemId()]))
                {
                    $item->setOrderItem($orderItemsIds[$item->getOldOrderItemId()]);
                }
            }

            $invoice
                ->setOrderId($order->getId())
                ->save();
        }
    }
}