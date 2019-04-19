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
class Aitoc_Aitexporter_Model_File_Csv extends Aitoc_Aitexporter_Model_File_Abstract 
{
    const DEFAULT_DELIMITER = ',';
    const DEFAULT_ENCLOSE = '"';
    
    protected $_headersMode = false;
    protected $_delimiter = self::DEFAULT_DELIMITER;
    protected $_enclose = self::DEFAULT_ENCLOSE;
    protected $_headers;
    
    /**
     * Read one entity from xml file
     * 
     * @param integer $offset Offset in bytes to begin reading from
     * @return array
     */
    public function read($offset = 0)
    {
        $fh = $this->_getFh();

        if($fh && !@feof($fh))
        {
            if($offset)
            {
                $this->_offset = $offset;
            }
            
            if(!$this->_offset) // skip two initial rows
            {
                @fgets($fh);
                $this->_offset = @ftell($fh);
            }
            else
            {
                @fseek($fh, $this->_offset);
            }
            
            $dataArray = @fgetcsv($fh, 0, $this->_delimiter, $this->_enclose);
            
            $this->_offset = @ftell($fh);

            return $dataArray;
        }
        return false;
    }
    
    /**
     * @return Aitoc_Aitexporter_Model_File_Csv 
     */
 
    public function setHeadersMode()
    {
        $this->_headersMode = true;
        return $this;
    }
    
    /**
     * Write header for visibility non-ANSI chars in CSV in UTF-8
     * 
     * @return Aitoc_Aitexporter_Model_File_Csv 
     */    
    
    protected function _writeHeaders()
    {
        if ($this->_headersMode)
        {
            fwrite($this->_getFh(),chr(0xEF).chr(0xBB).chr(0xBF),3);
        }
        return $this;
    }

    /**
     * @return integer
     */
    public function count()
    {
        $fh = $this->_getFh();
        $count = 0;
    
        if($fh)
        {
            @rewind($fh);
            @fgets($fh); //remove headers
    
            while(false !== @fgets($fh))
            {
                $count++;
            }
        }
        return $count;
    }
    
    public function setDelimiter($delimiter)
    {
        $this->_delimiter = str_replace('\t',"\t",$delimiter);
        return $this;
    }
    
    public function setEnclose($enclose)
    {
        $this->_enclose = $enclose;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getHeaders()
    {
        if(is_null($this->_headers))
        {
            $fh = $this->_getFh();
            @rewind($fh);
            /* check first 3 symbols. if we wrote headers for non-ANCI characters
             * they will be different than 'inc'. When we use fgets - we move cursor on 3 symbols.
             * if they are eq 'inc' then start reading file from the begining
             */
            if (fgets($fh,4) == 'inc')
            {
                @rewind($fh);
            }
            
            $this->_headers = (array)fgetcsv($fh, 0, $this->_delimiter, $this->_enclose);
    
            @fseek($fh, $this->_offset); // return to the position before getting headers
        }
        return $this->_headers;
    }
    
    /**
     * @param array $data
     */
    public function write($data)
    {
        $this->_writeHeaders();
        return (bool)fputcsv($this->_getFh(), $data, $this->_delimiter, $this->_enclose);
    }
}