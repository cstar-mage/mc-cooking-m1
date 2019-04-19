<?php
//445, 447, 418 - cross sell products ids
//Check if products exists
$generalCrossSellsIdsArr = array(445, 418, 447);
$generalCrossSells = Mage::getResourceModel('catalog/product_collection')->addFieldToFilter('entity_id', array('in' => $generalCrossSellsIdsArr));
$canAddProducts = true;
foreach($generalCrossSells as $generalCrossSell){
    if(!$generalCrossSell->getId()){
        $canAddProducts = false;
        break;
    }
}
//Add cross sells
$allProductsCollection = Mage::getResourceModel('catalog/product_collection');
if($canAddProducts){
    foreach($allProductsCollection as $product){
        $productId = $product->getId();
        if(!in_array($productId, $generalCrossSellsIdsArr)){
            $positionInc = 0;
            $crossSellsNormalizedArr = array();
            foreach($generalCrossSellsIdsArr as $generalCrossSellId){
                $crossSellsNormalizedArr[$generalCrossSellId] = array('position' => $positionInc);
                $positionInc++;
            }
            if(!empty($crossSellsNormalizedArr)){
                $product->setCrossSellLinkData($crossSellsNormalizedArr);
                $product->save();
            }
        }
    }
}
