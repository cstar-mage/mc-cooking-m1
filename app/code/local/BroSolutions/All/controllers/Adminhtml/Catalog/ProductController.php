<?php
require_once(Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Catalog'.DS.'ProductController.php');
class BroSolutions_All_Adminhtml_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController
{
    public function fastcloneAction()
    {
        $this->loadLayout();
        $this->_helper()->initProducts();
        $this->renderLayout();
    }

    public function savecloneAction()
    {
        $requestParams = $this->getRequest()->getParams();
        $requestParams = array_reverse($requestParams);
        $newProducts = array();
        if(isset($requestParams['cloned_product'])){
            foreach($requestParams['cloned_product'] as $sourceProductSku => $cloneData){
                $newProduct = $this->_helper()->cloneProduct($sourceProductSku, $cloneData);
                if($newProduct){
                    $newProducts[] = $newProduct;
                }
            }
        }
        if(!empty($newProducts)){
            $groupedProduct = false;
            $simpleChildren = array();
            foreach($newProducts as $newProduct){
                if($newProduct->getTypeId() == 'grouped'){
                    $groupedProduct = $newProduct;
                } elseif($newProduct->getTypeId() == 'virtual'){
                    $simpleChildren[] = $newProduct;
                }
                $newProduct->save();
            }
            if($groupedProduct && !empty($simpleChildren)){
                $productsLinks = Mage::getModel('catalog/product_link_api');
                $items = $productsLinks->items("grouped", $groupedProduct->getId());
                //var_dump($items); die;
                foreach($items as $item){
                    if(isset($item['product_id'])){
                        $productsLinks->remove("grouped", $groupedProduct->getId(), $item['product_id']);
                    }
                }
                foreach($simpleChildren as $simpleChild){
                    $productsLinks->assign("grouped", $groupedProduct->getId(), $simpleChild->getId());
                }
            }
        }

        $this->_redirect('adminhtml/catalog_product/index');
        return $this;
    }

    protected function _helper()
    {
        return Mage::helper('brosolutionsall');
    }
}