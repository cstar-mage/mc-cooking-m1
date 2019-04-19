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
class Aitoc_Aitexporter_Model_Import_Type_OrderImport extends Aitoc_Aitexporter_Model_Import_Type_Order
{
    protected $_isReturnNeeded;
    protected $_incrementId;
    protected $_incrementPrefix;

    /**
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $importConfig
     * @param Mage_Sales_Model_Abstract $parentItem
     */
    public function import(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem = null)
    {
        $this->_returnState = Aitoc_Aitexporter_Model_Import_Type_Order::STATE_ADD;
        $order       = Mage::getModel('sales/order');
        /* @var $order Mage_Sales_Model_Order */
        $orderXml = current($orderXml->xpath($itemXpath));
        /* @var $orderXml SimpleXMLElement */

        if (empty($orderXml->fields->increment_id))
        {
            $this->_returnState = Aitoc_Aitexporter_Model_Import_Type_Order::STATE_FAIL;
            return $this->_returnState;
        }

        $behavior = isset($config['behavior']) ? $config['behavior'] : 'append';

        $orderErrors = Mage::getModel('aitexporter/import_error')->getCollection()
            ->loadOrderErrors($orderXml->fields->increment_id);

        // Do not import orders with errors
        if ($orderErrors->getSize() && ($behavior != Aitoc_Aitexporter_Model_Import::BEHAVIOR_REMOVE))
        {
            $this->_returnState = Aitoc_Aitexporter_Model_Import_Type_Order::STATE_FAIL;
            return $this->_returnState;
        }

        $order->loadByIncrementId((string)$orderXml->fields->increment_id);

        $this->_behaveImport($behavior, $order);
        if($this->_isReturnNeeded)
        {
            return $this->_returnState;
        }

        $this->incrementPrefix = false;
        $this->incrementId = false;

        $this->_importOrderFields($orderXml, $order, $config);
        $this->_importGiftMessage($orderXml, $order);

        if (!empty($config['store']) && !$config['try_storeviews'])
        {
            $order->setStoreId($config['store']);
        }

        if(!$order->getRealOrderId())
        {
            $order->setRealOrderId(null);
        }

        $this->_importChildren($orderXml, $itemXpath, $config, $order);

        $this->_addNonExistingRequiredData($order);

        Mage::dispatchEvent('aitexporter_import_order_save_before', array('order' => $order));

        $order->save();
        $this->_importPostProcessBeforeSave($order);
        $this->_workWithIncrement($order, 'order', $this->_incrementPrefix, $this->_incrementId);

        $this->_postProcessGiftMessage($order);

        $this->_importPostProcessAfterSave($order);

        Mage::dispatchEvent('aitexporter_import_order_save_after', array('order' => $order));

        return $this->_returnState;
    }

    protected function _behaveImport($behavior, $order)
    {
        $this->_isReturnNeeded = false;
        switch ($behavior)
        {
            case Aitoc_Aitexporter_Model_Import::BEHAVIOR_REPLACE:
                if ($order->getId())
                {
                    if ($order->getGiftMessageId())
                    {
                        Mage::getModel('giftmessage/message')->load($order->getGiftMessageId())->delete();
                    }

                    Mage::dispatchEvent('aitexporter_import_replace_order', array('order' => $order));

                    $order->delete();
                    $order->reset();//       = Mage::getModel('sales/order');
                    $this->_returnState = Aitoc_Aitexporter_Model_Import_Type_Order::STATE_UPDATE;
                }
                break;

            case Aitoc_Aitexporter_Model_Import::BEHAVIOR_REMOVE:
                if (!$order->getId())
                {
                    $this->_isReturnNeeded = true;
                    $this->_returnState = Aitoc_Aitexporter_Model_Import_Type_Order::STATE_FAIL;
                    return;
                }

                Mage::dispatchEvent('aitexporter_import_replace_order', array('order' => $order));

                $order->delete();

                $this->_isReturnNeeded = true;
                $this->_returnState = Aitoc_Aitexporter_Model_Import_Type_Order::STATE_UPDATE;
                return;
                break;

            case Aitoc_Aitexporter_Model_Import::BEHAVIOR_APPEND:
            default:
                if ($order->getId())
                {
                    $this->_isReturnNeeded = true;
                    $this->_returnState = Aitoc_Aitexporter_Model_Import_Type_Order::STATE_UPDATE;
                    return;
                }
                break;
        }

    }

    protected function _importOrderFields(SimpleXMLElement $orderXml, $order, $config)
    {
        foreach ($orderXml->fields->children() as $field)
        {
            $fieldValue = (string)$field == '' ? null : (string)$field;
            switch ($field->getName())
            {
                case 'entity_id':
                case 'store_id':
                case 'quote_id':
                case 'customer_id':
                case 'gift_message_id':
                    break;

                case 'increment_id':
                    $this->_incrementId = $fieldValue;
                    $order->setData($field->getName(), $fieldValue);
                    break;

                case 'increment_prefix':
                    $this->_incrementPrefix = $fieldValue;
                    break;

                case 'status':
                    $fieldName = $field->getName();
                    if(!Mage::helper('aitexporter')->isPreorderEnabled())
                    {
                        $field = str_replace('preorder', '', $fieldValue);
                    }
                    $order->setData($fieldName, $fieldValue);
                    break;

                case 'store_code':
                    if (empty($config['store']) || $config['try_storeviews'])
                    {
                        $storeCode = $fieldValue;
                        $stores    = Mage::app()->getStores();

                        foreach ($stores as $store)
                        {
                            if ($store->getCode() == $storeCode)
                            {
                                $order->setStoreId($store->getId());
                                break;
                            }
                        }

                        if(!$order->getStoreId() && !empty($config['store']))
                        {
                            $order->setStoreId($config['store']);
                        }
                    }
                    break;
                case 'customer_group_id':
                    if ($config['create_customers'] == 'billing')
                    {
                        $order->setData($field->getName(), $fieldValue);
                    }
                    else
                    {
                        $sCustomerEmail = (string)$orderXml->fields->customer_email;
                        $sStoreId = (string)$orderXml->fields->store_id;
                        $oStoreData = Mage::getModel('core/store')->load($sStoreId);
                        $iWebsiteId = $oStoreData->getWebsiteId();
                        $customer = Mage::getModel('customer/customer')->setWebsiteId($iWebsiteId)->loadByEmail($sCustomerEmail);
                        if ($customer->getId())
                        {
                            $order->setData($field->getName(), $fieldValue);
                        }
                    }       
                    break;
                default:
                    $order->setData($field->getName(), $fieldValue);
                    break;
            }
        }
    }


    /*
     *  This data is required for any importing order to open in admin panel without crash
     * */
    protected function _addNonExistingRequiredData($order)
    {
        /*faking the payment method*/
        if(!$order->getPayment())
        {
            $orderPayment = Mage::getModel('sales/order_payment')
                ->setStoreId(0)
                ->setCustomerPaymentId(0)
                ->setMethod('free');
            $order->setPayment($orderPayment);
        }

        /*faking the dates*/
        if(!$order->getCreatedAt())
        {
            $order->setCreatedAt(date('Y-m-d H:i:s'));
        }
        if(!$order->getUpdatedAt())
        {
            $order->setUpdatedAt(date('Y-m-d H:i:s'));
        }

        /*faking addresses*/
        if(!$order->getBillingAddress())
        {
            $billingAddress = Mage::getModel('sales/order_address')
                ->setStoreId(0)
                ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING);
            $order->setBillingAddress($billingAddress);
        }

        if(!$order->getShippingAddress())
        {
            $shippingAddress = Mage::getModel('sales/order_address')
                ->setStoreId(0)
                ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING);
            $order->setShippingAddress($shippingAddress);
        }
    }


    protected function _importGiftMessage(SimpleXMLElement $orderXml, $order)
    {
        if (isset($orderXml->gift_message))
        {
            // empty gift message from CSV
            if (trim(strip_tags($orderXml->gift_message->asXML())))
            {
                $giftMessage = Mage::getModel('giftmessage/message');
                foreach ($orderXml->gift_message->children() as $messageField)
                {
                    switch ($messageField->getName())
                    {
                        case 'gift_message_id':
                        case 'customer_id':
                            break;

                        default:
                            $giftMessage->setData($messageField->getName(), (string)$messageField);
                            break;
                    }
                }

                $order->setGiftMessage($giftMessage);
            }
        }
    }

    protected function _importPostProcessBeforeSave($order)
    {
        Mage::getSingleton('aitexporter/import_type_order_address')->postProcess($order);
        Mage::getSingleton('aitexporter/import_type_order_item')->postProcess($order);
    }

    protected function _importPostProcessAfterSave($order)
    {
        Mage::getSingleton('aitexporter/import_type_order_statushistory')->postProcess($order);
        Mage::getSingleton('aitexporter/import_type_shipment')->postProcess($order);
        Mage::getSingleton('aitexporter/import_type_invoice')->postProcess($order);
        Mage::getSingleton('aitexporter/import_type_order_payment_transaction')->postProcess($order);
        Mage::getSingleton('aitexporter/import_type_creditmemo')->postProcess($order);
        Mage::getSingleton('aitexporter/import_type_order_item')->postProcessAfterSave($order);
        Mage::getSingleton('aitexporter/import_type_order_address')->postProcessAfterSave($order);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    protected function _postProcessGiftMessage(Mage_Sales_Model_Order $order)
    {
        $giftMessage = $order->getGiftMessage();
        if ($giftMessage)
        {
            $giftMessage->setCustomerId($order->getCustomerId())->save();
            $order->setGiftMessageId($giftMessage->getId())->save();
        }
    }

}