<?php

class BroSolutions_SmartSlides_Adminhtml_SmartslidesController extends Mage_Adminhtml_Controller_Action
{
    public function slidesgroupsAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }


    public function editslidersgroupAction()
    {
        $groupId = $this->getRequest()->getParam('slidergroup_id');
        $groupModel = Mage::getModel('smartslides/smartslidesgroups')->load($groupId);
        Mage::register('slides_group_model', $groupModel);
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('smartslides/adminhtml_smartslidesgroups_edit', 'edit_slides_group'))
            ->_addLeft($this->getLayout()->createBlock('smartslides/adminhtml_smartslidesgroups_edit_tabs'));

        $this->renderLayout();
    }

    public function saveslidersgroupAction()
    {
        $sliderGroupData = $this->getRequest()->getParams();
        if($sliderGroupData){
            try {
                if(isset($sliderGroupData['form_slides'])){
                    $slidesIdsStr = $sliderGroupData['form_slides'];
                    $slidesIds = explode('&',$slidesIdsStr);
                    if(!empty($slidesIds)){
                        $sliderGroupData['slides_ids'] = serialize($slidesIds);
                    }
                }
                if(isset($sliderGroupData['entity_id'])){
                    unset($sliderGroupData['entity_id']);
                }
                $slidersGroupsModel = Mage::getModel('smartslides/smartslidesgroups')->setData($sliderGroupData);
                if(empty($sliderGroupData['slider_group_id'])){
                    $slidersGroupsModel->setEntityId(NULL);
                } else {
                    $slidersGroupsModel->setEntityId($sliderGroupData['slider_group_id']);
                }
                $slidersGroupsModel->save();
                Mage::getSingleton('core/session')->addSuccess('Slider group has been save.');
                $this->_redirect('*/*/slidesgroups');
            }
            catch (Exception $e){
                Mage::logException($e);
                Mage::getSingleton('core/session')->addError($e->getMessage());
                $this->_redirect('*/*/editslidersgroup');
            }
        }
    }

    public function slidesAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function editslideAction()
    {
        $slideId = $this->getRequest()->getParam('slide_id');
        $slideModel = Mage::getModel('smartslides/smartslides')->load($slideId);
        Mage::register('slides_model', $slideModel);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveslideAction()
    {
        $sliderData = $this->getRequest()->getParams();
        $imageData = (isset($sliderData['image_path'])) ? $sliderData['image_path'] : array();
        unset($sliderData['image_path']);
        if($sliderData){
            try {
                //$imageData =
                $slidersModel = Mage::getModel('smartslides/smartslides')->setData($sliderData);
                if (empty($sliderData['entity_id'])) {
                    $slidersModel->setEntityId(NULL);
                }
                if(isset($imageData['delete']) && !empty($sliderData['entity_id']) && ($imageData['delete'] == '1')){
                    $imagePath = $slidersModel->getImagePath();
                    str_replace('/', DS, $imagePath);
                    $path = Mage::getBaseDir('media') . DS . 'slides' . DS;
                    $fullPath = $path.$imagePath;
                    unlink($fullPath);
                    $slidersModel->setImagePath('');
                } elseif (isset($_FILES['image_path']['name']) && !empty($_FILES['image_path']['name'])) {
                    $uploader = new Varien_File_Uploader('image_path');
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);
                    $path = Mage::getBaseDir('media') . DS . 'slides' . DS;
                    $idNamePrefix = empty($sliderData['entity_id']) ? '' : $sliderData['entity_id'];
                    $uploader->save($path, $idNamePrefix.$_FILES['image_path']['name']);
                    $data['image_path'] = $path . $_FILES['image_path']['name'];
                    $slidersModel->setImagePath('slides'.'/'.$idNamePrefix.$_FILES['image_path']['name']);
                }
                $slidersModel->save();
                $slideId = $slidersModel->getLastEntityId();

                Mage::getSingleton('core/session')->addSuccess('Slide has been save.');
                if($slideId && isset($sliderData['slider_group_id']) && $sliderData['slider_group_id']){
                    $slideGroup = Mage::getModel('smartslides/smartslidesgroups')->load($sliderData['slider_group_id']);
                    if($slideGroup->getEntityId()){
                        $slideGroup->addSlideById($slideId);
                    }
                    $this->_redirect('*/smartslides/editslidersgroup', array('slidergroup_id' => $sliderData['slider_group_id'],
                        'active_tab' => 'form_slides'));
                } else {
                    $this->_redirect('*/*/slides');
                }
            }
            catch (Exception $e){
                Mage::logException($e);
                Mage::getSingleton('core/session')->addError($e->getMessage());
                $redirectArguments = array();
                if(!empty($sliderData['entity_id'])){
                    $redirectArguments['slide_id'] = $sliderData['entity_id'];
                    $this->_redirect('*/*/editslide', $redirectArguments);
                } else {
                    $this->_redirect('*/*/editslide');
                }
            }
        }
    }




    public function slidesgridAction()
    {
        $this->loadLayout();
        $layout = $this->getLayout();
        $slidesBlock = $layout->createBlock('smartslides/adminhtml_smartslidesgroups_edit_tab_slides', 'slides_grid_in_tab');
        $gridSerializerBlock = $layout->createBlock('adminhtml/widget_grid_serializer');
        $gridSerializerBlock->initSerializerBlock('slides_grid_in_tab', '_getSelectedSlidesIds', 'form_slides');
        $this->getResponse()->setBody($slidesBlock->toHtml().$gridSerializerBlock->toHtml());
    }
}