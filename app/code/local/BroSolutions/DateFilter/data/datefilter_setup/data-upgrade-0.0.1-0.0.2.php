<?php
$productCollection = Mage::getResourceModel('catalog/product_collection')->addAttributeToSelect('release_date');
foreach($productCollection as $product){
    $productReleaseDate = $product->getReleaseDate();
    $time = strtotime($productReleaseDate);
    $month = date("m",$time);
    $product->setReleaseMonth($month);
    $product->save();
}