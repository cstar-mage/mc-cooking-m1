<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslides_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getUrl('*/*/saveslide'), 'method' => 'post', 'enctype' => 'multipart/form-data'));
        $form->setUseContainer(true);
        $fieldset = $form->addFieldset('main', array('legend' => 'Request Data'));
        $this->_addElementTypes($fieldset);
        $fieldset->addField('entity_id', 'hidden', array(
            'name' => 'entity_id',
        ));

        $fieldset->addField('slider_group_id', 'hidden', array(
            'name' => 'slider_group_id',

        ));

        $fieldset->addField('title', 'text', array(
            'label'     => Mage::helper('core')->__('Name'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'title',
            'value'  => '',
        ));

        $fieldset->addField('image_alt', 'text', array(
            'label'     => Mage::helper('core')->__('Image Alt'),
            'name'      => 'image_alt',
            'value'  => '',
        ));

        $fieldset->addField('slide_class', 'text', array(
            'label'     => Mage::helper('core')->__('Slide Class'),
            'name'      => 'slide_class',
            'value'  => '',
        ));

        $fieldset->addField('sort_order', 'text', array(
            'label'     => Mage::helper('core')->__('Sort Order'),
            'name'      => 'sort_order',
            'value'  => '',
        ));
        $fieldset->addField('image_link', 'text', array(
            'label'     => Mage::helper('core')->__('Image Link'),
            'name'      => 'image_link',
            'value'  => '',
        ));
        $fieldset->addField('subtitle', 'text', array(
            'label'     => Mage::helper('core')->__('Subtitle'),
            'name'      => 'subtitle',
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

        $fieldset->addField('description', 'textarea', array(
            'label'     => Mage::helper('core')->__('Description'),
            'name'      => 'description',
            'value'  => '',
        ));
        $fieldset->addField('image_path', 'image', array(
          "label"     => Mage::helper('core')->__('Upload Image'),
          'required'  => false,
          'name'      => 'image_path',
        ));
        $fieldset->addField('video_link', 'text', array(
            "label"     => Mage::helper('core')->__('Video Url'),
            "note"      => 'Enter Youtube video url-key (only bold text). Example https://www.youtube.com/watch?v=<strong>url-Youtube_Key</strong>',
            'required'  => false,
            'name'      => 'video_link',
            'comment' => "Please note, if you set video url slide image will be as placeholder",
        ));
        $groupOptions = Mage::getModel('smartslides/smartslidesgroups')->getOptionalArray();
        /*$fieldset->addField('group_ids', 'select', array(
            'label'     => Mage::helper('core')->__('Sliders group'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'group_ids',
            'onclick' => "",
            'onchange' => "",
            'default'  => '0',
            'values' => $groupOptions,
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));*/

        $slidesModel = Mage::registry('slides_model');
        if($slidesModel){
            $groupId = $this->getRequest()->getParam('from_group');
            if($groupId){
                $slidesModel->setSliderGroupId($groupId);
            }
            $form->setValues($slidesModel->getData());
        }
        $this->setForm($form);
        return parent::_prepareForm();
    }
}