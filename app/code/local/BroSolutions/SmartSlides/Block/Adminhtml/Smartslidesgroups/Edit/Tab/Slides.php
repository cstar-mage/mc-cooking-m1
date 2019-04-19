<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslidesgroups_Edit_Tab_Slides extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('slides_grid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('desc');
        $this->setUseAjax(true);
        if ($this->_getSelection()->getEntityId()) {
            $this->setDefaultFilter(array('in_slides'=>1));
        }
    }
    protected function _prepareCollection()
    {
        $slidesCollection = Mage::getModel('smartslides/smartslides')->getCollection();
        $this->setCollection($slidesCollection);
        parent::_prepareCollection();
        return $this;
    }
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_slides',
            array(
                'type'  => 'checkbox',
                'name'  => 'in_slides',
                'values'=> $this->_getSelectedSlidesIds(),
                'index' => 'entity_id',
                'width' => '20px',
            )
        );
        $this->addColumn(
            'entity_id',
            array(
                'header' => Mage::helper('catalog')->__('ID'),
                'width'         => '1',
                'align'  => 'left',
                'index'  => 'entity_id',
            )
        );
        $this->addColumn(
            'title',
            array(
                'header' => Mage::helper('catalog')->__('Name'),
                'width'         => '1',
                'align'  => 'left',
                'index'  => 'title',
            )
        );
        $this->addColumn('action_edit', array(
            'header'   => $this->helper('catalog')->__('Action'),
            'width'    => 15,
            'sortable' => false,
            'getter'    => 'getId',
            'filter'   => false,
            'type'     => 'action',
            'actions'   => array(
                array(
                    'caption'   => Mage::helper('customer')->__('Edit'),
                    'url'       => array('base'=> '*/*/editslide'),
                    'field'     => 'slide_id'
                )
            ),
        ));

    }

    protected function _getSelection()
    {
        $slideGroupId = Mage::app()->getRequest()->getParam('slidergroup_id');
        $slideGroup = Mage::getModel('smartslides/smartslidesgroups')->load($slideGroupId);
        return $slideGroup;
    }

    public function getSelectedSldies()
    {
        $slidesCollection = new Varien_Data_Collection();
        $slideGroup = $this->_getSelection();
        if($slideGroup){
            $slidesIdsSerialized = $slideGroup->getSlidesIds();
            $slidesIds = unserialize($slidesIdsSerialized);
            if(!empty($slidesIds)){
                $slidesCollection = Mage::getModel('smartslides/smartslides')->getCollection()->addFieldToFilter('entity_id', array('in', $slidesIds));

            }
        }
        return $slidesCollection;
    }

    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_slides') {
            $slidesIds = $this->_getSelectedSlidesIds();
            if (empty($slidesIds)) {
                $slidesIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in' => $slidesIds));
            } else {
                if ($slidesIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', array('nin' => $slidesIds));
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    public function _getSelectedSlidesIds()
    {
        $selectedSlides = $this->getSelectedSldies();
        $ids = array();
        foreach($selectedSlides as $slide){
            $ids[] = $slide->getEntityId();
        }
        return $ids;
    }


    protected function _prepareLayout()
    {
        $this->setChild('export_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Export'),
                    'onclick'   => $this->getJsObjectName().'.doExport()',
                    'class'   => 'task'
                ))
        );
        $this->setChild('reset_filter_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Reset Filter'),
                    'onclick'   => $this->getJsObjectName().'.resetFilter()',
                ))
        );
        $this->setChild('search_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Search'),
                    'onclick'   => $this->getJsObjectName().'.doFilter()',
                    'class'   => 'task'
                ))
        );
        $groupId = $this->getRequest()->getParam('slidergroup_id');
        if($groupId){
            $createSlideUrl = Mage::helper('adminhtml')->getUrl('adminhtml/smartslides/editslide', array('from_group' => $groupId));
        } else {
            $createSlideUrl = Mage::helper('adminhtml')->getUrl('adminhtml/smartslides/editslide');
        }
        $this->setChild('add_new_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Add new Slide'),
                    'onclick'   => 'setLocation(\''.$createSlideUrl.'\')',
                    'class'   => 'task'
                ))
        );
        return $this;
    }

    public function getMainButtonsHtml()
    {
        $html = '';
        if($this->getFilterVisibility()){
            $html.= $this->getResetFilterButtonHtml();
            $html.= $this->getSearchButtonHtml();
            $html.= $this->getAddNewButtonHtml();
        }
        return $html;
    }
    public function getAddNewButtonHtml()
    {
        return $this->getChildHtml('add_new_button');
    }
}