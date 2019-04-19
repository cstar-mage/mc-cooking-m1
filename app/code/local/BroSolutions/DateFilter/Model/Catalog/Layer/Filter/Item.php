<?php
class BroSolutions_DateFilter_Model_Catalog_Layer_Filter_Item extends Mage_Catalog_Model_Layer_Filter_Item
{
    public function getUrl()
    {
        $filter = $this->getFilter();
        if($filter instanceof BroSolutions_DateFilter_Model_Catalog_Layer_Filter_Date){
            $releaseDates = Mage::app()->getRequest()->getParam($filter->getRequestVar(), false);
            $currentVal = $this->getValue();
/*
            if ($releaseDates) {
                $queryVal = '';
                if(strpos($releaseDates, '|')){
                    $releaseDates = explode('|',$releaseDates);
                    $pos = array_search($currentVal, $releaseDates);
                    if($pos !== false){
                        unset($releaseDates[$pos]);
                    } else {
                        $releaseDates[] = $currentVal;
                    }
                    $releaseDates = array_unique($releaseDates);
                    $queryVal = implode('|', $releaseDates);

                } elseif($currentVal != $releaseDates) {
                    $queryVal = $currentVal.'|'.$releaseDates;
                }
                $query = array(
                    Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
                );
                if(!empty($queryVal)){
                    $query[$this->getFilter()->getRequestVar()] = $queryVal;
                }
            } else {
*/
                $query = array(
                    $this->getFilter()->getRequestVar()=>$this->getValue(),
                    Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
                );
//            }
            $existingQueryParams = Mage::app()->getRequest()->getParams();
            if(isset($existingQueryParams['id'])){
                unset($existingQueryParams['id']);
            }
            if(isset($existingQueryParams[$filter->getRequestVar()])){
                unset($existingQueryParams[$filter->getRequestVar()]);
            }
            /*if(isset($existingQueryParams[$filter->getMonthRequestVar()])){
                unset($existingQueryParams[$filter->getMonthRequestVar()]);
            }*/
            $resArray = array_merge($existingQueryParams, $query);
            array_unique($resArray);
            if(!empty($resArray)){
                return Mage::app()->getRequest()->getOriginalPathInfo().'?'.http_build_query($resArray);
            }
            return Mage::app()->getRequest()->getOriginalPathInfo();
        } else {
            return parent::getUrl();
        }
    }
}
