<?php

class BroSolutions_SmartSlides_Block_Slides extends Mage_Core_Block_Template
{
    protected $_sliderGroup = NULL;
    protected $_sliderGroupId = NULL;
    protected $_defaultTemplate = 'bro_solutions/smart_slides/slides.phtml';
    const HOME_BAGE_BOTTOM_BANNERS_COUNT = 6;
    public function setSliderGroup(BroSolutions_SmartSlides_Model_Smartslidesgroups $group)
    {
        $this->_sliderGroup = $group;
    }

    protected function _toHtml()
    {
        $html = '';
        $template = ($this->_template) ? $this->_template : $this->_defaultTemplate;
        if($this->_sliderGroup){
            $this->setTemplate($template);
            $html = parent::_toHtml();
        } else if($this->_sliderGroupId){
            $sliderGroupInstance = $this->getSlidesGroup();
            if($sliderGroupInstance){
                $this->setTemplate($template);
                $html = parent::_toHtml();
            }
        }
        return $html;
    }

    public function getSlidesGroup()
    {
        if($this->_sliderGroup){
            return $this->_sliderGroup;
        }
        if($this->_sliderGroupId){
            $groupInstance = Mage::getModel('smartslides/smartslidesgroups')->load($this->_sliderGroupId);
            if($groupInstance){
                $this->_sliderGroup = $groupInstance;
                return $groupInstance;
            }
        }
        return false;
    }



    public function setSliderGroupId($groupId)
    {
        $this->_sliderGroupId = $groupId;
        $this->getSlidesGroup();
        return $this;
    }

    public function getSlidesCollection()
    {
        $collection = new Varien_Object();
        if($this->_sliderGroup){
            $slidesIdsStr = $this->_sliderGroup->getSlidesIds();
            $slidesIds = unserialize($slidesIdsStr);
            $collection = Mage::getModel('smartslides/smartslides')->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $slidesIds))
                ->addFieldToFilter('status', array('eq' => 1));
            $collection->getSelect()->order('sort_order', 'ASC');
        }
        return $collection;
    }

    public function getSlidesGroupJsonConfig()
    {
        $groupData = $this->getSlidesGroup()->getData();
        //... data conversion
        $groupData['effect'] = (bool)$groupData['effect'];
        $groupData['auto_play'] = (bool)$groupData['auto_play'];
        $groupData['is_arrows_enabled'] = (bool)$groupData['is_arrows_enabled'];
        $groupData['is_dots_enabled'] = (bool)$groupData['is_dots_enabled'];
        $groupData['pause_on_hower'] = (bool)$groupData['pause_on_hower'];
        $groupData['slide_type'] = (bool)$groupData['slide_type'];

        return json_encode($groupData);
    }

    public function getSliderGroupId()
    {
        $sliderGroup = $this->getSlidesGroup();
        if($sliderGroup){
            return $sliderGroup->getId();
        }
        return false;
    }

    public function getSliderByGroupCode($code)
    {
        $slidesGroup = Mage::getResourceModel('smartslides/smartslidesgroups_collection')
            ->addFieldToFilter('group_code', array('eq' => $code))
            ->setPageSize(1)->getFirstItem();
        if($slidesGroup->getId()){
            return $slidesGroup;
        }
        return false;
    }


    public function setSliderGroupByCode($code)
    {
        $sliderGroup = $this->getSliderByGroupCode($code);
        if($sliderGroup && $sliderGroup->getId()){
            $this->setSliderGroup($sliderGroup);
        }
        return $this;
    }

    public function getSlideImageUrl(BroSolutions_SmartSlides_Model_Smartslides $slide)
    {
        $slideUrl = '';
        if($slide->getId()){
            $mediaUrl = $this->getUrl('media');
            $mediaUrl = str_replace('index.php/', '', $mediaUrl);
            $slideUrl = $mediaUrl.$slide->getImagePath();
        }
        return $slideUrl;
    }

    public function getLatestProductsCollection()
    {
        $now = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        $collection = Mage::getModel('catalog/product')->getCollection()->addUrlRewrite()
            ->addAttributeToSelect('name')->addAttributeToSelect('release_date')
            ->addAttributeToSelect('small_image')
            ->addAttributeToSort('release_date', 'ASC')
            ->addAttributeToFilter('release_date', array('gteq' => $now))
            ->setPageSize(self::HOME_BAGE_BOTTOM_BANNERS_COUNT);
        return $collection;
    }

}