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
class Aitoc_Aitexporter_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_isPreorderEnabled = null;

    /**
     * @param string $entityType
     * @return Aitoc_Aitexporter_Model_Export_Type_Iterface
     */
    public function getExportModelByEntityType($entityType)
    {
        return Mage::getSingleton('aitexporter/export_type_'.$entityType);
    }

    /**
     * @param string $entityType
     * @return Aitoc_Aitexporter_Model_Export_Type_Iterface
     */
    public function getImportModelByEntityType($entityType)
    {
        return Mage::getSingleton('aitexporter/import_type_'.$entityType);
    }

    public function getTmpPath()
    {
        $path = Mage::getBaseDir('var').DS.'aitexporter'.DS;

        if (!is_dir($path))
        {
            $file = new Varien_Io_File();
            $file->mkdir($path);
        }

        return $path;
    }

    public function isPreorderEnabled()
    {
        if($this->_isPreorderEnabled === null)
        {
            $this->_isPreorderEnabled = false;

            if (Mage::getConfig()->getNode('modules/Aitoc_Aitpreorder'))
            {
                $isActive = Mage::getConfig()->getNode('modules/Aitoc_Aitpreorder/active');
                if ($isActive && in_array((string)$isActive, array('true', '1')))
                {
                    $this->_isPreorderEnabled = true;
                }
            }
        }

        return $this->_isPreorderEnabled;
    }


    public function getEntityId($entityType)
    {
        $entityType = Mage::getModel('eav/config')->getEntityType($entityType);
        return $entityType->getEntityTypeId();
    }

    public function getEntityFields($entityType)
    {
        $attributeCodes = Mage::getSingleton('eav/config')
            ->getEntityAttributeCodes($entityType, null);
        return $attributeCodes;
    }
}