<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslidesgroups_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $this->setForm($form);

        $fieldset = $form->addFieldset('main', array('legend' => 'Slider group configuration'));
        $this->_addElementTypes($fieldset);
        $slidesGrpoupModel = Mage::registry('slides_group_model');
        $slideGroupIdValue = Mage::app()->getRequest()->getParam('slidergroup_id');
        $slidesGrpoupModel->setData('slider_group_id', $slideGroupIdValue);

        $fieldset->addField('slider_group_id', 'hidden', array(
            'name' => 'slider_group_id',
        ));

        $fieldset->addField('name', 'text', array(
            'label'     => Mage::helper('core')->__('Name'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'name',
            'value'  => '',
        ));

        $fieldset->addField('group_code', 'text', array(
            'label'     => Mage::helper('core')->__('Group Code'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'group_code',
            'value'  => '',
        ));


        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('core')->__('Status'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'status',
            'onclick' => "",
            'onchange' => "",
            'default'  => '1',
            'values' => array('1'=>'Enabled','0' => 'Disabled'),
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));


        $fieldset->addField('effect', 'select', array(
            'label'     => Mage::helper('core')->__('Effect'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'effect',
            'onclick' => "",
            'onchange' => "",
            'default'  => 'false',
            'values' => array('false'=>'Slide','true' => 'Fade'),
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));


        $fieldset->addField('animate_speed', 'text', array(
            'label'     => Mage::helper('core')->__('Animate Speed'),
            'name'      => 'animate_speed',
            'value'  => '',
        ));


        $fieldset->addField('is_arrows_enabled', 'select', array(
            'label'     => Mage::helper('core')->__('Is Arrows Enabled'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'is_arrows_enabled',
            'onclick' => "",
            'onchange' => "",
            'default'  => 'false',
            'values' => array('true'=>'Yes','false' => 'No'),
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));

        $fieldset->addField('is_dots_enabled', 'select', array(
            'label'     => Mage::helper('core')->__('Is Dots Enabled'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'is_dots_enabled',
            'onclick' => "",
            'onchange' => "",
            'default'  => 'false',
            'values' => array('true'=>'Yes','false' => 'No'),
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));

        $fieldset->addField('per_page', 'text', array(
            'label'     => Mage::helper('core')->__('Per page'),
            'required'  => false,
            'name'      => 'per_page',
            'value'  => '',
        ));


        $fieldset->addField('auto_play', 'select', array(
            'label'     => Mage::helper('core')->__('Auto Play'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'auto_play',
            'onclick' => "",
            'onchange' => "",
            'default'  => 'false',
            'values' => array('true'=>'Yes','false' => 'No'),
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));

        $fieldset->addField('pause_on_hower', 'select', array(
            'label'     => Mage::helper('core')->__('Pause on hover'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'pause_on_hower',
            'onclick' => "",
            'onchange' => "",
            'default'  => 'false',
            'values' => array('true'=>'Yes','false' => 'No'),
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));


        $fieldset->addField('blocks_after', 'text', array(
            'label'     => Mage::helper('Core')->__('Display Slider After Blocks:'),
            'name'      => 'blocks_after',
            'after_element_html' => '</br><small>Please, enter block names in layout, For Example: catalog.product.vew,catalog.category.view</small>',
            'tabindex' => 1
        ));

        $fieldset->addField('handle', 'text', array(
            'label'     => Mage::helper('Core')->__('Display on pages:'),
            'name'      => 'handle',
            'after_element_html' => '</br><small>Please, enter handle in layout, For Example: catalog_product_view</small>',
            'tabindex' => 1
        ));


        $fieldset->addField('slide_type', 'select', array(
            'label'     => Mage::helper('core')->__('Slider Type'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'slide_type',
            'default'  => 'true',
            'values' => array('true'=>'Default','false' => 'Responsive'),
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));

        if($slidesGrpoupModel){
            $form->setValues($slidesGrpoupModel->getData());
        }
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _prepareLayout() {
        $return = parent::_prepareLayout();
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        return $return;
    }

}