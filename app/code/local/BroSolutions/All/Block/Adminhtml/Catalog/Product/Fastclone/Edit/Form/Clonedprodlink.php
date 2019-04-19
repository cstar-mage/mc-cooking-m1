<?php
class BroSolutions_All_Block_Adminhtml_Catalog_Product_Fastclone_Edit_Form_Clonedprodlink extends Varien_Data_Form_Element_Abstract
{
    public function getElementHtml()
    {
        $createdProduct = $this->getCreatedProduct();
        $html = '';
        if($createdProduct->getId()){
            $html = '<a href="'.Mage::helper("adminhtml")->getUrl('*/*/edit/', array('id' => $createdProduct->getId())).'" target="_blank">'.$createdProduct->getName().'</a>';
        }
        return $html;
    }

    public function getCreatedProduct()
    {
        return Mage::registry('last_added_product');
    }
}