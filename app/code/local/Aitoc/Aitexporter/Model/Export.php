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
class Aitoc_Aitexporter_Model_Export extends Mage_Core_Model_Abstract
{
    /**
     * @var Varien_Object
     */
    private $_config;
    private $_exportTmpPath;
    
    /**
     * @var Aitoc_Aitexporter_Model_Processor_Response
     */
    protected $_response;
    
    protected $_prfxPath;
    
    /**
     * @var Aitoc_Aitexporter_Model_Processor_Config
     */
    protected $_processorConfig;

    public function _construct()
    {
        parent::_construct();

        $this->_init('aitexporter/export');
    }
    
    protected function _initIteration($options)
    {
        if(!isset($options['id']))
        {
            $options['id']=$this->getId();
        }
        $this->load($options['id']);
        $this->_response        = Mage::getSingleton('aitexporter/processor_response');
        $this->_processorConfig = Mage::getSingleton('aitexporter/processor_config');
        $this->_prfxPath        = Mage::helper('aitexporter')->getTmpPath().'export_'.$this->getId();
    }
    
    protected function _prepareXmlExportFile()
    {
        $path = $this->_prfxPath.'.xml';
        $xml = Mage::getModel('aitexporter/file_xml', $path);
        if(!file_exists($path))
        {
            $xml->write('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<orders>');
        }
    }

    protected function _prepareHeadersXmlExportFile()
    {
        $headersXmlPath = $this->_prfxPath.'_headers.xml';
        $headersXml = new Aitoc_Aitexporter_Model_Extendedxml('<?xml version="1.0" encoding="UTF-8"?><order />');
        $headersXml->asXML($headersXmlPath);
    }

    protected function _isCsvExport()
    {
        $parse = $this->getConfig()->getParse();
        return isset($parse['type']) && ($parse['type'] == 'csv');
    }

    public function initExport($options)
    {
        $this->_initIteration($options);
        //reset option
        $this->_processorConfig->updateOption('from', 0);
        // Calls to create and filter order items
        $orders = $this->getOrders();
        
        if (0 == count($orders->getItems()))
        {
            // stop iterator with appropriate message
            $this->_response->addMessage('notice', Mage::helper('aitexporter')->__('No orders found using current settings. Please change settings in "Order Filters" menu and try again.'))
                ->setSwitch();
            $this->delete();                
            return;
        }
        // prepare new export xml file
        $this->_prepareXmlExportFile();
        // prepare new export headers xml file for upcoming conversion from xml to csv
        if ($this->_isCsvExport())
        {
            $this->_prepareHeadersXmlExportFile();
        }
        
        $newOptions = array(
            'id'       => $this->getId(),
            'from'     => 0,
            'limit'    => 25,
            'entities' => $this->getOrdersCount(),
        );
        
        // switching processor to export iterations
        $this->_response->setSwitch('export::makeExport', $newOptions);
    }

    public function beforeExport() 
    {
        if(!Mage::app()->getStore()->isAdmin()) 
        {
            return;
        }
        $config = Mage::getSingleton('aitexporter/processor_config');
        $session = Mage::getSingleton('adminhtml/session');
        $order_exp_from = $session->getData("order_exp_from");
        if(!is_null($order_exp_from) && $config->getOption('from')>0) 
        {
            if($order_exp_from == $config->getOption('from'))
            {
			    $session->setData("order_exp_from", NULL);
                $config->resetExport();
				throw new Exception(Mage::helper('aitexporter')->__('Export process has been stopped. cycling getting orders'));
            }
        }
        $from = $config->getOption('from');
        $session->setData("order_exp_from", $from);        
    }
    
    /**
     * Works with loaded object
     */
    public function makeExport($options = array())
    {
        $this->_initIteration($options);
        
        $config = $this->getConfig();
        
        // Calls to create and filter order items
        if(!isset($options['limit']))
        {
            $options['limit']=25;
        }
        $orders = $this->getOrders($this->_processorConfig->getOption('from'), $options['limit']);
        /* @var $orders Aitoc_Aitexport_Model_Mysql4_Export_Order_Collection */
        
        if ($this->getOrdersCount() > 0)
        {
            $this->beforeExport();
            $xmlPath = $this->_prfxPath.'.xml';
            $xml = Mage::getModel('aitexporter/file_xml', $xmlPath);
            
            // Retrieve headers xml and converter model if csv conversion requested 
            if ($this->_isCsvExport())
            {
                $headersXmlPath = $this->_prfxPath.'_headers.xml';
                //$headersXml = new SimpleXMLElement($headersXmlPath, null, true);
                $headersXml = new Aitoc_Aitexporter_Model_Extendedxml($headersXmlPath, null, true);
                $converter = Mage::getModel('aitexporter/converter');
            }
    
            // Fill document in with orders
            $orderExport = Mage::getModel('aitexporter/export_type_order');
            $from = $this->_processorConfig->getOption('from');
            if(!$from)
            {
                $from = isset($options['from'])?$options['from']:0;
            }
            foreach ($orders as $order)
            {
                $orderXml = new Aitoc_Aitexporter_Model_Extendedxml('<order/>');
                $order = Mage::getModel('sales/order')->load($order->getOrderId());
                $orderExport->prepareXml($orderXml, $order, $config);
                $xml->write($orderXml);
                
                // Update headers xml
                if ($this->_isCsvExport())
                {
                    $converter->createCsvHeadersRecursive($orderXml, $headersXml);
                    $headersXml->asXML($headersXmlPath);
                }
                $from++;
                $this->_processorConfig->updateOption('from', $from);
            }
        }
        if($this->getOrdersCount() < $options['limit'])
        {
           $this->_response->setSwitch('export::finalizeXmlExport', array('id' => $this->getId()));
        }
    }
    
    public function finalizeXmlExport($options)
    {
        $this->_initIteration($options);
        
        $xmlPath = $this->_prfxPath.'.xml';
        $xml = Mage::getModel('aitexporter/file_xml', $xmlPath);
        
        $xml->write('</orders>');

        if ($this->_isCsvExport())
        {
            // if csv file requested - proceed to conversion process
            $this->_response->setSwitch('export::initConvert', array('id' => $this->getId()));
            return;
        }

        if($this->getProfileId())
        {
            $profile = Mage::getSingleton('aitexporter/config')->getExportProfile($this->getProfileId());
            if($profile->getXsl()) {
                try
                {
                    $this->applyXsl($xmlPath, $profile);
                }
                catch(Exception $e)
                {
                    $this->_response->addMessage('notice', Mage::helper('aitexporter')->__('Xsl modification was not done: ') . $e->getMessage());
                }
            }
        }
        $this->_response->setSwitch('export::finishExport', array('id' => $this->getId()));
    }

    public function initConvert($options)
    {
        $this->_initIteration($options);
        
        $headersXmlPath = $this->_prfxPath.'_headers.xml';
        $csvPath        = $this->_prfxPath.'.csv';
        
        $parse = $this->getConfig()->getParse();
        if(!file_exists($csvPath))
        {
            Mage::getSingleton('aitexporter/converter')->xml2csvInit(
                    $headersXmlPath,
                    $csvPath,
                    isset($parse['delimiter']) ? $parse['delimiter'] : Aitoc_Aitexporter_Model_File_Csv::DEFAULT_DELIMITER,
                    isset($parse['enclose'])   ? $parse['enclose']   : Aitoc_Aitexporter_Model_File_Csv::DEFAULT_ENCLOSE
            );
            $this->_processorConfig->updateOption('from', null);
        }

        $xml        = Mage::getModel('aitexporter/file_xml', $this->_prfxPath.'.xml');
        $newOptions = array(
            'id'       => $this->getId(),
            'from'     => 0,
            'limit'    => 25,
            'count'    => $xml->count(),
            'entities' => @filesize($this->_prfxPath.'.xml'),
        );
        $this->_response->setSwitch('export::makeConvert', $newOptions);
    }

    public function makeConvert($options)
    {
        $this->_initIteration($options);
        
        $xmlPath        = $this->_prfxPath.'.xml';
        $headersXmlPath = $this->_prfxPath.'_headers.xml';
        $csvPath        = $this->_prfxPath.'.csv';
        
        $xml        = Mage::getModel('aitexporter/file_xml', $xmlPath);
        $csv        = Mage::getModel('aitexporter/file_csv', $csvPath);
        $converter  = Mage::getSingleton('aitexporter/converter');
        
        $parse = $this->getConfig()->getParse();
        $csv->setDelimiter(isset($parse['delimiter']) ? $parse['delimiter'] : Aitoc_Aitexporter_Model_File_Csv::DEFAULT_DELIMITER)
              ->setEnclose(isset($parse['enclose'])   ? $parse['enclose']   : Aitoc_Aitexporter_Model_File_Csv::DEFAULT_ENCLOSE);

        $from = $options['from'];
        $limit = $options['limit']; //$options['count'];
        for ($i = 0; $i < $limit; $i++)
        {
            if($order = $xml->read($from))
            {
                $converter->xml2csv($order, $csv);
                $from = $xml->getOffset();
                $this->_processorConfig->updateOption('from', $from);
            }else{
                $this->_response->setSwitch('export::finishExport', array('id' => $this->getId()));
            }
        }
    }

    public function finishExport($options)
    {
        $this->_initIteration($options);
        
        $config = $this->getConfig();
        
        $path = $this->getTmpFilePath();

        // Transfer ready file depending on config
        switch ($config['file']['type'])
        {
            case 'file':
                $this->fileCopy($path);
                break;
        
            case 'ftp':
                if ($this->ftpUpload($path))
                {
                    $this->setIsFtpUpload(1)->save();
                }
                break;
        
            case 'email':
                if ($this->emailSend($path))
                {
                    $this->setIsEmail(1)->save();
                }
                break;

        }
        $this->_processorConfig->updateOption('from', null);
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('aitexporter')->__('Export is successfully complete.'));
        $this->_response->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml/aitexporter_export/viewLog', array('id' => $this->getId())));
        $this->_response->setSwitch();
    }

    public function fileCopy($filePath)
    {
        $file   = new Varien_Io_File();
        $config = $this->getConfig();
        if(isset($config['file']['path'])) {
            $localPath = Mage::getBaseDir() . DS . trim($config['file']['path'], ' \\/');
        } else {
            $localPath = Mage::getBaseDir();
        }
        if (!is_dir($localPath))
        {
            $localPath = Mage::getBaseDir().DS.trim($localPath, ' /');
        }

        if (!is_dir($localPath) && !$file->mkdir($localPath))
        {
            throw new Exception(Mage::helper('aitexporter')->__('Local export directory %s does not exist or does not have read permissions', $localPath));
        }

        $userDefinedExportPath = $localPath.DS.$this->getFilename();
        $result                = $file->cp($filePath, $userDefinedExportPath);
        if(!$result)
        {
            throw new Exception(Mage::helper('aitexporter')->__('File %s hasn\'t been copied from temporaty folder to %s', $filePath, $userDefinedExportPath));
        }
    }

    
    public function ftpUpload($filePath)
    {
        $config = $this->getConfig();
        $result = true;
        $ftp    = new Varien_Io_Ftp();
        $connectParams = array(
            'host'     => trim($config['ftp']['host']),
            'user'     => trim($config['ftp']['user']),
            'password' => trim($config['ftp']['password']),
            'passive'  => trim($config['ftp']['passive']),
            );

        if(strpos($connectParams['host'],':'))
        {
            list($connectParams['host'],$connectParams['port']) = explode(':',$connectParams['host']);
        }

        try 
        {
            $result &= $ftp->open($connectParams);
            $result &= $ftp->cd('/' . trim($config['ftp']['path'], ' /') . '/');
        }
        catch (Exception $e)
        {
            throw new Exception(Mage::helper('aitexporter')->__('FTP error: %s', $e->getMessage()));
        }

        if($result == 0)
        {
            throw new Exception(Mage::helper('aitexporter')->__('FTP error: invalid ftp folder %s', trim($config['ftp']['path'], ' /')));
        }

        $result &= $ftp->write($this->getFilename(), $filePath);
        if ($result == 0)
        {
            throw new Exception(Mage::helper('aitexporter')->__('FTP creation error: cannot write file %s to its folder', $this->getFilename()));
        }

        $ftp->close();
        
        return true;
    }
    
    public function emailSend($filePath)
    {
        $config    = $this->getConfig()->getEmail();
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);

        try 
        {
            /* var $emailModel Mage_Core_Model_Email_Template */
            $emailModel = Mage::getModel('core/email_template');
            if (!file_exists($filePath))
            {
                return ;
            }
            $fileContents = file_get_contents($filePath); /*(Here put the filename with full path of file, which have to be send)*/
            
            $emailModel->getMail()->createAttachment($fileContents, 'text/csv')->filename = $this->getFilename();

            $emailModel->sendTransactional(
                $config['template'], 
                $config['sender'],
                $config['sendto'],
                $config['sendto'],
                array('order_numbers' => $this->_getOrderNumbers())
                );
            $translate->setTranslateInline(true);
        }
        catch (Exception $e) 
        {
            $translate->setTranslateInline(true);
            throw new Exception('Email sending error: '.$e->getMessage());
        }
        
        return true;
    }

    protected function _getOrderNumbers()
    {
        $orderIds     = array();
        $orderNumbers = array();

        foreach($this->getOrders() as $order)
        {
            $orderIds[] = $order->getOrderId();
        }

        $orderCollection = Mage::getModel('sales/order')->getCollection();
        /* @var $orderCollection Mage_Sales_Model_Mysql4_Order_Collection */

        $orderCollection
            ->addFieldToFilter('entity_id', array('in' => $orderIds))
            ->getSelect()->setPart(Zend_Db_Select::COLUMNS, array())->columns(array('entity_id', 'increment_id'));

        foreach ($orderCollection as $order)
        {
            $orderNumbers[] = $order->getIncrementId();
        }

        return join(', ', $orderNumbers);
    }

    /**
     * @return Aitoc_Aitexporter_Model_Mysql4_Export_Order_Collection
     */
    public function getOrders($from = 0, $limit = 0)
    {
        $order           = Mage::getModel('aitexporter/export_order');
        $orderCollection = $order->getCollection();

        // export init only
        if (0 == $this->getOrdersCount())
        {
            $order->assignOrders($this);
        }

        $orderCollection->addFieldToFilter('export_id', $this->getId());

        // for iterative processes only
        if($limit)
        {
            $orderCollection->getSelect()->limit($limit, $from);
        }

        $orderCollection->load();
        $size = count($orderCollection->getItems());
        $this->setOrdersCount($size)->save();

        return $orderCollection;
    }
    
    /** Gets export config as an object
     * 
     * @return Varien_Object
     */
    public function getConfig()
    {
        if (null === $this->_config)
        {
            $this->_config = new Varien_Object(unserialize($this->getSerializedConfig()));
        }
        
        return $this->_config; 
    }
    
    /** Handles file uploading
     * 
     * @return filename
     */
    public function handleUpload($field)
    {
        if(isset($_FILES[$field]['name']) and (file_exists($_FILES[$field]['tmp_name']))) 
        {
            $uploader = new Varien_File_Uploader($field);
            $uploader->setAllowedExtensions(array('xsl'));
            $uploader->setAllowRenameFiles(false);
            $uploader->setFilesDispersion(false);
            $path = Mage::helper('aitexporter')->getTmpPath();
            $uploader->save($path, $_FILES[$field]['name']);
            return $_FILES[$field]['name'];
        }
    }

    public function validateXsl($filename)
    {
        $xp = new XSLTProcessor();
        
        // create a DOM document and load the XSL stylesheet
        $xsl = new DOMDocument();
        $path = Mage::helper('aitexporter')->getTmpPath() . $filename;
        
        if (!$xsl->load($path))
        {
            throw new Exception(Mage::helper('aitexporter')->__('Invalid xsl format or file %s has incorrect name', $path));
        }
  
        // import the XSL styelsheet into the XSLT process
        $xp->importStylesheet($xsl);
        
        // create a DOM document and load the XML data
        $xmlDoc = new DomDocument();
        $xmlDoc->loadXML('<orders />');

        // transform the XML into HTML using the XSL file
        if (!$xp->transformToXML($xmlDoc)) 
        {
            throw new Exception('XSL transformation failed'.print_r(libxml_get_last_error(), 1));
        }
    }

    public function applyXsl($path, $profile)
    {
        $xslString = $profile->getXsl();
        
        $xp = new XsltProcessor();

        // create a DOM document and load the XSL stylesheet
        $xsl = new DOMDocument();
        if (!$xsl->loadXml($xslString))
        {
            throw new Exception(Mage::helper('aitexporter')->__('Invalid xsl format'));
        }
  
        // import the XSL styelsheet into the XSLT process
        $xp->importStylesheet($xsl);
        
        // create a DOM document and load the XML data
        $xmlDoc = new DomDocument();
        $xmlDoc->load($path);
                
        //if($modifiedXml = new SimpleXMLElement($xp->transformToXML($xmlDoc)))
        if($modifiedXml = new Aitoc_Aitexporter_Model_Extendedxml($xp->transformToXML($xmlDoc)))
        {
            unlink($path);
            $modifiedXml->asXML($path);
        }
        else
        {
            throw new Exception('XSL transformation failed');
        }
    }

    /**
     * 
     * @param array $config
     * @return Aitoc_Aitexporter_Model_Export
     */
    public function setConfig(array $config)
    {
        $this->setSerializedConfig(serialize($config));
        if(!$this->getId()) {
            $this->save();
        }
        $filename  = !empty($config['file']['filename']) ? $config['file']['filename'] : 'order_export';
        $parseType = isset($config['parse']['type']) ? $config['parse']['type'] : 'xml';
        $filename .= '_'.$this->getId().'_'.date('YmdHis', Mage::getModel('core/date')->timestamp()).'.';

        switch ($parseType)
        {
            case 'csv':
                $filename .= 'csv';
                break;

            case 'xml':
            default:
                $filename .= 'xml';
                break;
        }

        $this->setFilename($filename)
             ->setDt(now());

        return $this;
    }

    public function getTmpFilePath()
    {
        $parse = $this->getConfig()->getParse();
        $type  = isset($parse['type']) ? $parse['type'] : 'xml';

        return $this->_prfxPath.'.'.$type;
    }
    
    public function prepareOrderCollection(Varien_Data_Collection_Db $collection)
    {
    	if(version_compare(Mage::getVersion(),'1.4.1.1','ge')) 
        {
        	$this->getResource()->prepareOrderCollection($this, $collection);
        }
        
        return $this;
    }

    protected function _beforeDelete()
    {
        $this->_exportTmpPath = $this->getTmpFilePath();
        
        return parent::_beforeDelete();
    }

    protected function _afterDelete()
    {
        if (file_exists($this->_exportTmpPath))
        {
            unlink($this->_exportTmpPath);
        }

        return parent::_afterDelete();
    }
    
    public function getDbTime()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');      
        $query = 'SELECT now()';
        
        return $currentTime = $readConnection->fetchOne($query);
    }
}