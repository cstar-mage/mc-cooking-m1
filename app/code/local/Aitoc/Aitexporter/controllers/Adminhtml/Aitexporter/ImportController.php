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
class Aitoc_Aitexporter_Adminhtml_Aitexporter_ImportController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/convert/aitexporter_import')
            ->_addBreadcrumb(Mage::helper('aitexporter')->__('Aitoc Sales Import'), Mage::helper('aitexporter')->__('Aitoc Sales Import'));

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction();

        $this->_addContent($this->getLayout()->createBlock('aitexporter/import_edit'))
             ->_addLeft($this->getLayout()->createBlock('aitexporter/import_edit_tabs'));

        $this->renderLayout();
    }

    public function saveAction()
    {
        if ($this->getRequest()->getPost()) 
        {
            $postData   = $this->getRequest()->getPost();
            $config     = Mage::getSingleton('aitexporter/config')->saveImportConfig($postData)->getImportConfig();
            $extensions = array('xml');
            if (isset($config['parse']['type']) && 'csv' == $config['parse']['type'])
            {
                $extensions = array('csv');
            }

            if (isset($_FILES['import_file']['name']) && file_exists($_FILES['import_file']['tmp_name'])) 
            {
                try 
                {
                    $processor = Mage::getSingleton('aitexporter/processor');
                    
                    if(!$processor->getProcess())
                    {
                        $uploader = new Varien_File_Uploader('import_file');
    
                        $uploader->setAllowedExtensions($extensions);
                        $uploader->setAllowRenameFiles(true);
    
                        $path = Mage::helper('aitexporter')->getTmpPath();
    
                        if (!is_dir($path))
                        {
                            mkdir($path);
                        }
    
                        $uploadFileParts = explode('.', $_FILES['import_file']['name']);
                        $tmpName         = 'tmp'.md5(uniqid(mt_rand())).'.'.end($uploadFileParts);
    
                        $uploader->save($path, $tmpName);
    
                        $import = Mage::getModel('aitexporter/import')
                            ->setFilename($_FILES['import_file']['name'])
                            ->setStatus(Aitoc_Aitexporter_Model_Import::STATUS_PENDING)
                            ->setSerializedConfig(serialize($config))
                            ->setDt(now())
                            ->save();
    
                        rename($path.$tmpName, $path.Aitoc_Aitexporter_Model_Import::FILE_PREFIX.$import->getId().'.'.current($extensions));
    
                        $processor->setProcess('import::initValidation', array('id' => $import->getId()))->save();
                    
                        return $this->_redirect('*/*/', array('id' => $import->getId(), 'active_tab' => 'processor'));
                    }
                    else 
                    {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('aitexporter')->__('Sorry, another export or import process is already running. You can stop it at the \'Processor\' tab.'));
                    }
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
        }

        $this->_redirect('*/*/index');
    }

    public function cancelAction()
    {
        $config = Mage::getSingleton('aitexporter/processor_config');
        $config->resetExport();
		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('aitexporter')->__('Import process has been canceled'));
        return $this->_redirect('*/*/');
    
    }
    
    public function importAction()
    {
        $importId      = $this->getRequest()->getParam('id');
        
        $processor = Mage::getSingleton('aitexporter/processor');
        if(!$processor->getProcess())
        {
            $processor->setProcess('import::initImport', array('id' => $importId))->save();
            $this->_redirect('*/*/', array('id' => $importId, 'active_tab' => 'processor'));
        }
        else
        {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('aitexporter')->__('Sorry, another export or import process is already running. You can stop it at the \'Processor\' tab.'));
            $this->_redirect('*/*/viewLog', array('id' => $importId));
        }
    }

    /** History AJAX navigation
     */
    public function historyAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('aitexporter/import_edit_tab_history')->toHtml());
    }

    /** View Import log AJAX navigation
     */
    public function viewLogGridAction()
    {
        $importId      = $this->getRequest()->getParam('id');
        $currentImport = Mage::getModel('aitexporter/import')->load($importId);

        if (!$currentImport->getId())
        {
            return $this->_redirect('*/*/index');
        }

        Mage::register('current_import', $currentImport);

        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('aitexporter/import_log_form')->toHtml());
    }

    public function viewLogAction()
    {
        $importId      = $this->getRequest()->getParam('id');
        $currentImport = Mage::getModel('aitexporter/import')->load($importId);

        if (!$currentImport->getId())
        {
            return $this->_redirect('*/*/index');
        }

        Mage::register('current_import', $currentImport);

        $session = Mage::getSingleton('adminhtml/session');
        /* @var $session Mage_Adminhtml_Model_Session */ 

        switch($currentImport->getBehavior())
        {
            case Aitoc_Aitexporter_Model_Import::BEHAVIOR_REMOVE:
                if (Aitoc_Aitexporter_Model_Import::STATUS_COMPLETE != $currentImport->getStatus())
                {
                    $session->addError(Mage::helper('aitexporter')->__('By clicking "Import" button you are going to remove specified orders.'));
                }
                break;
        }

        switch ($currentImport->getStatus())
        {
            case Aitoc_Aitexporter_Model_Import::STATUS_WARNINGS:
                $session->addError(Mage::helper('aitexporter')->__('The file is valid but there are some minor errors. Some data is incorrect. You can either review your file and correct data or ignore the errors and proceed with importing.'));
                break;

            case Aitoc_Aitexporter_Model_Import::STATUS_VALID:
                $session->addSuccess(Mage::helper('aitexporter')->__('Import file values are valid. Please click "Import" to import data'));
                break;

            case Aitoc_Aitexporter_Model_Import::STATUS_COMPLETE:
                switch ($currentImport->getBehavior())
                {
                    case Aitoc_Aitexporter_Model_Import::BEHAVIOR_APPEND:
                        $session->addNotice(Mage::helper('aitexporter')->__(
                        	'Orders Added: %s, Orders Ignored: %s, Orders Failed to Import: %s', 
                            $currentImport->getAddCount(), 
                            $currentImport->getUpdateCount(), 
                            $currentImport->getFailCount() 
                            ));
                        break;

                    case Aitoc_Aitexporter_Model_Import::BEHAVIOR_REPLACE:
                        $session->addNotice(Mage::helper('aitexporter')->__(
                        	'Orders Added: %s, Orders Updated: %s, Orders Failed to Import: %s', 
                            $currentImport->getAddCount(), 
                            $currentImport->getUpdateCount(), 
                            $currentImport->getFailCount() 
                            ));
                        break;

                    case Aitoc_Aitexporter_Model_Import::BEHAVIOR_REMOVE:
                        $session->addNotice(Mage::helper('aitexporter')->__(
                        	'Orders Removed: %s, Orders Failed to Remove: %s', 
                            $currentImport->getUpdateCount(), 
                            $currentImport->getFailCount() 
                            ));
                        break;
                }

                break;

            case Aitoc_Aitexporter_Model_Import::STATUS_ERRORS:
                $session->addError(Mage::helper('aitexporter')->__('There are some critical errors in the file, and it cannot be imported. Please review your file and correct data.'));
                break;
        }

        $this->_initAction();

        $this->_addContent($this->getLayout()->createBlock('aitexporter/import_log'));

        $this->renderLayout();
    }

    public function deleteAction()
    {
        $importId      = $this->getRequest()->getParam('id');
        $currentImport = Mage::getModel('aitexporter/import')->load($importId);

        if (!$currentImport->getId())
        {
            return $this->_redirect('*/*/index');
        }

        $currentImport->delete();

        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('aitexporter')->__('Import information has been deleted successfully.'));

        return $this->_redirect('*/*/index', array('active_tab' => 'history_section'));
    }

    public function massDeleteAction()
    {
        $importIds = $this->getRequest()->getParam('import_ids');
        if(!is_array($importIds)) 
        {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('aitexporter')->__('Please select item(s)'));
        }
        else 
        {
            try 
            {
                foreach ($importIds as $importId) 
                {
                    Mage::getModel('aitexporter/import')->load($importId)->delete();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('aitexporter')->__('Total of %d record(s) were successfully deleted', count($importIds)));
            } 
            catch (Exception $e) 
            {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index', array('active_tab' => 'history_section'));
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/convert/aitexporter_import');
    }
}