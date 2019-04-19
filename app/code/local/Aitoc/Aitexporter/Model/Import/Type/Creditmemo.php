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
class Aitoc_Aitexporter_Model_Import_Type_Creditmemo extends Aitoc_Aitexporter_Model_Import_Type_Complex implements Aitoc_Aitexporter_Model_Import_Type_Interface
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
        $isValid       = true;
        $creditMemoXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($creditMemoXml->asXML())))
        {
            return $isValid;
        }

        if (empty($creditMemoXml->increment_id))
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setError(Mage::helper('aitexporter')->__('"increment_id" field does not exist in path %s. Credit Memo will not be created', $itemXpath))
                ->save();
            $isValid = false;
        }
        else 
        {
            $incrementId = (string)$creditMemoXml->increment_id;
            $creditMemo  = Mage::getModel('sales/order_creditmemo');
            $ids         = $creditMemo->getCollection()
                ->addAttributeToFilter('increment_id', $incrementId)
                ->getAllIds();

            if (!empty($ids)) 
            {
                reset($ids);
                $creditMemo->load(current($ids));
            }

            if ($creditMemo->getId())
            {
                $order = Mage::getModel('sales/order')->load($creditMemo->getOrderId());

                if ($order->getIncrementId() != $orderIncrementId)
                {
                    Mage::getModel('aitexporter/import_error')
                        ->setOrderIncrementId($orderIncrementId)
                        ->setError(Mage::helper('aitexporter')->__('Credit Memo with increment_id "%s" already exists for order %s. Credit Memo will not be created', $incrementId, $order->getIncrementId()))
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
        $creditMemo = Mage::getModel('sales/order_creditmemo');
        /* @var $creditMemo Mage_Sales_Model_Order_Creditmemo */
        $creditMemoXml = current($orderXml->xpath($itemXpath));
        /* @var $creditMemoXml SimpleXMLElement */

        // empty items from CSV 
        if (!trim(strip_tags($creditMemoXml->asXML())))
        {
            return false;
        }

        $incrementId = (string)$creditMemoXml->increment_id;
        if ($incrementId) 
        {
            $creditMemoCheck = Mage::getModel('sales/order_creditmemo');
            $ids             = $creditMemo->getCollection()
                ->addAttributeToFilter('increment_id', $incrementId)
                ->getAllIds();

            if (!empty($ids)) 
            {
                reset($ids);
                $creditMemoCheck->load(current($ids));
            }
            
            // Credit Memo with increment Id exists for another order
            if ($creditMemoCheck->getId())
            {
                //$incrementId = null;
                return Aitoc_Aitexporter_Model_Import_Type_Order::STATE_FAIL;
            }
        }

        $incrementPrefix = false;
        $incrementId = false;
        foreach ($creditMemoXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'entity_id':
                case 'order_id':
                //case 'increment_id':
                    break;
                
                case 'increment_id':
                    $incrementId = (string)$field;
                    $creditMemo->setData($field->getName(), (string)$field);
                    break;
                    
                case 'increment_prefix':
                    $incrementPrefix = (string)$field;
                    break;

                case 'shipping_address_id':
                case 'billing_address_id':
                case 'invoice_id':
                case 'transaction_id':
                    $creditMemo->setData('old_'.$field->getName(), (string)$field);
                    break;

                case 'store_id':
                    $creditMemo->setStoreId($parentItem->getStoreId());
                    break;

                default:
                    $creditMemo->setData($field->getName(), (string)$field);
                    break;
            }
        }

        $importCreditMemos = $parentItem->getImportCreditMemos();
        if (empty($importCreditMemos))
        {
            $importCreditMemos = array();
        }
        $importCreditMemos[] = $creditMemo;
        $parentItem->setImportCreditMemos($importCreditMemos);

        $this->_importChildren($orderXml, $itemXpath, $config, $creditMemo);
        
        $this->_workWithIncrement($parentItem, 'creditmemo', $incrementPrefix, $incrementId);
    }

    public function getConfigPath()
    {
        return 'order/creditmemo';
    }

    public function getXpath()
    {
        return '/creditmemos/creditmemo';
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
        $importCreditMemos = $order->getImportCreditMemos();
        if (empty($importCreditMemos))
        {
            $importCreditMemos = array();
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

        $invoices   = $order->getInvoiceCollection();
        $invoiceIds = array();
        foreach ($invoices as $invoice)
        {
            if ($invoice->getOldEntityId())
            {
                $invoiceIds[$invoice->getOldEntityId()] = $invoice->getId();
            }
        }

        foreach ($importCreditMemos as $creditMemo)
        {
            /* @var $creditMemo Mage_Sales_Model_Order_Creditmemo */

            if ($creditMemo->getOldShippingAddressId() && isset($addressIds[$creditMemo->getOldShippingAddressId()]))
            {
                $creditMemo->setShippingAddressId($addressIds[$creditMemo->getOldShippingAddressId()]);
            }

            if ($creditMemo->getOldBillingAddressId() && isset($addressIds[$creditMemo->getOldBillingAddressId()]))
            {
                $creditMemo->setBillingAddressId($addressIds[$creditMemo->getOldBillingAddressId()]);
            }

            /* @var $invoice Mage_Sales_Model_Order_Invoice */
            foreach ($creditMemo->getItemsCollection() as $item)
            {
                if ($item->getOldOrderItemId() && isset($orderItemsIds[$item->getOldOrderItemId()]))
                {
                    $item->setOrderItem($orderItemsIds[$item->getOldOrderItemId()]);
                }
            }

            if ($creditMemo->getOldInvoiceId() && isset($invoiceIds[$creditMemo->getOldInvoiceId()]))
            {
                $creditMemo->setInvoiceId($invoiceIds[$creditMemo->getOldInvoiceId()]);
            }

            $creditMemo
                ->setOrderId($order->getId())
                ->save();
        }
    }
}