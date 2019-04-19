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
class Aitoc_Aitexporter_Model_Extendedxml extends SimpleXMLElement
{
    /** 
      * Add CDATA text in a node 
      * @param string $cdata_text The CDATA value  to add 
      */ 
   private function addCData($cdata_text) 
   { 
    $node= dom_import_simplexml($this); 
    $no = $node->ownerDocument; 
    $node->appendChild($no->createCDATASection($cdata_text)); 
   } 

   /** 
    * Create a child with CDATA value 
    * @param string $name The name of the child element to add. 
    * @param string $cdata_text The CDATA value of the child element. 
    */ 
     public function addChildCData($name,$cdata_text) 
     { 
         $child = parent::addChild($name); 
         $child->addCData($cdata_text); 
         return $child;
     }
     
     public function addChild($name, $text = '',$namespace='')
     {
         if(is_numeric($text) || $text == '') {
             return parent::addChild($name, $text);
         } else {
             return $this->addChildCData($name, $text);
         }
     }
}