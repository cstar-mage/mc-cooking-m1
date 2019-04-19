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
class Aitoc_Aitexporter_Model_Export_Type_Order extends Aitoc_Aitexporter_Model_Export_Type_Complex implements Aitoc_Aitexporter_Model_Export_Type_Interface
{
    private $__specialAttributeFields = array('payment_authorization_expiration' => 'payment_auth_expiration');

    /**
     *
     * @param SimpleXMLElement $orderXml
     * @param Mage_Sales_Model_Order $order
     * @param Varien_Object $exportConfig
     */
    public function prepareXml(SimpleXMLElement $orderXml, Mage_Core_Model_Abstract $order, Varien_Object $exportConfig)
    {
        // Add order values using field filters
        $filteredOrderFieldNames = $exportConfig->getOrderField() ? array_keys($exportConfig->getOrderField()) : array();
        $allOrderFields          = $this->getOrderFields();
        $orderFieldsXml          = $orderXml->addChild('fields');

        $orderFieldsXml->addChild('increment_id', $order->getData('increment_id'));
        $orderFieldsXml->addChild('increment_prefix', $this->_getIncrementPrefix($order, 'order'));

        foreach($allOrderFields as $orderField)
        {
            if(in_array($this->getAttributeCodeEavFlat($orderField), $filteredOrderFieldNames))
            {
                if((version_compare(Mage::getVersion(),'1.4.1.0','<')) && in_array($this->getAttributeCodeEavFlat($orderField), array_keys($this->__specialAttributeFields)))
                {
                    $childValue = $order->getData($this->__specialAttributeFields[$this->getAttributeCodeEavFlat($orderField)]);
                    $orderFieldsXml->addChild($this->__specialAttributeFields[$this->getAttributeCodeEavFlat($orderField)], $childValue);
                }
                else
                {
                    $childValue = $order->getData($this->getAttributeCodeEavFlat($orderField));
                    $orderFieldsXml->addChild($this->getAttributeCodeEavFlat($orderField), $childValue);
                }
            }
        }
        $this->_addPreorder($orderFieldsXml,$order);
        $this->_addGiftMessage($orderXml, $order);

        $orderFieldsXml->addChild('store_code', $this->_getStoreCodeByStoreId($order->getStoreId()));

        $this->_exportChildData($orderXml, $order, $exportConfig);
    }

    protected function _addPreorder(SimpleXMLElement $orderFieldsXml, Mage_Core_Model_Abstract $order)
    {
        if(Mage::helper('aitexporter')->isPreorderEnabled())
        {
            $childValue = $order->getData('status_preorder');
            $orderFieldsXml->addChild('status_preorder', $childValue);
        }
    }

    protected function _addGiftMessage(SimpleXMLElement $orderXml, Mage_Core_Model_Abstract $order)
    {
        if ($order->getGiftMessageId())
        {
            $giftMessageXml   = $orderXml->addChild('gift_message');
            $giftMessageModel = Mage::getModel('giftmessage/message')->load($order->getGiftMessageId());
            $giftMessageData  = $giftMessageModel->getData();

            foreach($giftMessageData as $key => $value)
            {
                $giftMessageXml->addChild($key, $value);
            }
        }
    }

    protected function _getStoreCodeByStoreId($storeId = 0)
    {
        if (!$storeId)
        {
            return false;
        }

        return Mage::app()->getStore($storeId)->getCode();
    }

    /**
     *
     * @see Aitoc_Aitexporter_Model_Export_Type_Complex::getChildTypes()
     * @param Varien_Object $exportConfig
     * @return array
     */
    public function getChildTypes(Varien_Object $exportConfig)
    {
        return $exportConfig->getEntityType() ? $exportConfig->getEntityType() : array();
    }

    public function getOrderFields()
     {
         if(version_compare(Mage::getVersion(), '1.4.1.0', '<')) {
             $collection = Mage::getResourceModel('sales/order_collection')
                 ->addAttributeToSelect('*')
             ;
             $collection->getSelect()
                 ->limit(1,0);

             $fields = $collection->getData();
             $fields = array_keys($fields[0]);
             $fields = array_unique(array_merge($fields, Mage::helper('aitexporter')->getEntityFields('order')));
         } else {
             Zend_Db_Table::setDefaultAdapter(Mage::getSingleton('core/resource')->getConnection('core_read'));
             $orderTable = new Zend_Db_Table(Mage::getResourceModel('sales/order')->getMainTable());
             $fields = $orderTable->info(Zend_Db_Table_Abstract::COLS);
         }

         foreach($fields as $key => $value) {
             if(in_array($value, array('status_preorder', 'increment_id'))) {
                 unset($fields[$key]);
             }
         }

         sort($fields);

         return $fields;
     }

    public function getAttributeCodeEavFlat($field)
    {
            return $field;
    }
}