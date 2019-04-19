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
class Aitoc_Aitexporter_Model_Import_Error extends Mage_Catalog_Model_Abstract
{
    const TYPE_ERROR   = 'error';
    const TYPE_WARNING = 'warning';

    public function _construct()
    {
        parent::_construct();

        $this->_init('aitexporter/import_error');
    }

    public function getTypes()
    {
        return array(
            self::TYPE_ERROR   => Mage::helper('aitexporter')->__('Error'), 
            self::TYPE_WARNING => Mage::helper('aitexporter')->__('Minor Error'), 
            );
    }

    protected function _beforeSave()
    {
        if (!$this->getId())
        {
            $currentImport = Mage::registry('current_import');
            if ($currentImport)
            {
                $this->setImportId($currentImport->getId());
            }

            if (!$this->getType())
            {
                $this->setType(self::TYPE_WARNING);
            }
        }

        parent::_beforeSave();
    }
}