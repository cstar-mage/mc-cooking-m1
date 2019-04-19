<?php
class BroSolutions_All_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_FOR_GLOBAL_CROSS_SELLS = 'globcrosssells_selection/globcrosssells_group/globcrosssells_ids';
    const XML_APTH_FOR_API_RULE_ID = 'cartrulefix_selection/cartrulefix_group/cartrulefix_rule_id';
    const XML_PATH_FOR_API_USER_IS_MEMBER_URL = 'cartrulefix_selection/cartrulefix_group/cartrulefix_is_member_url';
    const CLONING_PRODUCT_SKU_PREFIX = 'sku_';
    const CLONING_PRODUCT_CLASS_DATE_PREFIX = 'date_';
    protected $_crossSellIds = NULL;
    protected $_productsSkusForClone = array('PE-GROUPTEMPLATE'/*, 'PE-DEPOSIT-TEMPLATE', 'PE-SECOND-NOTAX', 'PE-SECOND-TAX'*/);
    protected $_clonedProductToGroupProductRelations = array('PE-GROUPTEMPLATE' => 'Private Event', 'PE-DEPOSIT-TEMPLATE' => 'Private Event Deposit', 'PE-SECOND-NOTAX' => 'Private Event (non-taxable)',
        'PE-SECOND-TAX' => 'Private Event (tax)');

    public function getGlobalCrossSellIds()
    {
        if(!$this->_crossSellIds){
            $crossSellIds = Mage::getStoreConfig(self::XML_PATH_FOR_GLOBAL_CROSS_SELLS);
            $this->_crossSellIds = explode(',', $crossSellIds);
            $crossSellsCollection = Mage::getResourceModel('catalog/product_collection')->AddFieldToFilter('entity_id', array('in' => array($this->_crossSellIds)));
            foreach($crossSellsCollection as $crossSellProduct){
                $crossSellProductType = $crossSellProduct->getTypeId();
                if($crossSellProductType == Mage_Catalog_Model_Product_Type::TYPE_GROUPED){
                    $associatedProducts = $crossSellProduct->getTypeInstance(true)->getAssociatedProducts($crossSellProduct);
                    foreach($associatedProducts as $associatedProduct){
                        $this->_crossSellIds [] = $associatedProduct->getId();
                    }
                }
            }
        }
        return $this->_crossSellIds;
    }

    public function checkIfNeedApplyRule(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $crossSellIds = $this->getGlobalCrossSellIds();
        $productId = $item->getProductId();
        if(in_array($productId, $crossSellIds) !== false){
            return false;
        }
        return true;
    }

    public function isCustomerMember()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $email = $customer->getEmail();
            $canAppllyRule = $this->_getCanApplyRule($email);
            return $canAppllyRule;
        }
        return false;
    }

    public function checkRuleIdForMembers($id)
    {
        $ruleId = Mage::getStoreConfig(self::XML_APTH_FOR_API_RULE_ID);
        return ($ruleId == $id);
    }

    protected function _getCanApplyRule($email)
    {

        $apiUrl = trim(Mage::getStoreConfig(self::XML_PATH_FOR_API_USER_IS_MEMBER_URL)).$email;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        if(!empty($output)){
            $result = json_decode($output, true);
            if(isset($result['status']) && $result['status'] == 'success'){
                if(isset($result['data']) && isset($result['data']['is_member']) && $result['data']['is_member'] === true){
                    return true;
                }
            }
        }
        return false;
    }

    public function copyCategoriesAndStock($sourceProduct, $destProduc)
    {
        $sourceCategoryIds = $sourceProduct->getCategoryIds();
        $sourceStockItem = $sourceProduct->getStockItem();
        $necessarryStockDataKeys = array('use_config_manage_stock', 'manage_stock', 'min_sale_qty', 'max_sale_qty', 'is_in_stock', 'qty');
        $stockData = array();
        foreach($sourceStockItem as $key => $val){
            if(array_search($key, $necessarryStockDataKeys) !== false){
                $stockData[$key] = $val;
            }
        }
        $stockData['use_config_manage_stock'] = false;
        $destProduc->setStockData($stockData);
        $destProduc->setCategoryIds($sourceCategoryIds);
    }


    public function initProducts()
    {
        $productCollection = Mage::getResourceModel('catalog/product_collection')->addAttributeToSelect('*')->addAttributeToSort('type_id', 'ASC');
        $productCollection->addAttributeToFilter('sku', array('in' => $this->_productsSkusForClone));
        Mage::getModel('cataloginventory/stock')->addItemsToProducts($productCollection);
        $this->_appendChildProducts($productCollection);
        if(!Mage::registry('products_for_clone')){
            Mage::register('products_for_clone', $productCollection);
        }
    }

    protected function _appendChildProducts($productCollection)
    {
        $productCollection->load();
        $groupedProductId = false;
        foreach($productCollection as $product){
            if($product->getTypeId() == 'grouped'){
                $groupedProductId = $product->getId();
                break;
            }
        }
        $groupedProduct = Mage::getModel('catalog/product')->load($groupedProductId);
        $associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($groupedProduct);
        foreach($associatedProducts as $associatedProduct){
            $associatedProduct->setParentGroupedProductSku($groupedProduct->getSku());
            $productCollection->addItem($associatedProduct);
        }
    }

    public function cloneProduct($sourceProductSku, $destinationProductData)
    {
        if(isset($destinationProductData['sku'])){
            $sourceProduct = Mage::getModel('catalog/product');
            $sourceProductId = $sourceProduct->getIdBySku($sourceProductSku);
            $sourceProduct->load($sourceProductId);
            if ($sourceProduct->getId()) {
                $newProduct = $sourceProduct->duplicate();
                if($newProduct->getId()){
                    $newProduct = Mage::getModel('catalog/product')->load($newProduct->getId());
                    $sourceProductData = $sourceProduct->getData();
                    /*foreach($sourceProductData as $key => $value){
                        $newProduct->setData($key, $value);
                    }*/
                    $newProduct->setSku($destinationProductData['sku']);
                    if(isset($this->_clonedProductToGroupProductRelations[$sourceProductSku])){
                        $newProduct->setName($this->_clonedProductToGroupProductRelations[$sourceProductSku]);
                    }
                    if(isset($destinationProductData['release_date'])){
                        $newProduct->setData('release_date', $destinationProductData['release_date']);
                    }
                    $this->copyCategoriesAndStock($sourceProduct, $newProduct);
                    if(isset($sourceProductData['status'])){
                        $newProduct->setStatus($sourceProductData['status']);
                    }
                    if(isset($sourceProduct['is_salable'])){
                        $newProduct->setData('is_salable', $sourceProductData['status']);
                    }
                    if(isset($destinationProductData['assotiations']) && !empty($destinationProductData['assotiations'])){
                        $newProduct->setParentGroupedProductSku($destinationProductData['assotiations']);
                    }
                    return $newProduct;
                }
            }
        }
        return false;
    }

    public function associateGroupedChildren($newProduct)
    {
        $parentSku = $newProduct->getParentGroupedProductSku();
        if($parentSku){
            $parentGroupedSku = $this->_getParentClonedGroupedSkuFromRequest($parentSku);
            $this->attachChildrenToGrouped($parentGroupedSku, $newProduct->getSku());
        }
    }

    protected function _getParentClonedGroupedSkuFromRequest($parentSku)
    {
        $requestParams = Mage::app()->getRequest()->getParams();
        if(isset($requestParams['cloned_product']) && isset($requestParams['cloned_product'][$parentSku]) && isset($requestParams['cloned_product'][$parentSku]['sku'])){
            return $requestParams['cloned_product'][$parentSku]['sku'];
        }
        return false;
    }

    public function attachChildrenToGrouped($parendSku, $childSku)
    {
        $parentProduct = Mage::getModel('catalog/product');
        $parentProductId = $parentProduct->getIdBySku($parendSku);
        $childProduct = Mage::getModel('catalog/product');
        $childProductId = $childProduct->getIdBySku($childSku);
        if($parentProductId && $childProductId){
            $parentProduct = $parentProduct->load($parentProductId);
            $childProduct = $childProduct->load($childProductId);
            $productsLinks = Mage::getModel('catalog/product_link_api');
            $productsLinks->assign("grouped", $parentProduct->getId(), $childProduct->getId());
        }
    }
}