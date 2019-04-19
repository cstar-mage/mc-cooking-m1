<?php

class BroSolutions_DateFilter_Model_Catalog_Layer_Filter_Date extends Mage_Catalog_Model_Layer_Filter_Abstract
{
    protected $_productsCollection = NULL;
    protected $_availableDates = NULL;
    protected $_allDates = NULL;
    protected $_monthRequestVar = 'month';
    protected $_yearRequestVar = 'year';
    protected $_privateClassesDates = NULL;
    protected $_soldOutClassesDates = NULL;
    protected $_privateClassesDatesInPast = NULL;


    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->_requestVar = 'release_date';
    }


    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = $request->getParam($this->getRequestVar());
        $monthFilter = $request->getParam($this->_monthRequestVar, false);
        $yearFilter = $request->getParam($this->_yearRequestVar, false);
        $this->_getLayerProductsCollection()->addAttributeToSelect('release_date');
        $select = $this->getLayer()->getProductCollection()->getSelect();

        $select->columns(array('release_month' => 'MONTH(release_date)'));
        $select->where('release_date IS NOT NULL');
        $isClearUrl = $this->isUrlHasFilterParams();

        if(!$yearFilter){
            $select->where("YEAR(release_date) >= ?", date("Y"));
        } elseif(!$isClearUrl) {
            $select->where("YEAR(release_date) >= ?", $yearFilter);
        }
        if(!Mage::registry('before_load_collection_select')){
            Mage::register('before_load_collection_select', clone $select);
        }
        if($isClearUrl){
            $select->where("MONTH(release_date) >= ?", date('m'));
            $select->where("MONTH(release_date) < ?", date('m') + 1);
            $select->where("YEAR(release_date) >= ?", date('Y'));
            $select->where("DAY(release_date) >= ?", date('d'));
            //$select->where("STR_TO_DATE(concat(DATE_FORMAT(e.release_date, '%d-%m-%Y'), ' ', e.release_time), '%d-%m-%Y %l %p') - INTERVAL 24 HOUR >= NOW()");
        }
        $select->columns("(STR_TO_DATE(concat(DATE_FORMAT(e.release_date, '%d-%m-%Y'), ' ', e.release_time), '%d-%m-%Y %l %p') - INTERVAL 24 HOUR > NOW()) as rel_d_t");

        if ((!$filter || Mage::registry('date_filter')) && !$monthFilter) {
            return $this;
        }
        if($monthFilter && !$filter){
            $select->where("MONTH(release_date) = ?", $monthFilter);
        } else {
            if(strpos('|', $filter) == false){
                $filter = explode('|', $filter);
            }
            $select->where('e.release_date IN (?)', $filter);
        }
        $this->_setStates($filter);
        Mage::register('date_filter', true);
        return $this;
    }

    protected function _setStates($filter)
    {
        if(is_array($filter)){
            foreach($filter as $item){
                $timeStamp = Mage::getModel('core/date')->timestamp(strtotime($item));
                $stateLabel = date('Y-m-d', $timeStamp);
                $count = isset($this->_allDates[$item]) ? $this->_allDates[$item] : 0;
                $state = $this->_createItem(
                    $stateLabel, $item, $count
                )->setVar($this->_requestVar);
                $this->getLayer()->getState()->addFilter($state);
            }
        } else {
            $timeStamp = Mage::getModel('core/date')->timestamp(strtotime($filter));
            $stateLabel = date('Y-m-d', $timeStamp);
            $count = isset($this->_allDates[$filter]) ? $this->_allDates[$filter] : 0;
            $state = $this->_createItem(
                $stateLabel, $filter
            )->setVar($this->_requestVar);
            $this->getLayer()->getState()->addFilter($state);
        }
    }

    /**
     * Get filter name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('core')->__('Release Date');
    }

    /**
     * Get data array for building sale filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $data = array();
        $availableDates = $this->getAllAvailableDates();
        foreach ($availableDates as $date => $count) {
            $dateTimestamp = Mage::getModel('core/date')->timestamp(strtotime($date));
            $data[] = array(
                'label' => date('Y-m-d', $dateTimestamp),
                'value' => $date,
                'count' => $count,
            );
        }
        return $data;
    }


    protected function _getLayerProductsCollection($clone = false)
    {
        if (!$this->_productsCollection) {
            $this->_productsCollection = $this->getLayer()->getProductCollection();
        }
        if ($clone) {
            $clonedCollection = clone $this->_productsCollection;
            $clonedCollection->clear();
            return $clonedCollection;
        }
        return $this->_productsCollection;
    }

    protected function _getAvailableDates()
    {
        if (!$this->_availableDates) {
            $collection = $this->_getLayerProductsCollection(true)->addAttributeToSelect('release_date');
            $this->_availableDates = array();
            $this->_availableDates = array();
            foreach ($collection as $product) {
                $releaseDate = $product->getReleaseDate();
                if (!empty($releaseDate)) {
                    if (!isset($this->_availableDates[$product->getReleaseDate()])) {
                        $this->_availableDates[$product->getReleaseDate()] = 1;
                    } else {
                        $this->_availableDates[$product->getReleaseDate()] += 1;
                    }
                }
            }
        }
        return $this->_availableDates;
    }

    protected function _getAllDates()
    {
        $result = array();
        $collection = $this->_getLayerProductsCollection(true)->addAttributeToSelect('release_date');
        foreach ($collection as $product) {
            $releaseDate = $product->getReleaseDate();
            if (!empty($releaseDate)) {
                if (!isset($result[$product->getReleaseDate()])) {
                    $result[$product->getReleaseDate()] = 1;
                } else {
                    $result[$product->getReleaseDate()] += 1;
                }
            }
        }
        return $result;
    }

    protected function _getAppliedFilterDate()
    {
        $requestParam = Mage::app()->getRequest()->getParam($this->getRequestVar(), false);
        return $requestParam;
    }

    public function getAvailableDates()
    {
        return $this->_getAllDates();
    }
    public function getAppliedFilterDate()
    {
        return $this->_getAppliedFilterDate();
    }

    public function getAvailableMonths()
    {
        $collection = $this->_getLayerProductsCollection(true);
        $select = clone $collection->getSelect();
        $select->reset(Zend_Db_Select::COLUMNS)->reset(Zend_Db_Select::WHERE)->columns('MONTH(e.release_date) AS release_month')->group('release_month');
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $results = $readConnection->fetchAll($select);
        foreach($results as $item){
            if(isset($item['release_month'])){
                $result [] = $item['release_month'];
            }
        }
        $result = array_unique($result);
        return $result;
    }

    public function getSelectedMonth()
    {
        $reqeustParam = Mage::app()->getRequest()->getParam($this->_monthRequestVar, false);
        if($reqeustParam){
            return $reqeustParam;
        }
        return date('m');
    }

    public function getMonthRequestVar()
    {
        return $this->_monthRequestVar;
    }

    public function getYearRequestVar()
    {
        return $this->_yearRequestVar;
    }

    public function getAllAvailableDatesInPast()
    {
        $releaseDateAttributeId = $this->_getReleaseDateAttributeId();
        $statusAttributeId = $this->_getStatusAttributeId();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $select = $readConnection->select()->from(array('e' => $resource->getTableName('catalog_product_entity')))
            ->joinLeft(
                array('st' => $resource->getTableName('catalog_product_entity_int')),
                '`e`.`entity_id` = `st`.`entity_id` AND `st`.`attribute_id` = '.$statusAttributeId.' AND `st`.`entity_type_id` = 4',
                array('status' => 'value')
            )->joinLeft(
                array('rd' => $resource->getTableName('catalog_product_entity_datetime')),
                '`e`.`entity_id` = `rd`.`entity_id` AND `rd`.`attribute_id` = '.$releaseDateAttributeId.' AND `rd`.`entity_type_id` = 4',
                array('release_date' => 'value')
            );
        $select->where('`st`.`value` = ?', Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
        $select->where('`rd`.`value` IS NOT NULL');
        $nowDate = date('Y-m-d H:i:s');
        $select->where('`rd`.`value` <= ?', $nowDate);
        $selectStr = (string) $select;
        $results = $readConnection->fetchAll($selectStr, 'release_date');
        $this->_privateClassesDates = array();
        $retArr = array();
        foreach($results as $dateRow){
            if(isset($dateRow['release_date'])){
                $releaseDate = $dateRow['release_date'];
                if(isset($retArr[$releaseDate])){
                    $this->_privateClassesDatesInPast[$releaseDate] ++;
                } else {
                    $this->_privateClassesDatesInPast[$releaseDate] = 1;
                }
            }
        }
        return $this->_privateClassesDatesInPast;
    }

    protected function _getReleaseDateAttributeId()
    {
        $attributeId = Mage::getResourceModel('eav/entity_attribute')
            ->getIdByCode('catalog_product', 'release_date');
        return $attributeId;
    }

    protected function _getStatusAttributeId()
    {
        $attributeId = Mage::getResourceModel('eav/entity_attribute')
            ->getIdByCode('catalog_product', 'status');
        return $attributeId;
    }

    public function getAllAvailableClassDates()
    {
        if (!Mage::registry('before_load_collection_select')) {
            return $this->_getAvailableDates();
        }
        $select = Mage::registry('before_load_collection_select');
        $select->reset(Varien_Db_Select::COLUMNS)->columns('release_date');
        $select->where('private_class != ?', 1);
        $select->where('status = ?', 1);
        $selectStr = (string) $select;
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $results = $readConnection->fetchAll($selectStr, 'release_date');
        $this->_allDates = array();
        foreach($results as $dateRow){
            $releaseDate = current($dateRow);
            if(isset($retArr[$releaseDate])){
                $this->_allDates[$releaseDate] ++;
            } else {
                $this->_allDates[$releaseDate] = 1;
            }
        }
        return $this->_allDates;
    }

    public function getAllAvailableDates()
    {
        if (!Mage::registry('before_load_collection_select')) {
            return $this->_getAvailableDates();
        }
        $select = Mage::registry('before_load_collection_select');
        $select->reset(Varien_Db_Select::COLUMNS)->columns('release_date');
        //$select->where('private_class != ?', 1);
        $select->where('status = ?', 1);
        $selectStr = (string) $select;
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $results = $readConnection->fetchAll($selectStr, 'release_date');
        $this->_allDates = array();
        foreach($results as $dateRow){
            $releaseDate = current($dateRow);
            if(isset($retArr[$releaseDate])){
                $this->_allDates[$releaseDate] ++;
            } else {
                $this->_allDates[$releaseDate] = 1;
            }
        }
        return $this->_allDates;
    }

    public function getPrivateClassesDates()
    {
        $select = clone Mage::registry('before_load_collection_select');
        $select->reset(Varien_Db_Select::COLUMNS)->columns('release_date');
        $whereParts = $select->getPart(Zend_Db_Select::WHERE);
        $where = '';
        foreach($whereParts as $key => $wherePart){
            if(strpos($wherePart, 'private_class')){
                unset($whereParts[$key]);
            } else {
                $where .= $wherePart;
            }
        }
        $select->reset(Zend_Db_Select::WHERE);
        $select->where($where);
        $select->where('status = ?', 1);
        $select->where('private_class = ?', 1);
        $selectStr = (string) $select;
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $results = $readConnection->fetchAll($selectStr, 'release_date');
        $this->_privateClassesDates = array();
        foreach($results as $dateRow){
            $releaseDate = current($dateRow);
            if(isset($retArr[$releaseDate])){
                $this->_privateClassesDates[$releaseDate] ++;
            } else {
                $this->_privateClassesDates[$releaseDate] = 1;
            }
        }
        return $this->_privateClassesDates;
    }

    public function getSoldOutClassesDates()
    {

        $select = clone Mage::registry('before_load_collection_select');
        $select->reset(Varien_Db_Select::COLUMNS)->columns('release_date');
        $whereParts = $select->getPart(Zend_Db_Select::WHERE);
        $where = '';
        foreach($whereParts as $key => $wherePart){
            if(strpos($wherePart, 'private_class')){
                unset($whereParts[$key]);
            } else {
                $where .= $wherePart;
            }
        }
        $select->reset(Zend_Db_Select::WHERE);
        $select->where('status = ?', 1);
        $select->where($where);
        $inventoryTableName = Mage::getSingleton('core/resource')->getTableName('cataloginventory/stock_item');
        $select->joinLeft(
            array('stock' => $inventoryTableName),
            '`e`.`entity_id` = `stock`.`product_id`',
            array('')
        );
        $select->where('stock.qty <= ?', 0);
        $selectStr = (string) $select;
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $results = $readConnection->fetchAll($selectStr, 'release_date');
        $this->_soldOutClassesDates = array();
        foreach($results as $dateRow){
            $releaseDate = current($dateRow);
            if(isset($retArr[$releaseDate])){
                $this->_soldOutClassesDates[$releaseDate] ++;
            } else {
                $this->_soldOutClassesDates[$releaseDate] = 1;
            }
        }
        return $this->_soldOutClassesDates;
    }

    public function isUrlHasFilterParams()
    {
        $currentCategory = Mage::registry('current_category');
        if($currentCategory){
            $availableSortByOptions = $currentCategory->getAvailableSortByOptions();
            $availableSortBy = array_keys($availableSortByOptions);
            $availableFilterBy = $this->_getFilterVars();
            $availableParams = array_merge($availableSortBy, $availableFilterBy);
            $existingRequestParams = array();
            $requestParams = Mage::app()->getRequest()->getParams();
            if(is_array($requestParams)){
                $existingRequestParams = array_keys($requestParams);
            }
            $resArr = array_intersect($availableParams, $existingRequestParams);
            return empty($resArr);
        }
    }

    protected function _getFilterVars()
    {
        return array($this->getMonthRequestVar(), $this->getYearRequestVar(), $this->getRequestVar());
    }
}
