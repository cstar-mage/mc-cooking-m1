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
class Aitoc_Aitexporter_Helper_Processor extends Mage_Core_Helper_Abstract
{
    protected $_types = array(
        'export' => 'Export',
        'import' => 'Import'
    );
    
    protected $_methods = array(
        'export' => array(
            'initExport'        => 'Initialization',
            'makeExport'        => 'Processing Data',
            'finalizeXmlExport' => 'Processing Data',
            'initConvert'       => 'CSV Conversion Initialization',
            'makeConvert'       => 'Data Conversion',
            'finishExport'      => 'Finalizing',   
        ),
        'import' => array(
            'initImport'         => 'Initialization',
            'initConvert'        => 'CSV Conversion Initialization',
            'makeConvert'        => 'Data Conversion',
            'makeImport'         => 'Processing Data',
            'initValidation'     => 'Data Validation',
            'reinitValidation'   => 'Data Validation',
            'makeValidation'     => 'Data Validation',
            'finalizeConvert'    => 'Data Conversion',
        )
    );
    
    /**
     * Get friendly process name
     * 
     * @param string $process
     * @return string
     */
    public function getProcessName($process)
    {
        list($processType, $processMethod) = explode('::', $process);
        
        return $this->__($this->_types[$processType]).' - '.$this->__($this->_methods[$processType][$processMethod]);
    }
    
    /**
     * Get process completeness percent
     *
     * @param array $options
     * @return integer
     */
    public function calculatePercent($options)
    {
        if(isset($options['limit']))
        {
            if($options['from'] > 0)
            {
                return round($options['from'] / $options['entities'] * 100);
            }
            else
            {
                return 0;
            }
        }
        else
        {
            return 100;
        }
    }
}