<?php
class BroSolutions_SmartSlides_Model_Smartslides extends Mage_Core_Model_Abstract
{
    const SLIDES_DIR_IN_MEDIA = 'slides';

    protected function _construct()
    {
        $this->_init('smartslides/smartslides');
    }
    static public function getOptionArray()
    {
        return array(
            1    => Mage::helper('catalog')->__('Enabled'),
            0   => Mage::helper('catalog')->__('Disabled')
        );
    }

    public function getLastEntityId()
    {
        $collection = $this->getCollection()
            ->setOrder('entity_id', 'DESC')->setPageSize(1)->setCurPage(1);
        return $collection->getFirstItem()->getId();
    }

    public function checkIfSlideExists($slideId)
    {
        $slide = Mage::getModel('smartslides/smartslides')->load($slideId);
        if($slide->getEntityId()){
            return true;
        }
        return false;
    }
}