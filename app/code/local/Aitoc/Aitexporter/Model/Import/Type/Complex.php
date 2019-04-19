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
abstract class Aitoc_Aitexporter_Model_Import_Type_Complex
{
    const CONFIG_XPATH_PREFIX = 'aitexporter/import/';

    private $_childModels;

    /**
     * @return array of Aitoc_Aitexporter_Model_Import_Type_Interface objects
     */
    public function getChildren()
    {
        if (!isset($this->_childModels))
        {
            $childNodes = Mage::getConfig()->getNode(self::CONFIG_XPATH_PREFIX.$this->getConfigPath());

            if ($childNodes && $childNodes->hasChildren())
            {
                foreach ($childNodes->children() as $child)
                {
                    $this->_childModels[] = Mage::helper('aitexporter')->getImportModelByEntityType($child->getName());
                }
            }
        }

        return $this->_childModels;
    }

    /**
     *
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $config
     * @return boolean
     */
    protected function _validateChildren(SimpleXMLElement $orderXml, $itemXpath, array $config, $orderIncrementId)
    {
        $isValid = true;
        foreach ($this->getChildren() as $childModel)
        {
            /* @var $childModel Aitoc_Aitexporter_Model_Import_Type_Interface */

            $childXpath = $itemXpath.$childModel->getXpath();
            $children   = $orderXml->xpath($childXpath);
            $k          = 0;

            if ( $children == '' || 0 == count($children))
            {
                if($childModel->getErrorType()) {
                    Mage::getModel('aitexporter/import_error')
                        ->setOrderIncrementId($orderIncrementId)
                        ->setType($childModel->getErrorType())
                        ->setError(Mage::helper('aitexporter')->__('Order does not have any entities with path %s', $childXpath))
                        ->save();
                    $isValid = false;
                }
                $children = array();
            }

            foreach ($children as $child)
            {
                $isChildValid = $childModel->validate($orderXml, $childXpath.'['.++$k.']', $config, $orderIncrementId);

                if (!$isChildValid)
                {
                    $isValid = false;
                    //return $isValid = false;
                }
            }
        }

        return $isValid;
    }

    /**
     *
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $config
     * @param Mage_Sales_Model_Abstract $parentItem
     */
    protected function _importChildren(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem)
    {
        foreach ($this->getChildren() as $childModel)
        {
            /* @var $childModel Aitoc_Aitexporter_Model_Import_Type_Interface */

            $childXpath = $itemXpath.$childModel->getXpath();
            $children = $orderXml->xpath($childXpath);
            $k = 0;
            if(!$children) {
                continue;
            }
            foreach ($orderXml->xpath($childXpath) as $child)
            {
                $childModel->import($orderXml, $childXpath.'['.++$k.']', $config, $parentItem);
            }
        }
    }

    protected function _workWithIncrement(Mage_Sales_Model_Order $order, $entityCode = '', $incrementPrefix = false, $incrementId = false)
    {
        if($incrementPrefix && $incrementId)
        {
            $entityType = Mage::getModel('eav/entity_type')->loadByCode($entityCode);

            $incrementId = preg_replace('/(' . $incrementPrefix . ')/', '', $incrementId, 1, $count);
            if($count > 0)
            {
                $integerIncrementId = (integer) $incrementId;
            }
            else
            {
                $incrementIdArray = explode('0', $incrementId);
                $incrementPrefix = $incrementIdArray[0];
                $integerIncrementId = (integer) preg_replace('/(' . $incrementPrefix . ')/', '', $incrementId, 1);
            }

            $entityStoreConfig = Mage::getModel('eav/entity_store')
                    ->loadByEntityStore($entityType->getId(), $order->getStoreId());

            if(!$entityStoreConfig->getId())
            {
                Mage::getSingleton('eav/config')
                    ->getEntityType('order')
                    ->fetchNewIncrementId($order->getStoreId());

            }

            $defaultIncPrefix = Mage::getModel('eav/entity_store')
                ->loadByEntityStore($entityType->getId(), $order->getStoreId())
                ->getIncrementPrefix();
            $defaultIncId = Mage::getModel('eav/entity_store')
                ->loadByEntityStore($entityType->getId(), $order->getStoreId())
                ->getIncrementLastId();

            $defaultIncId = preg_replace('/(' . $defaultIncPrefix . ')/', '', $defaultIncId, 1);
            $defaultIncId = (integer) $defaultIncId;

            if($defaultIncId < $integerIncrementId)
            {
            	$insertIncrementPrefix = $defaultIncPrefix ? (string) $defaultIncPrefix : (string) $incrementPrefix;
                $stringIncrementId = $insertIncrementPrefix . Mage::getModel('eav/entity_increment_alphanum')->format($integerIncrementId);

				$entity_store = Mage::getModel('eav/entity_store')
                    ->loadByEntityStore($entityType->getId(), $order->getStoreId());

                if ($entity_store->getData() != array())
                {
                	$entity_store
	                	->setIncrementPrefix($insertIncrementPrefix)
	                	->setIncrementLastId($stringIncrementId)
	                	->save();
                }
                else
                {
					Mage::getModel('eav/entity_store')
	                    ->setEntityTypeId($entityType->getId())
	                    ->setStoreId($order->getStoreId())
	                    ->setIncrementPrefix($insertIncrementPrefix)
	                    ->setIncrementLastId($stringIncrementId)
	                    ->save();
                }

            }
        }
    }

    abstract public function getConfigPath();
}