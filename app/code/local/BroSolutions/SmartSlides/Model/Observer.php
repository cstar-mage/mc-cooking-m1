<?php
class BroSolutions_SmartSlides_Model_Observer
{
    protected $_blocksToInsertAfterNames = array();
    protected $_handles = array();

    public function insertBlock($observer)
    {
        if(empty($this->_blocksToInsertAfterNames)){
            $slidesGroups = Mage::getResourceModel('smartslides/smartslidesgroups_collection');
            foreach($slidesGroups as $slidesGroup){
                $blocksStr = $slidesGroup->getBlocksAfter();
                if(!empty($blocksStr)){
                    $this->_blocksToInsertAfterNames[$slidesGroup->getEntityId()] = $blocksStr;
                }
                $handleStr = $slidesGroup->getHandle();
                if(!empty($handleStr)){
                    $this->_handles[$slidesGroup->getEntityId()] = $handleStr;
                }
            }
        }
        $currentBlock = $observer->getBlock();
        $currentBlockNameInLayout = $currentBlock->getNameInLayout();
        $transportObject = $observer->getTransport();
        if($transportObject){
            $transportObjectHtml = $transportObject->getHtml();
            $layoutInstance = Mage::app()->getLayout();
            $handles = $layoutInstance->getUpdate()->getHandles();
            foreach ($this->_blocksToInsertAfterNames as $groupId => $blockName) {
                $hanldeName = (isset($this->_handles[$groupId])) ? $this->_handles[$groupId] : false;
                if (($blockName == $currentBlockNameInLayout) && (in_array($hanldeName, $handles) !== false)) {
                    $sliderGroupHtml = $layoutInstance->createBlock('smartslides/slides')->setSliderGroupId($groupId)->toHtml();
                    $transportObject->setHtml($transportObjectHtml . $sliderGroupHtml);
                }

            }
        }

        return $this;
    }
}