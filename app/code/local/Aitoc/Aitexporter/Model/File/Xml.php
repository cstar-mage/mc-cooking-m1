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
class Aitoc_Aitexporter_Model_File_Xml extends Aitoc_Aitexporter_Model_File_Abstract
{
    /**
     * Read one entity from xml file
     *
     * @param integer $offset Offset in bytes to begin reading from
     * @return SimpleXmlElement | false
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
                @fgets($fh);
                $this->_offset = @ftell($fh);
            }
            else
            {
                @fseek($fh, $this->_offset);
            }

            $xml = $this->_extractEntity();

            $this->_offset = @ftell($fh);

            $result = false; //default return value

            if($xml) //if not the last or empty row
            {
                $result = new Aitoc_Aitexporter_Model_Extendedxml($xml);
            }
            return $result;
        }
        return false;
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
            @fgets($fh);
            @fgets($fh);

            while($this->_extractEntity())
            {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return string
     */
    protected function _extractEntity()
    {
        $result = '';
        $endTag = false;
        $len = 0;
        while($row = @fgets($this->_getFh()))
        {
            if(!$endTag && preg_match('|^\<(.*)\>(.*)|Uis', $row, $match)) {
                $endTag = '</'.$match[1].'>';
                $len = strlen($endTag);
            }
            if(!$result && substr(trim($row), 0, 9) == '</orders>') //new entity starts with some closing xml element - expecting end of file
            {
                break;
            }
            $result .= $row;
            $row = trim($row);
            if(($len == 0 && substr($row, -8, 8) == '</order>') || ($len > 0 && substr($row, -$len, $len) == $endTag)  ) //line ends with some xml element - expecting end of entity
            {
                break;
            }
        }

        return $result;
    }

    /**
     * @param SimpleXMLElement $data
     */
    public function write($data)
    {
        if($data instanceof SimpleXMLElement)
        {
            $entityString = $data->asXML();
            $pos = strpos($entityString, PHP_EOL);
            if($pos === false) {
                $pos = strpos($entityString, 0x0A);//unix type
            }
            $entityString = trim(substr($entityString, $pos+1)); // remove xml declaration and trim

        }else{
            $entityString = $data;
        }

        return @fwrite($this->_getFh(), $entityString.PHP_EOL);
    }
}