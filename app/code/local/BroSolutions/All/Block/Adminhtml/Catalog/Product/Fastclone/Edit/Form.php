<?php
class BroSolutions_All_Block_Adminhtml_Catalog_Product_Fastclone_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getUrl('*/*/saveclone'), 'method' => 'post', 'enctype' => 'multipart/form-data'));
        $fieldset = $form->addFieldset('main', array('legend' => 'Cloning products configuration'));
        $form->setUseContainer(true);
        $this->setForm($form);
        $this->_addElementTypes($fieldset);
        $productsForCloneCollection = Mage::registry('products_for_clone');
        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );
        $groupedToSimpleAssotiation = array();
        foreach($productsForCloneCollection as $product){
            $productTypeId = $product->getTypeId();
            $parentProductSku = $product->getParentGroupedProductSku();
            $parentProductSkuHtml = '';
            if($parentProductSku){
                if(!isset($groupedToSimpleAssotiation[$parentProductSku])){
                    $groupedToSimpleAssotiation[$parentProductSku] = array();
                }
                $groupedToSimpleAssotiation[$parentProductSku][] = $product->getSku();
                //$parentProductSkuHtml = '</br> Parent product SKU: '.$parentProductSku;
            }
            $fieldset->addField(BroSolutions_All_Helper_Data::CLONING_PRODUCT_SKU_PREFIX.$product->getSku(), 'text', array(
                'label'     => Mage::helper('core')->__(str_replace(array('Template', 'Private Event - '), '', $product->getName()) . ' SKU'),
                'name'      => 'cloned_product['.$product->getSku().'][sku]',
                'value'  => $product->getSku() . '_clone',
                'required' => true,
                'after_element_html' => '',//</br>Product\'s SKU: '.$product->getSku(),
            ));
            if($productTypeId == 'grouped'){
                $fieldset->addField(BroSolutions_All_Helper_Data::CLONING_PRODUCT_CLASS_DATE_PREFIX.$product->getSku(), 'date', array(
                    'label'     => Mage::helper('core')->__('Class date'),
                    'name'      => 'cloned_product['.$product->getSku().'][release_date]',
                    'image'    => $this->getSkinUrl('images/grid-cal.gif'),
                    'value'  => $product->getReleaseDate(),
                    'format'   => $dateFormatIso,
                ));
            }
            $fieldset->addField('grouped_product_assotiations'.$product->getSku(), 'hidden', array(
                'required'  => true,
                'name'      => 'cloned_product['.$product->getSku().'][assotiations]',
                'value'  => $parentProductSku,
            ));
        }

        return parent::_prepareForm();
    }
}
