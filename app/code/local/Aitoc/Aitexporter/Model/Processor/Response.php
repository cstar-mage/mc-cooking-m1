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
class Aitoc_Aitexporter_Model_Processor_Response
{
    protected $_messages = array();
    protected $_switch = array();
    protected $_redirect;
    
    protected $_allowedMessageTypes = array(
        Aitoc_Aitexporter_Model_Processor::RESULT_NOTICE,
        Aitoc_Aitexporter_Model_Processor::RESULT_ERROR,
        Aitoc_Aitexporter_Model_Processor::RESULT_SUCCESS
    );
    
    /**
     * Set next iteration new model/method/options. Call with empty params or without params to stop iterator.
     * 
     * @param string $method Model and method like 'export::initExport'
     * @param array $options New operation default options
     * 
     * @return Aitoc_Aitexporter_Model_Processor_Response
     */
    public function setSwitch($method = '', $options = array())
    {
        $this->_switch = array(
                    'process' => $method,
                    'options' => $options
        );
        return $this;
    }
    
    /**
     * Get new model/method/options
     * 
     * @return array
     */
    public function getSwitch()
    {
        return $this->_switch;
    }
    
    /**
     * Add message to display at processor page
     * 
     * @param string $type Type of the message according to abstract iterator constants
     * @param string $message Message text
     * 
     * @return Aitoc_Aitexporter_Model_Processor_Response
     * @throws Exception
     */
    public function addMessage($type, $message)
    {
        if(in_array($type, $this->_allowedMessageTypes))
        {
            $this->_messages[$type][] = $message;
        }
        else
        {
            throw new Exception("Unknow iterator responce message type '{$type}'");
        }
        return $this;
    }
    
    /**
     * Get messages to display
     * 
     * @param string $type Get messages of concrete type
     * 
     * @return array
     */
    public function getMessages($type = '')
    {
        if ($type)
        {
            return isset($this->_messages[$type])?$this->_messages[$type]:array();
        }
        else 
        {
            return $this->_messages;
        }
    }
    
    public function setRedirect($url)
    {
        $this->_redirect = $url;
        return $this;
    }
    
    public function getRedirect()
    {
        return $this->_redirect;
    }
}