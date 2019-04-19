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
class Aitoc_Aitexporter_Model_Import_Type_OrderValidate extends Aitoc_Aitexporter_Model_Import_Type_Order
{
    protected $_isValid;
    protected $_isReturnNeeded;

    protected function _validateAddresses(SimpleXMLElement $orderXml, $orderIncrementId)
    {
        if (isset($orderXml->addresses))
        {
            $shippingAddresses = 0;
            $billingAddresses  = 0;

            foreach ($orderXml->addresses->children() as $address)
            {
                if (!empty($address->address_type))
                {
                    switch ((string)$address->address_type)
                    {
                        case 'billing':
                            $billingAddresses++;
                            break;

                        case 'shipping':
                            $shippingAddresses++;
                            break;
                    }
                }
            }

            if (!$shippingAddresses && empty($orderXml->fields->is_virtual))
            {
                Mage::getModel('aitexporter/import_error')
                    ->setOrderIncrementId($orderIncrementId)
                    ->setError(Mage::helper('aitexporter')->__('Order does not contain a shipping address'))
                    ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
                    ->save();
                $this->_isValid  = false;
            }

            if (!$billingAddresses)
            {
                Mage::getModel('aitexporter/import_error')
                    ->setOrderIncrementId($orderIncrementId)
                    ->setError(Mage::helper('aitexporter')->__('Order does not contain a billing address'))
                    ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
                    ->save();
                $this->_isValid  = false;
            }
        }
    }

    protected function _validateShipping(SimpleXMLElement $orderXml, $orderIncrementId)
    {
        $shippingMethod = isset($orderXml->fields->shipping_method) ? (string)$orderXml->fields->shipping_method : '';

        if ('' == $shippingMethod)
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setError(Mage::helper('aitexporter')->__('Order contains no shipping_method field. All order shipments will not be imported.'))
                ->save();
            $this->_isValid  = false;
        }
        else
        {
            $order          = Mage::getModel('sales/order');
            /* @var $order Mage_Sales_Model_Order */
            $carrier        = $order->setShippingMethod($shippingMethod)
                ->setshippingmethod($shippingMethod) //magento will use parent::getShippingMethod() which will be interpretated as 'getshippingmethod', which will give error that shipping is not set
                ->getShippingCarrier();

            if (false == ($carrier instanceof Mage_Shipping_Model_Carrier_Interface))
            {
                Mage::getModel('aitexporter/import_error')
                    ->setOrderIncrementId($orderIncrementId)
                    ->setError(Mage::helper('aitexporter')->__('Order contains wrong shipping_method field. Such shipping method does not exist in system. All order shipments will not be imported.'))
                    ->save();
                $this->_isValid  = false;
            }
        }
    }

    protected function _validateStores(SimpleXMLElement $orderXml, $orderIncrementId)
    {
        $stores = Mage::app()->getStores();

        if (empty($orderXml->fields->store_code) && count($stores) > 1)
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setError(Mage::helper('aitexporter')->__('Field "store_code" does not exist in order'))
                ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
                ->save();
            $this->_isValid  = false;
        }
        elseif (count($stores) > 1)
        {
            $isStoreExists = false;
            $storeCode     = (string)$orderXml->fields->store_code;
            foreach ($stores as $store)
            {
                if ($storeCode == $store->getCode())
                {
                    $isStoreExists = true;
                }
            }

            if (!$isStoreExists)
            {
                Mage::getModel('aitexporter/import_error')
                    ->setOrderIncrementId($orderIncrementId)
                    ->setError(Mage::helper('aitexporter')->__('Store view with code "%s" does not exist', $storeCode))
                    ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
                    ->save();
                $this->_isValid  = false;
            }
        }
    }


    protected function _behaveValidation($behavior,$order,$orderIncrementId)
    {
        $this->_isReturnNeeded = false;
        switch ($behavior)
        {
            case Aitoc_Aitexporter_Model_Import::BEHAVIOR_REMOVE:
                $message = Mage::helper('aitexporter')->__('Order will be removed');
                if (!$order->getId())
                {
                    $message = Mage::helper('aitexporter')->__('Order does not exist');
                    $this->_isValid  = false;
                }

                Mage::getModel('aitexporter/import_error')
                    ->setOrderIncrementId($orderIncrementId)
                    ->setError($message)
                    ->save();
                break;

            case Aitoc_Aitexporter_Model_Import::BEHAVIOR_REPLACE:
            case Aitoc_Aitexporter_Model_Import::BEHAVIOR_APPEND:
            default:
                if ($order->getId())
                {
                    Mage::getModel('aitexporter/import_error')
                        ->setOrderIncrementId($orderIncrementId)
                        ->setError(Mage::helper('aitexporter')->__('Order already exists'))
                        ->save();
                    $this->_isValid  = false;

                    if (Aitoc_Aitexporter_Model_Import::BEHAVIOR_APPEND == $behavior)
                    {
                        $this->_isReturnNeeded = true;
                        return;
                    }
                }
                break;
        }
    }

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
        $this->_isValid  = true;
        $orderXml = current($orderXml->xpath($itemXpath));

        if (!isset($orderXml->fields))
        {
            Mage::getModel('aitexporter/import_error')
                ->setError(Mage::helper('aitexporter')->__('Order %s Does not have fields element', $itemXpath))
                ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
                ->save();
            $this->_isValid  = false;
        }
        else
        {
            $orderIncrementId = isset($orderXml->fields->increment_id) ? (string)$orderXml->fields->increment_id : false;

            if (empty($orderXml->fields->status))
            {
                Mage::getModel('aitexporter/import_error')
                    ->setError(Mage::helper('aitexporter')->__('Order "status" field in %s is empty', $itemXpath))
                    ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_WARNING)
                    ->setOrderIncrementId($orderIncrementId)
                    ->save();
                $this->_isValid  = false;
            }

            if (!$orderIncrementId)
            {
                Mage::getModel('aitexporter/import_error')
                    ->setError(Mage::helper('aitexporter')->__('Order Increment field in %s is empty', $itemXpath))
                    ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
                    ->save();
                $this->_isValid  = false;
            }
            else
            {
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderIncrementId);

                $behavior = isset($config['behavior']) ? $config['behavior'] : 'append';

                $this->_behaveValidation($behavior,$order,$orderIncrementId);
                if($this->_isReturnNeeded)
                {
                    return $this->_isValid;
                }

                if (empty($config['store']) || $config['try_storeviews'])
                {
                    $this->_validateStores($orderXml, $orderIncrementId);
                }
            }
        }
        $this->_validateAddresses($orderXml, $orderIncrementId);
        $this->_validateShipping($orderXml, $orderIncrementId);
        $this->_validateChildren($orderXml, $itemXpath, $config, $orderIncrementId);

        return $this->_isValid;
    }
}