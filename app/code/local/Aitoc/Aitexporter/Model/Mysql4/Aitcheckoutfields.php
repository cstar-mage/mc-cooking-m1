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
class Aitoc_Aitexporter_Model_Mysql4_Aitcheckoutfields extends Mage_Core_Model_Mysql4_Abstract
{
    const ORDER_VALUES_TABLE = 'aitoc_order_entity_custom';

    public function _construct()
    {
        
    }

    public function deleteAitcheckoutfields(Mage_Sales_Model_Order $order)
    {
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');
        $db->delete(
            self::ORDER_VALUES_TABLE, 
            ' entity_id = '.$db->quote($order->getId()).' '
            );
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Eav_Model_Entity_Attribute $field
     * @param array $values
     */
    public function addOrderCheckoutFieldValues(Mage_Sales_Model_Order $order, Mage_Eav_Model_Entity_Attribute $field, array $values)
    {
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');
        $helper = Mage::helper('aitexporter/aitcheckoutfields');
        /* @var $helper Aitoc_Aitexporter_Helper_Aitcheckoutfields */
        $options = $helper->getFieldOptions($field);

        $stringValue = join(',', $values);
        if (!empty($options))
        {
            $valuesIds = array();
            foreach ($values as $value)
            {
                if (isset($options[$value]))
                {
                    $valuesIds[] = $options[$value];
                }
            }

            $stringValue = join(',', $valuesIds);
        }

        if ($stringValue !== 0)
        {
            $db->insert(self::ORDER_VALUES_TABLE, array(
                'attribute_id' => $field->getId(), 
            	'entity_id'    => $order->getId(), 
                'value'        => $stringValue, 
                ));
        }
    }
}