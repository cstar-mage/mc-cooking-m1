<?php
/**
 * Webtex
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.webtexsoftware.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@webtexsoftware.com and we will send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to http://www.webtexsoftware.com for more information, 
 * or contact us through this email: info@webtexsoftware.com.
 *
 * @category   Webtex
 * @package    Webtex_Core
 * @copyright  Copyright (c) 2011 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */

class Webtex_Core_Block_System_Config_Form_Fieldset_Modules
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset_Modules_DisableOutput
{
    private $_adapter = null;
    private $_tablePrefix = '';
    
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $this->_adapter = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        $html = $this->_getHeaderHtml($element);

        $modules = Mage::helper('mgxcore')->getModuleList();
        sort($modules);
        $html.= $this->_getHeadFieldHtml($element);
        foreach ($modules as $module) {
            $html.= $this->_getFieldHtml($element, $module);
        }
        $html .= $this->_getFooterHtml($element);

        return $html;
    }
    
    protected function _getHeadFieldHtml($fieldset)
    {
        $html  = '<tr>';
        $html .= '<td class="label"><label><strong>'.$this->__('Module Name').'</strong></label></td>';
        $html .= '<td class="value"><strong>'.$this->__('Version / Data Version').'</strong></td>';
        $html .= '</tr>';
        return $html;
    }
    
    protected function _getFieldHtml($fieldset, $module)
    {
        $dataArray = explode('_', $module->id);
        $dataName = strtolower($dataArray[1]).'_setup';
        $query = 'select * from ' . $this->_tablePrefix .'core_resource where code like "'.$dataName.'"' ;
        $res = $this->_adapter->fetchRow($query);

        $field = $fieldset->addField((string)$module->id, 'label',
            array(
                'name'          => $module->id,
                'label'         => $module->name ? $module->name : $module->id,
                'value'         => $module->version . ' / ' . $res['data_version'],
            ))->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }
}