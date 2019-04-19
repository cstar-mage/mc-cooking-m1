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
abstract class Aitoc_Aitexporter_Model_File_Abstract 
{
    protected $_filePath;
    protected $_fh;
    protected $_offset = 0;
    
    public function __construct($filePath)
    {
        $this->_filePath = $filePath;
    }
    
    public function __destruct()
    {
        if($this->_fh)
        {
            @fclose($this->_fh);
        }
    }
    
    public function getOffset()
    {
        return $this->_offset;
    }
    
    /**
     * Attempt to open file for reading and writing
     * 
     * @return resource | false
     */
    protected function _getFh()
    {
        if(is_null($this->_fh))
        {    
            $this->_fh = @fopen($this->_filePath, 'a+');
        }
        return $this->_fh;
    }
    
    /**
     * Read one entity from xml file
     * 
     * @param integer $offset Offset in bytes to begin reading from
     */
    abstract public function read($offset = 0);
    
    /**
     * @return integer
     */
    abstract public function count();

    /**
     * @return bool
     */
    abstract public function write($data);
}