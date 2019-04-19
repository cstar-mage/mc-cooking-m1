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
class Aitoc_Aitexporter_Model_Import_Type_Shipment extends Aitoc_Aitexporter_Model_Import_Type_Complex implements Aitoc_Aitexporter_Model_Import_Type_Interface
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
        $isValid     = true;
        $shipmentXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($shipmentXml->asXML())))
        {
            return $isValid;
        }

        if (empty($shipmentXml->increment_id))
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setError(Mage::helper('aitexporter')->__('"increment_id" field does not exist in path %s. Shipment will not be created', $itemXpath))
                ->save();
            $isValid = false;
        }
        else 
        {
            $incrementId = (string)$shipmentXml->increment_id;
            $shipment    = Mage::getModel('sales/order_shipment')->loadByIncrementId($incrementId);

            if ($shipment->getId())
            {
                $order = Mage::getModel('sales/order')->load($shipment->getOrderId());

                if ($order->getIncrementId() != $orderIncrementId)
                {
                    Mage::getModel('aitexporter/import_error')
                        ->setOrderIncrementId($orderIncrementId)
                        ->setError(Mage::helper('aitexporter')->__('Shipment with increment_id "%s" already exists for order %s. Shipment will not be created', $incrementId, $order->getIncrementId()))
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
     * @param Mage_Sales_Model_Abstract $parentItem
     */
    public function import(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem = null)
    {
        /* @var $parentItem Mage_Sales_Model_Order */
        $shipment = Mage::getModel('sales/order_shipment');
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipmentXml = current($orderXml->xpath($itemXpath));
        /* @var $shipmentXml SimpleXMLElement */

        // empty items from CSV 
        if (!trim(strip_tags($shipmentXml->asXML())))
        {
            return false;
        }

        $incrementId = (string)$shipmentXml->increment_id;
        if ($incrementId) 
        {
            $shipmentCheck = Mage::getModel('sales/order_shipment')->loadByIncrementId($incrementId);

            // Shipment with increment Id exists for another order
            if ($shipmentCheck->getId())
            {
                //$incrementId = null;
                return Aitoc_Aitexporter_Model_Import_Type_Order::STATE_FAIL;
            }
        }

        $incrementPrefix = false;
        $incrementId = false;
        foreach ($shipmentXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'entity_id':
                case 'order_id':
                //case 'increment_id':
                    break;
                
                case 'increment_id':
                    $incrementId = (string)$field;
                    $shipment->setData($field->getName(), (string)$field);
                    break;
                    
                case 'increment_prefix':
                    $incrementPrefix = (string)$field;
                    break;

                case 'shipping_address_id':
                case 'billing_address_id':
                    $shipment->setData('old_'.$field->getName(), (string)$field);
                    break;

                case 'store_id':
                    $shipment->setStoreId($parentItem->getStoreId());
                    break;

                case 'customer_id':
                    $shipment->setCustomerId($parentItem->getCustomerId());
                    break;

                default:
                    $shipment->setData($field->getName(), (string)$field);
                    break;
            }
        }

        $importShipments = $parentItem->getImportShipments();
        if (empty($importShipments))
        {
            $importShipments = array();
        }
        $importShipments[] = $shipment;
        $parentItem->setImportShipments($importShipments);

        $this->_importChildren($orderXml, $itemXpath, $config, $shipment);
        
        $this->_workWithIncrement($parentItem, 'shipment', $incrementPrefix, $incrementId);
    }

    public function getConfigPath()
    {
        return 'order/shipment';
    }

    public function getXpath()
    {
        return '/shipments/shipment';
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

        try
        {
            $carrier = $order->getShippingCarrier();
        }
        catch(Exception $e)
        {
            return false;
        }

        if (false == ($carrier instanceof Mage_Shipping_Model_Carrier_Interface))
        {
            return false;
        }

        $importShipments = $order->getImportShipments();
        if (empty($importShipments))
        {
            $importShipments = array();
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

        foreach ($importShipments as $shipment)
        {
            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            $shipment->setCustomerId($order->getCustomerId());

            if ($shipment->getOldShippingAddressId() && isset($addressIds[$shipment->getOldShippingAddressId()]))
            {
                $shipment->setShippingAddressId($addressIds[$shipment->getOldShippingAddressId()]);
            }

            if ($shipment->getOldBillingAddressId() && isset($addressIds[$shipment->getOldBillingAddressId()]))
            {
                $shipment->setBillingAddressId($addressIds[$shipment->getOldBillingAddressId()]);
            }

            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            foreach ($shipment->getItemsCollection() as $item)
            {
                if ($item->getOldOrderItemId() && isset($orderItemsIds[$item->getOldOrderItemId()]))
                {
                    $item->setOrderItem($orderItemsIds[$item->getOldOrderItemId()]);
                }
            
                //start Aitoc_Preorder compatibility fix
                if(Mage::helper('aitexporter')->isPreorderEnabled())
                {
                    if(!$item->getId()) 
                    {
                        $orderItem=$item->getOrderItem();
                        if($orderItem->getData('product_type')=='bundle')
                        {
                            $isRegular=0;
                            $isRegular=Mage::helper('aitpreorder')->bundleHaveReg($orderItem);
                            if($isRegular==0)
                            {
                                if (version_compare(Mage::getVersion(),'1.4.1.0','>=')) 
                                {
                                    $tmp_total_qty=$shipment->getTotalQty();
                                    $shipment->setTotalQty($tmp_total_qty-$item->getQty());
                                }
                                $item->setQty(0);
                            }
                        }
                    }
                }
                //end Aitoc Preorder compatibility fix
            }

            foreach ($shipment->getTracksCollection() as $track)
            {
                $track->setOrderId($order->getId());
            }

            if (count($shipment->getAllItems()))
            {
                $shipment
                    ->setOrderId($order->getId())
                    ->save();
            }
        }
    }
}