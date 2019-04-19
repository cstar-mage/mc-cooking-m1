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
class Aitoc_Aitexporter_Model_Processor
{
    const ITERATOR_AJAX = 'ajax';
    const ITERATOR_CRON = 'cron';
    
    const ITERATOR_STATUS_FREE = 'free';
    const ITERATOR_STATUS_BUSY = 'busy';

    const RESULT_NOTICE   = 'notice';
    const RESULT_ERROR    = 'error';
    const RESULT_SWITCH   = 'switch';
    const RESULT_SUCCESS  = 'success';
    
    const ITERATOR_CONFIG_PATH = 'sales/aitexporter/iterator';
    
    protected $_timeout = 120; // default busy status timeout - 10 min / 600 sec
    
    protected $_iterator = self::ITERATOR_CRON;
    
    /**
     * @var Aitoc_Aitexporter_Model_Processor_Config
     */
    protected $_config;
    protected $_configModel = 'aitexporter/processor_config';
    
    public function __construct($configModel = null)
    {
        if($configModel) {
            $this->_configModel = $configModel;
        }
        $this->_iterator = Mage::app()->getRequest()->getParam('isAjax') ? self::ITERATOR_AJAX : self::ITERATOR_CRON;

        $this->_checkTimeout()
             ->_switchIterator();
    }
    
    /**
     * @return Aitoc_Aitexporter_Model_Processor_Config
     */
    protected function _config()
    {
        if(is_null($this->_config))
        {
            $this->_config = Mage::getSingleton($this->_configModel);
        }
        return $this->_config;
    }
    
    /**
     * Refresh the time mark of the config and save it
     * 
     * @return Aitoc_Aitexporter_Model_Processor
     */
    protected function _saveConfig()
    {
        $this->_config()->set('time', time())->save();
        return $this;
    }
    
    /**
     * Check if timeout reached and then free the iterator and swich it to cron
     * 
     * @return Aitoc_Aitexporter_Model_Processor
     */
    protected function _checkTimeout()
    {
        if(($this->_config()->get('time') + $this->_timeout) < time())
        {
            $this->_config()->set('iterator', self::ITERATOR_CRON);
            if($this->isBusy())
            {
                $this->free();
            }
        }
        return $this;
    }
    
    /**
     * Is current process launched by ajax
     * 
     * @return bool
     */
    public function isAjax()
    {
        return ($this->_iterator == self::ITERATOR_AJAX);
    }
    
    /**
     * Attempt to switch between iterator types
     */
    protected function _switchIterator()
    {
        if($this->isAjax() && $this->_iterator != $this->_config()->get('iterator'))
        {
            $this->_config()->set('iterator', $this->_iterator);
        }
    }

    /**
     * Check if some process already started
     * 
     * @return bool
     */
    public function isBusy()
    {
        return ($this->_config()->get('status') == self::ITERATOR_STATUS_BUSY);
    }
    
    /**
     * Set new process and its options
     * 
     * @param string $process Set new process to config. Process should be like 'export::makeExport' where 'export' is some model and 'makeExport' is that model's method
     * @param array $options Optioanl param for process options
     * 
     * @return @return Aitoc_Aitexporter_Model_Processor
     */
    public function setProcess($process, $options = array())
    {
        $this->_config()->set('process', $process);
        $this->_config()->set('options', $options);
        return $this; 
    }
    
    /**
     * Get current process
     * 
     * @return string
     */
    public function getProcess()
    {
        return $this->_config()->get('process');
    }

    
    /**
     * Set new process and its options
     * 
     * @param int $forward Set forward flag. If forward flag value is 1 processor tries to complete the whole chain of processes at once.
     * 
     * @return @return Aitoc_Aitexporter_Model_Processor
     */
    public function setForward($forward)
    {
        $this->_config()->set('forward', $forward);
        return $this; 
    }
    
    /**
     * Get current forward flag
     * 
     * @return int
     */
    public function getForward()
    {
        return $this->_config()->get('forward');
    }    
    
    
    /**
     * Attempt to launch the iterator if it's free and alowed for current iterator type
     *
     * @return bool
     */
    public function launch()
    {
        if(!$this->isBusy() && $this->getProcess() && $this->_config()->get('iterator') == $this->_iterator)
        {
            $this->_config()->set('status', self::ITERATOR_STATUS_BUSY);
            $this->_saveConfig();
    
            return true;
        }
        return false;
    }
    
    /**
     * Attempt to free the iterator if it's busy
     */
    public function free()
    {
        if($this->isBusy())
        {
            $this->_config()->set('status', self::ITERATOR_STATUS_FREE);
            $this->_saveConfig();
        }
    }
    
    /**
     * Save processor config
     * 
     * @return Aitoc_Aitexporter_Model_Processor
     */
    public function save()
    {
        return $this->_saveConfig();
    }
    
    public function run()
    {
        if((bool)$this->launch() && (bool)$this->getProcess())
        {
            try
            {
                // extract model and method from the config
                list($processModel, $processMethod) = explode('::', $this->getProcess());
                $model = Mage::getModel('aitexporter/'.$processModel);
                $model->{$processMethod}($this->_config()->get('options'));
            }
            catch(Exception $e)
            {
                Mage::getSingleton('aitexporter/processor_response')->addMessage('error', $e->getMessage());
                Mage::log((string)$e, false, 'aitexporter_exception.log');
            }
    
            $response = Mage::getSingleton('aitexporter/processor_response');
            $newProcess = $response->getSwitch();
    
            if(!empty($newProcess))
            {
                $this->setProcess($newProcess['process'], $newProcess['options']);
            }
            
            if(!isset($newProcess['process']) || empty($newProcess['process']))
            {
                $this->setForward(0);
            }            
            
            $this->free();
            
            if($this->getForward())
            {
                $this->run();
            }
        }
    }
}