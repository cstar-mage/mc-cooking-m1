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
class Aitoc_Aitexporter_Model_Processor_Direct extends Aitoc_Aitexporter_Model_Processor
{
    /**
    * This class is small update for our process system to divide checkout/invoice exports from cron, because cron may block exewcuting profiles on this events. 
    * Most probably this will require refactoring whole process system.
    */
    protected $_configModel = 'aitexporter/processor_direct_config';    
    
    /**
    * Direct process can't be blocked by configuration
    * 
    */
    public function isBusy()
    {
        return false;
    }
    
    
}