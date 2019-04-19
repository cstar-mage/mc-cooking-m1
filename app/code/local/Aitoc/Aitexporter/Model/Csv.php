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
class Aitoc_Aitexporter_Model_Csv
{
    private $_xml2csvConfig;

    /**
     * @param string $xmlPath
     * @param string $csvPath
     * @param string $delimiter
     * @param string $enclose
     */
    public function xml2csv($xmlPath, $csvPath = null, $delimiter = ',', $enclose = '"')
    {
        if (empty($csvPath))
        {
            $csvPath = dirname($xmlPath).DS.'tmp'.md5(uniqid(mt_rand())).'.csv';
        }

        //$xml = new SimpleXMLElement($xmlPath, null, true);
        $xml = new Aitoc_Aitexporter_Model_Extendedxml($xmlPath, null, true);

        // Create headers structure
        $headers    = array();
        $headersXml = new Aitoc_Aitexporter_Model_Extendedxml('<order />');
        $config     = Mage::getConfig()->getNode('aitexporter/csv');
        $this->_xml2csvConfig = array();
        foreach ($config->children() as $child)
        {
            $this->_xml2csvConfig[$child->getName()] = (string)$child;
        }

        foreach ($xml->children() as $orderXml)
        {
            $this->_createCsvHeadersRecursive($orderXml, $headersXml);
        }

        $csvHandle = fopen($csvPath, 'w');
        $headers   = $this->_headersXml2arrayRecursive($headersXml);
        fputcsv($csvHandle, $headers, $delimiter, $enclose);

        foreach ($xml->children() as $orderXml)
        {
            $order = $this->_orderXml2csv($headers, $orderXml);
            fputcsv($csvHandle, $order, $delimiter, $enclose);
        }

        fclose($csvHandle);

        return $csvPath;
    }

    protected function _createCsvHeadersRecursive(SimpleXMLElement $elementXml, SimpleXMLElement $headersXml, $iterate = false)
    {
        $iteration = 0;
        foreach ($elementXml->children() as $childXml)
        {
            $iteration++;
            if ('fields' == $childXml->getName())
            {
                $this->_createCsvHeadersRecursive($childXml, $headersXml);
            }
            elseif (in_array($childXml->getName(), $this->_xml2csvConfig))
            {
                $this->_createCsvHeadersRecursive($childXml, $headersXml, true);
            }
            elseif ($childXml->getName())
            {
                $childName = $childXml->getName();
                if ($iterate)
                {
                    $childName = $childXml->getName().$iteration;
                }
                
                if (!isset($headersXml->{$childName}))
                {
                    $childHeadersXml = $headersXml->addChild($childName);
                }
                else
                {
                    $childHeadersXml = $headersXml->{$childName}[0];
                }

                $this->_createCsvHeadersRecursive($childXml, $childHeadersXml);
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

                    if (isset($this->_xml2csvConfig[$pathItemBase]))
                    {
                        $pathItem = $this->_xml2csvConfig[$pathItemBase].'/'.$pathItemBase.'['.$pathItemIndex.']';
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
     * @param string $csvPath
     * @param string $xmlPath
     * @param string $delimiter
     * @param string $enclose
     * @param boolean $isCheckStore
     * @return string
     * @throws Exception
     */
    public function csv2xml($csvPath, $xmlPath = null, $delimiter = ',', $enclose = '"', $isCheckStore = true)
    {
        if (empty($xmlPath))
        {
            $xmlPath = dirname($csvPath).DS.'tmp'.md5(uniqid(mt_rand())).'.xml';
        }

        $csvHandle = fopen($csvPath, 'r');

        if (false === $csvHandle)
        {
            throw new Exception(Mage::helper('aitexporter')->__('CSV File Does not exist'));
        }

        $isHeaders = true;
        $headers   = array();
        $xml       = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><orders />');
        $xml       = new Aitoc_Aitexporter_Model_Extendedxml('<?xml version="1.0" encoding="UTF-8"?><orders />');
        $csvConfig = (array) Mage::getConfig()->getNode('aitexporter/csv');
        
        while (false !== ($row = fgetcsv($csvHandle, 0, $delimiter, $enclose)))
        {
            if ($isHeaders)
            {
                $headers   = $row;
                $isHeaders = false;

                $missedFields = array();
//***         Changed for some odd reason ***//
//                $checkFields  = array('increment_id', 'state');
                $checkFields  = array('increment_id');
                if ($isCheckStore)
                {
                    $checkFields[] = 'store_code';
                }

                foreach ($checkFields as $checkField)
                {
                    if (!in_array($checkField, $headers))
                    {
                        $missedFields[] = $checkField;
                    }
                }

                if (count($missedFields))
                {
                    fclose($csvHandle);
                    throw new Exception(Mage::helper('aitexporter')->__('CSV File Does not contain required fields: "%s"', join('", "', $missedFields)));
                }
            }
            else
            {
                $orderXml       = $xml->addChild('order');
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
                        $orderFieldsXML->addChild($element, htmlentities($row[$key]));
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

                        $iterationXml->addChild($lastField, (string)htmlentities($row[$key]));
                    }

                    $xml->asXML($xmlPath);
                }
            }
        }

        fclose($csvHandle);

        return $xmlPath;
    }

    public function xpath2csvHeader($xpath)
    {
        
    }
}