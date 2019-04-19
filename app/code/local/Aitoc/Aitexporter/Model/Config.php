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
class Aitoc_Aitexporter_Model_Config
{
    const EXPORT_PATH     = 'sales/aitexporter/export'; //deprecated
    const EXPORT_XSL_PATH = 'sales/aitexporter/export_xsl'; //deprecated
    const IMPORT_PATH     = 'sales/aitexporter/import';

    private $_exportConfigProfiles = array();
    private $_xsl = null;

    public function getExportProfile($profileId = 0)
    {
        $profile = Mage::getModel('aitexporter/profile');
        
        if ($profileId == 0)
        {
            if(!isset($this->_exportConfigProfiles[0])) {
                $collection = $profile->getResourceCollection()
                    ->loadLast();
                    
                if($collection->getSize() != 0)
                {
                    $profile = $collection->getFirstItem();
                } else {
                    $profile->setConfig( array() );
                }
                $this->_exportConfigProfiles[0] = $profile;
            }
            $profile = $this->_exportConfigProfiles[0];
        } else {
            if(!isset($this->_exportConfigProfiles[$profileId])) {
                $profile->load($profileId);
                $this->_exportConfigProfiles[$profileId] = $profile;
            }
            $profile = $this->_exportConfigProfiles[$profileId];
        }
        return $profile;
    }
    
    public function getExportConfig($profileId = 0)
    {
        $profile = $this->getExportProfile($profileId);
        return $profile->getConfig();
    }

    public function getImportConfig()
    {
        return unserialize(Mage::getStoreConfig(self::IMPORT_PATH));
    }

    /**
     * 
     * @param array $export
     */
    public function saveExportConfig($export, $storeId = 0, $profile_id = 0)
    {
        $profile = Mage::getModel('aitexporter/profile');
        /* @var $profile Aitoc_Aitexporter_Model_Profile */
        if($profile_id > 0) {
            $profile->load($profile_id);
        }

        $profile->setStoreId($storeId)
            ->setName($export['profile']['name']);
        
        unset($export['profile']);
        
        if(!is_null($this->_xsl)) {
            $profile->setXsl( $this->_xsl );
        }
        $profile->setConfig( $export )
            ->updateDate()
            ->save();
        
        $this->_exportConfigProfiles[$profile->getId()] = $this->_exportConfigProfiles[0] = $profile;

        return $this;
    }

    public function loadProfileId( $profile_id = 0 )
    {
        return $this->getExportProfile($profile_id)->getId();
    }
    
    /**
     * 
     * @param array $export
     */
    public function saveImportConfig($export)
    {
        $config = Mage::getConfig();
        /* @var $config Mage_Core_Model_Config */

        $config->saveConfig(self::IMPORT_PATH, serialize($export))->reinit();

        return $this;
    }

    /**
     * 
     * @param string $xslContent
     */
    public function saveXsl($xslContent)
    {
        $this->_xsl = $xslContent;
        return $this;
    }
    
}