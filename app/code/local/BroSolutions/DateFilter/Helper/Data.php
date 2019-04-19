<?php
class BroSolutions_DateFilter_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_monthsTransArr = array(
        'January'   => 'Jan',
        'February'  => 'Feb',
        'March'     => 'Mar',
        'April'     => 'Apr',
        'May'       => 'May',
        'June'      => 'Jun',
        'July'      => 'Jul',
        'August'    => 'Aug',
        'September' => 'Sep',
        'October'   => 'Oct',
        'November'  => 'Nov',
        'December'  => 'Dec',
        );
    const POSITION_KEY_TO_REMOVE_FROM_SORT_BY = 'position';

    public function getFormattedDateForProductListing(Mage_Catalog_Model_Product $_product)
    {
        $formattedStr ='';
        if($_product && $_product->getId()){
            $_resource = $_product->getResource();
            $releaseDate = $_resource->getAttributeRawValue($_product->getId(), 'release_date', Mage::app()->getStore());
            $releaseTime = $_resource->getAttributeRawValue($_product->getId(), 'release_time', Mage::app()->getStore());
            $dateTimestamp = strtotime($releaseDate);
            if ($releaseTime) {
                $formattedReleaseStartDate = date('D, F j', $dateTimestamp) . ' | ' . $releaseTime;
            } else {
                $formattedReleaseStartDate = date('D, F j', $dateTimestamp);
            }
            $formattedReleaseStartDate = strtr($formattedReleaseStartDate, $this->_monthsTransArr);
            $formattedReleaseStartDate = str_replace(':00', '', $formattedReleaseStartDate);
            return $formattedReleaseStartDate;
        }
        $formattedStr = str_replace(':00', '', $formattedStr);
        return $formattedStr;
    }

    public function getKeyToRemoveFronSort()
    {
        return self::POSITION_KEY_TO_REMOVE_FROM_SORT_BY;
    }

    public function getProductStock($product = NULL)
    {
        $qty = '';
        if(!$product){
            $product = Mage::registry('current_product');
        }
        if(($product instanceof Mage_Catalog_Model_Product) && $product->getId()){
            $qty = (int) Mage::getModel('cataloginventory/stock_item')
                ->loadByProduct($product)->getQty();
        }
        return $qty;
    }

    public function checkDateCondition($product)
    {
        if(!$product){
            $product = Mage::registry('current_product');
        }
        $productType = $product->getTypeID();
        if($productType == 'giftcards'){
            return true;
        }
        $releaseDate = new DateTime($product->getReleaseDate());
        $now = new DateTime();
        $releaseDateTimestamp = floatval($releaseDate->getTimestamp());
        $nowTimestamp = floatval($now->getTimestamp());
        $timestampDifferrence = $nowTimestamp - $releaseDateTimestamp;
        $daysDifferrence = floor($timestampDifferrence/(60*60*24));
        if($daysDifferrence >= 1){
            return false;
        }
        return true;
    }
    public function isReleaseDateToday($product)
    {
        $releaseDate = $product->getReleaseDate();
        $releaseDate = date('Y-d-m', strtotime($releaseDate));
        $today = date("Y-d-m");
        if($releaseDate == $today){
            return true;
        }
        return false;
    }
}
