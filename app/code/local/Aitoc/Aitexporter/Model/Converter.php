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
class Aitoc_Aitexporter_Model_Converter
{
    private $_xml2csvConfig;
    
    protected function _getXml2csvConfig($param = null, $default = null)
    {
        if(is_null($this->_xml2csvConfig))
        {
            $this->_xml2csvConfig = array();
            $config = Mage::getConfig()->getNode('aitexporter/csv');
            foreach ($config->children() as $child)
            {
                $this->_xml2csvConfig[$child->getName()] = (string)$child;
            }
        }
        
        if($param)
        {
            return isset($this->_xml2csvConfig[$param])?$this->_xml2csvConfig[$param]:$default;
        }
        else
        {
            return $this->_xml2csvConfig;
        }
    }

    /**
     * @param string $headersXmlPath
     * @param string $csvPath
     * @param string $delimiter
     * @param string $enclose
     */
    public function xml2csvInit($headersXmlPath, $csvPath, $delimiter = Aitoc_Aitexporter_Model_File_Csv::DEFAULT_DELIMITER, $enclose = Aitoc_Aitexporter_Model_File_Csv::DEFAULT_ENCLOSE)
    {
        // Create headers structure
        $headers    = array();
        $headersXml = new Aitoc_Aitexporter_Model_Extendedxml($headersXmlPath, null, true);
        $headers    = $this->_headersXml2arrayRecursive($headersXml);

        Mage::getModel('aitexporter/file_csv', $csvPath)
            ->setHeadersMode()
            ->setDelimiter($delimiter)
            ->setEnclose($enclose)
            ->write($headers);
    }
    
    /**
     * @param SimpleXMLElement $order
     * @param Aitoc_Aitexporter_Model_File_Csv $csv
     */
    public function xml2csv(SimpleXMLElement $order, Aitoc_Aitexporter_Model_File_Csv $csv)
    {
        $orderArray = $this->_orderXml2csv($csv->getHeaders(), $order);
        $csv->write($orderArray);
    }

    public function createCsvHeadersRecursive(SimpleXMLElement $elementXml, SimpleXMLElement $headersXml, $iterate = false)
    {
        $iteration = 0;
        foreach ($elementXml->children() as $childXml)
        {
            $iteration++;
            $childName = $childXml->getName();
            if ('fields' == $childName)
            {
                $this->createCsvHeadersRecursive($childXml, $headersXml);
            }
            elseif (in_array($childName, $this->_getXml2csvConfig()))
            {
                $this->createCsvHeadersRecursive($childXml, $headersXml, true);
            }
            elseif ($childName)
            {
                if ($iterate)
                {
                    $childName .= $iteration;
                }
                
                if (!isset($headersXml->{$childName}))
                {
                    $childHeadersXml = $headersXml->addChild($childName);
                }
                else
                {
                    $childHeadersXml = $headersXml->{$childName}[0];
                }

                $this->createCsvHeadersRecursive($childXml, $childHeadersXml);
            }
        }
    }

    protected function _headersXml2arrayRecursive(SimpleXMLElement $headersXml, $basePath = '')
    {
        $headersArray = array();
        if (count($headersXml->children()))
        {
            foreach ($headersXml->children() as $childXml)
            {
                $childHeaders = $this->_headersXml2arrayRecursive($childXml, ($basePath ? $basePath.':' : '').$childXml->getName());
                $headersArray = array_merge($headersArray, $childHeaders);
            }
        }
        else
        {
            $headersArray[] = $basePath;
        }

        return $headersArray;
    }

    protected function _orderXml2csv(array $headers, SimpleXMLElement $orderXml)
    {
        $orderArray = array();

        foreach ($headers as $key => $header)
        {
            $orderArray[$key] = '';
            $elementPath = explode(':', $header);
            if (1 == count($elementPath))
            {
                if (isset($orderXml->fields->{$header}[0]))
                {
                    $orderArray[$key] = (string)$orderXml->fields->{$header}[0];
                }
            }
            else
            {
                $lastField = array_pop($elementPath);
                foreach ($elementPath as $i => & $pathItem)
                {
                    $pathItemBase = rtrim($pathItem, '1234567890');

                    $pathItemIndex = 0;
                    if ($pathItemBase != $pathItem)
                    {
                        $pathItemIndex = (int)substr($pathItem, strlen($pathItemBase));
                    }

                    if ($this->_getXml2csvConfig($pathItemBase))
                    {
                        $pathItem = $this->_getXml2csvConfig($pathItemBase).'/'.$pathItemBase.'['.$pathItemIndex.']';
                    }
                    elseif ($pathItemBase == 'aitcheckoutfields')
                    {
                        $pathItem = $pathItemBase;
                    }
                    elseif (0 == $i) 
                    {
                        $pathItem = 'fields/'.$pathItem;
                    }
                }

                $xpath        = join('/', $elementPath).'/'.$lastField.'[1]';
                $elementXml   = current($orderXml->xpath($xpath));
                $elementValue = '';
                if ($elementXml instanceof SimpleXMLElement)
                {
                    $elementValue = (string)$elementXml;
                }

                $orderArray[$key] = $elementValue;
            }
        }

        return $orderArray;
    }

    /** Creates XML file from the CSV one
     * 
     * @param array $row CSV order data
     * @param array $headers CSV headers
     * @param Aitoc_Aitexporter_Model_File_Xml $xml
     * @param boolean $isCheckStore
     */
    public function csv2xml($row, $headers, Aitoc_Aitexporter_Model_File_Xml $xml, $isCheckStore = true, $addCustomers)
    {
        $csvConfig = (array) Mage::getConfig()->getNode('aitexporter/csv');
        
        $orderXml       = new Aitoc_Aitexporter_Model_Extendedxml('<?xml version="1.0" encoding="UTF-8"?><order />');
        $orderFieldsXML = $orderXml->addChild('fields');

        foreach ($headers as $key => $element)
        {
            if ('' === $row[$key])
            {
                continue;
            }

            $elementPath = explode(':', $element);
            if (1 == count($elementPath))
            {
                $orderFieldsXML->addChild($element, htmlspecialchars($row[$key],ENT_NOQUOTES));
            }
            else
            {
                $lastField    = array_pop($elementPath);
                $iterationXml = $orderXml;
                foreach ($elementPath as $i => $pathItem)
                {
                    $pathItemBase = rtrim($pathItem, '1234567890');

                    $pathItemIndex = 0;
                    if ($pathItemBase != $pathItem)
                    {
                        $pathItemIndex = (int)substr($pathItem, strlen($pathItemBase)) - 1;
                    }

                    if (empty($csvConfig[$pathItemBase]))
                    {
                        if (!isset($iterationXml->{$pathItem}))
                        {
                            $iterationXml = $iterationXml->addChild($pathItem);
                        }
                        else 
                        {
                            $iterationXml = $iterationXml->{$pathItem}[0];
                        }
                    }
                    else 
                    {
                        if (!isset($iterationXml->{$csvConfig[$pathItemBase]}))
                        {
                            $iterationXml->addChild($csvConfig[$pathItemBase]);
                        }

                        while (!isset($iterationXml->{$csvConfig[$pathItemBase]}->{$pathItemBase}[$pathItemIndex]))
                        {
                            $iterationXml->{$csvConfig[$pathItemBase]}->addChild($pathItemBase);
                        }

                        $iterationXml = $iterationXml->{$csvConfig[$pathItemBase]}->{$pathItemBase}[$pathItemIndex];
                    }
                }

                $iterationXml->addChild($lastField, (string)htmlspecialchars($row[$key],ENT_NOQUOTES));
            }
        }
        
        $xml->write($orderXml);
    }

    public function xpath2csvHeader($xpath)
    {
        
    }
}