<?php
class BroSolutions_DateFilter_Block_Catalog_Layer_Filter_Date
    extends Mage_Catalog_Block_Layer_Filter_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'datefilter/catalog_layer_filter_date';
        $this->setTemplate('catalog/layer/date_filter.phtml');
    }

    public function getCalendarBlock()
    {
        return $this->getLayout()->createBlock('datefilter/catalog_layer_filter_calendar')->setTemplate('page/js/calendar.phtml');
    }

    public function getAvailableDatesStr()
    {
        $availableDates = Mage::getModel($this->_filterModelName)->getAllAvailableDates();
        $dateFormattedForDatepicker = array();
        foreach($availableDates as $date => $count){
            $timeStamp = strtotime($date);
            $dateFormattedForDatepicker [] = date('Y-m-d', $timeStamp);
        }
        return json_encode($dateFormattedForDatepicker);
    }

    public function getClassDatesStr()
    {
        $availableDates = Mage::getModel($this->_filterModelName)->getAllAvailableClassDates();
        $dateFormattedForDatepicker = array();
        foreach($availableDates as $date => $count){
            $timeStamp = strtotime($date);
            $dateFormattedForDatepicker [] = date('Y-m-d', $timeStamp);
        }
        return json_encode($dateFormattedForDatepicker);
    }

    public function getAvailablePrivateClassesDatesStr()
    {
        $availableDates = Mage::getModel($this->_filterModelName)->getPrivateClassesDates();
        $dateFormattedForDatepicker = array();
        foreach($availableDates as $date => $count){
            $timeStamp = strtotime($date);
            $dateFormattedForDatepicker [] = date('Y-m-d', $timeStamp);
        }
        return json_encode($dateFormattedForDatepicker);
    }

    public function getAvailableDatesInPastStr()
    {
        $availableDates = Mage::getModel($this->_filterModelName)->getAllAvailableDatesInPast();
        $dateFormattedForDatepicker = array();
        foreach($availableDates as $date => $count){
            $timeStamp = strtotime($date);
            $dateFormattedForDatepicker [] = date('Y-m-d', $timeStamp);
        }
        return json_encode($dateFormattedForDatepicker);
    }

    public function getSoldOutClassesDates()
    {
        $availableDates = Mage::getModel($this->_filterModelName)->getSoldOutClassesDates();
        $dateFormattedForDatepicker = array();
        foreach($availableDates as $date => $count){
            $timeStamp = strtotime($date);
            $dateFormattedForDatepicker [] = date('Y-m-d', $timeStamp);
        }
        return json_encode($dateFormattedForDatepicker);
    }

    public function getFormattedDateValue($item)
    {
        $value = $item->getValue();
        $timeStamp = strtotime($value);
        return date('Y-m-d', $timeStamp);
    }

    public function isFilterApplied()
    {
        return json_encode(Mage::registry('date_filter'));
    }

    public function getAppliedDate()
    {
        $appliedDate = Mage::getModel($this->_filterModelName)->getAppliedFilterDate();
        if($appliedDate){
            $timeStamp = strtotime($appliedDate);
            $appliedDateStr = date('Y/m/d', $timeStamp);
            return json_encode($appliedDateStr);
        }
        return '';
    }

    public function getAvailableMonths()
    {
        $monthRequestVar = Mage::getModel('datefilter/catalog_layer_filter_date')->getMonthRequestVar();
        $selectedMonth = Mage::app()->getRequest()->getParam($monthRequestVar, false);
        $currentMonth = ($selectedMonth) ? $selectedMonth : date('m');
        return array($currentMonth - 1, $currentMonth, $currentMonth + 1);
    }
    public function getMonthNameByNum($num)
    {
        $num = ($num == 0) ? 12 : $num;
        $num = ($num == 13) ? 1 : $num;
        if($num){
            $dateObj = DateTime::createFromFormat('!m', $num);
            $monthName = $dateObj->format('F');
            return $monthName;

        }
        return '';
    }

    public function getMonth()
    {
        return Mage::getModel($this->_filterModelName)->getSelectedMonth();
    }

    public function getClearUrl()
    {
        $existingParams = Mage::app()->getRequest()->getParams();
        $monthRequestVarName =  Mage::getModel($this->_filterModelName)->getMonthRequestVar();
        $dateRequestVarName = Mage::getModel($this->_filterModelName)->getRequestVar();
        if(isset($existingParams[$monthRequestVarName])){
            unset($existingParams[$monthRequestVarName]);
        }
        if(isset($existingParams[$dateRequestVarName])){
            unset($existingParams[$dateRequestVarName]);
        }
        if(isset($existingParams['id'])){
            unset($existingParams['id']);
        }
        array_unique($existingParams);
        return  Mage::app()->getRequest()->getOriginalPathInfo().'?'.http_build_query($existingParams);
    }
    public function getFilterMonthUrl($monthNum)
    {
        $monthReqeustVar = Mage::getModel($this->_filterModelName)->getMonthRequestVar();
        $yearRequestVar = Mage::getModel($this->_filterModelName)->getYearRequestVar();
        $existingYearVal = Mage::app()->getRequest()->getParam($yearRequestVar, false);
        $yearVal = ($existingYearVal) ? $existingYearVal : date("Y");
        if($monthNum == 13){
            $yearVal++;
            $monthNum = 1;
        }
        if($monthNum == 0){
            $monthNum = 12;
            $yearVal--;
        }
        $paramsToAdd = array($monthReqeustVar => $monthNum, $yearRequestVar => $yearVal);
        $existingParams = Mage::app()->getRequest()->getParams();
        if(isset($existingParams['id'])){
            unset($existingParams['id']);
        }
        if(isset($existingParams['release_date'])){
            unset($existingParams['release_date']);
        }
        $monthRequestVar = Mage::getModel('datefilter/catalog_layer_filter_date')->getMonthRequestVar();
        if(isset($existingParams[$monthRequestVar])){
            unset($existingParams[$monthRequestVar]);
        }
        if(isset($existingParams[$yearRequestVar])){
            unset($existingParams[$yearRequestVar]);
        }
        $resArray = array_merge($paramsToAdd, $existingParams);
        array_unique($resArray);
        return Mage::app()->getRequest()->getOriginalPathInfo().'?'.http_build_query($resArray);
    }

    public function getCurrentYear()
    {
        return date("Y");
    }

    public function getYearForToolbar()
    {
        $yearRequestVar = Mage::getModel($this->_filterModelName)->getYearRequestVar();
        $selectedYear = Mage::app()->getRequest()->getParam($yearRequestVar, false);
        if($selectedYear){
            return $selectedYear;
        }
        return date("Y");
    }

    public function getMonthDifferrence()
    {
        $currentMonth = date("m");
        $monthReqeustVar = Mage::getModel($this->_filterModelName)->getMonthRequestVar();
        $selectedMonth = Mage::app()->getRequest()->getParam($monthReqeustVar, false);
        $yearRequestVar = Mage::getModel($this->_filterModelName)->getYearRequestVar();
        $selectedYear = Mage::app()->getRequest()->getParam($yearRequestVar, false);
        $currentYear = date("Y");

        if($selectedMonth && (abs($currentMonth - $selectedMonth)> 0)){
            if($selectedYear > $currentYear){
                return ($selectedYear - $currentYear)*12-($currentMonth - $selectedMonth).'m';
            }
            return -($currentMonth - $selectedMonth).'m';
        }
        return 0;
    }

    public function getAppliedDatesStr()
    {
        $reqeustVar = Mage::getModel($this->_filterModelName)->getRequestVar();
        $selectedDatesStr = Mage::app()->getRequest()->getParam($reqeustVar, false);
        $selectedDatesArr = explode('|', $selectedDatesStr);
        $dateFormattedForDatepicker = array();
        foreach($selectedDatesArr as $selectedDate){
            $timeStamp = Mage::getModel('core/date')->timestamp(strtotime($selectedDate));
            $dateFormattedForDatepicker [] = date('Y-m-d', $timeStamp);
        }
        return json_encode($dateFormattedForDatepicker);
    }
}
