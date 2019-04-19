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
class Aitoc_Aitexporter_Model_Import extends Mage_Catalog_Model_Abstract
{
    const FILE_PREFIX       = 'import_';

    const STATUS_PENDING    = 'pending';
    const STATUS_ERRORS     = 'errors';
    const STATUS_WARNINGS   = 'warnings';
    const STATUS_VALID      = 'valid';
    const STATUS_COMPLETE   = 'complete';

    const BEHAVIOR_APPEND   = 'append';
    const BEHAVIOR_REPLACE  = 'replace';
    const BEHAVIOR_REMOVE   = 'remove';

    /**
     * @var Varien_Object
     */
    private $_config;
    private $_tmpPaths = array();

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

        $this->_init('aitexporter/import');
    }

    /** Gets import config as an object
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

    public function getStatuses()
    {
        return array(
            self::STATUS_PENDING    => Mage::helper('aitexporter')->__('Not Validated'),
            self::STATUS_ERRORS     => Mage::helper('aitexporter')->__('Critical Errors'),
            self::STATUS_WARNINGS   => Mage::helper('aitexporter')->__('Minor Errors'),
            self::STATUS_VALID      => Mage::helper('aitexporter')->__('Valid Data'),
            self::STATUS_COMPLETE   => Mage::helper('aitexporter')->__('Imported'),
         );
    }

    protected function _initIteration($options)
    {
        $this->load($options['id']);
        if(!Mage::registry('current_import'))
        {
            Mage::register('current_import', $this);
        }
        $this->_response        = Mage::getSingleton('aitexporter/processor_response');
        $this->_processorConfig = Mage::getSingleton('aitexporter/processor_config');
        $this->_prfxPath        = Mage::helper('aitexporter')->getTmpPath().'import_'.$this->getId();
    }

    public function initValidation($options)
    {
        $this->_initIteration($options);

        if (self::STATUS_PENDING != $this->getStatus())
        {
            $this->_response->addMessage('notice', 'This import is already validated');
            $this->_response->setSwitch();
            return;
        }

        $parse = $this->getConfig()->getParse();
        if (isset($parse['type']) && ($parse['type'] == 'csv'))
        {
            $this->_response->setSwitch('import::initConvert', array('id' => $this->getId()));
        }
        else
        {
            $this->_response->setSwitch('import::reinitValidation', array('id' => $this->getId()));
        }
    }

    public function initConvert($options)
    {
        $this->_initIteration($options);

        $xmlPath = $this->_prfxPath.'.xml';
        $xml = Mage::getModel('aitexporter/file_xml', $xmlPath);
        $xml->write('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<orders>');

        $options = array(
                'id' => $this->getId(),
                'from'  => 0,
                'limit' => 25,
                'entities' => filesize($this->_prfxPath.'.csv'),
        );
        $this->_response->setSwitch('import::makeConvert', $options);
    }

    public function makeConvert($options)
    {
        $this->_initIteration($options);

        $xml        = Mage::getModel('aitexporter/file_xml', $this->_prfxPath.'.xml');
        $csv        = Mage::getModel('aitexporter/file_csv', $this->_prfxPath.'.csv');
        $converter  = Mage::getSingleton('aitexporter/converter');

        $config = $this->getConfig();
        $parse = $config->getParse();
        $csv->setDelimiter(isset($parse['delimiter']) ? $parse['delimiter'] : Aitoc_Aitexporter_Model_File_Csv::DEFAULT_DELIMITER)
              ->setEnclose(isset($parse['enclose'])   ? $parse['enclose']   : Aitoc_Aitexporter_Model_File_Csv::DEFAULT_ENCLOSE);

        $isCheckStore = (bool)$config->getStore();
        $headers = $csv->getHeaders();

        try
        {
            $from = $options['from'];
            for ($i = 0; $i < $options['limit']; $i++)
            {
                if($order = $csv->read($from))
                {
                    $addCustomers = $config->getData('create_customers');
                    $converter->csv2xml($order, $headers, $xml, $isCheckStore, $addCustomers);
                    $from = $csv->getOffset();
                    $this->_processorConfig->updateOption('from', $from);
                }else{
                    $xml->write('</orders>');
                    $this->_response->setSwitch('import::reinitValidation', array('id' => $this->getId()));
                    return;
                }
            }
        }
        catch (Exception $e)
        {
            $this->setError($e);
            return;
        }
    }

    public function setError($e, $errorType = Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
    {
        $message = is_object($e) ? $e->getMessage() : (string)$e;
        Mage::getModel('aitexporter/import_error')
            ->setType($errorType)
            ->setImportId($this->getId())
            ->setError($message)
            ->save();

        $this->setStatus(self::STATUS_ERRORS)->save();
        $this->_response->setSwitch();
    }

    public function reinitValidation($options)
    {
        $this->_initIteration($options);

        $xmlPath = $this->_prfxPath.'.xml';
        $xml = Mage::getModel('aitexporter/file_xml', $xmlPath);

        try {
            if(!$xml->read())
                {
                    $this->setError(Mage::helper('aitexporter')->__('File does not have orders'));
                    return;
                }
        } catch(Exception $e) {
            $this->setError($e);
            return;
        }

        $options = array(
            'id'       => $this->getId(),
            'from'     => 0,
            'limit'    => 25,
            'status'   => self::STATUS_VALID,
            'entities' => filesize($this->_prfxPath.'.xml'),
        );
        $this->_response->setSwitch('import::makeValidation', $options);
    }

    public function makeValidation($options)
    {
        $this->_initIteration($options);

        $importOrder = Mage::getModel('aitexporter/import_type_order');
        $config = $this->getConfig();

        $xmlPath = $this->_prfxPath.'.xml';
        $xml = Mage::getModel('aitexporter/file_xml', $xmlPath);

        $from = $options['from'];
        for ($i = 0; $i < $options['limit']; $i++)
        {
            if($orderXml = $xml->read($from))
            {
                if(!$importOrder->validate($orderXml, $importOrder->getXpath(), (array)$config->getData()))
                {
                    $this->_processorConfig->updateOption('status', self::STATUS_WARNINGS);
                }
                $from = $xml->getOffset();
                $this->_processorConfig->updateOption('from', $from);
            }
            else
            {
                $this->setStatus(self::STATUS_VALID);
                if($this->_processorConfig->getOption('status') != self::STATUS_VALID)
                {
                    $this->setStatus(self::STATUS_WARNINGS);
                }
                $this->save();
                $this->_response->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml/aitexporter_import/viewLog', array('id' => $this->getId())));
                $this->_response->setSwitch();
                return;
            }
        }
    }

    public function initImport($options)
    {
        $this->_initIteration($options);

        if (!in_array($this->getStatus(), array(self::STATUS_WARNINGS, self::STATUS_VALID)))
        {
            $this->_response->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml/aitexporter_import/viewLog', array('id' => $this->getId())));
            $this->_response->setSwitch();
            return;
        }

        $options = array(
            'id'       => $this->getId(),
            'from'     => 0,
            'limit'    => 25,
            'entities' => filesize($this->_prfxPath.'.xml'),
        );
        $this->_response->setSwitch('import::makeImport', $options);
    }

    public function makeImport($options)
    {
        $this->_initIteration($options);

        $importOrder = Mage::getModel('aitexporter/import_type_order');
        $config = $this->getConfig();

        $xmlPath = $this->_prfxPath.'.xml';
        $xml = Mage::getModel('aitexporter/file_xml', $xmlPath);

        $from = $options['from'];
        for ($i = 0; $i < $options['limit']; $i++)
        {
            if($orderXml = $xml->read($from))
            {
                $state = $importOrder->import($orderXml, $importOrder->getXpath(), (array)$config->getData());

                switch ($state)
                {
                    case Aitoc_Aitexporter_Model_Import_Type_OrderImport::STATE_ADD:
                        $this->setAddCount($this->getAddCount()+1);
                        break;

                    case Aitoc_Aitexporter_Model_Import_Type_OrderImport::STATE_UPDATE:
                        $this->setUpdateCount($this->getUpdateCount()+1);
                        break;

                    case Aitoc_Aitexporter_Model_Import_Type_OrderImport::STATE_FAIL:
                        $this->setFailCount($this->getFailCount()+1);
                        break;
                }
                $this->save();

                $from = $xml->getOffset();
                $this->_processorConfig->updateOption('from', $from);
            }
            else
            {
                $this->setStatus(self::STATUS_COMPLETE)
                     ->save();
                $this->_response->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml/aitexporter_import/viewLog', array('id' => $this->getId())));
                $this->_response->setSwitch();
                return;
            }
        }
    }

    public function getTmpFilePath($type = null)
    {
        $config    = unserialize($this->getSerializedConfig());
        $parseType = isset($config['parse']['type']) ? $config['parse']['type'] : 'xml';
        if (empty($type))
        {
            $type = $parseType;
        }

        return Mage::helper('aitexporter')->getTmpPath().self::FILE_PREFIX.$this->getId().'.'.$type;
    }

    public function getBehavior()
    {
        $config   = unserialize($this->getSerializedConfig());
        $behavior = isset($config['behavior']) ? $config['behavior'] : 'append';

        return $behavior;
    }

    protected function _beforeDelete()
    {
        $this->_tmpPaths = array(
            $this->getTmpFilePath('xml'),
            $this->getTmpFilePath('csv')
            );

        return parent::_beforeDelete();
    }

    protected function _afterDelete()
    {
        foreach ($this->_tmpPaths as $tmpPath)
        {
            if (file_exists($tmpPath))
            {
                unlink($tmpPath);
            }
        }

        return parent::_afterDelete();
    }
}