<?php
class  BroSolutions_All_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $orderItemId = $this->getRequest()->getParam('item_id', false);
        $orderItem = Mage::getModel('sales/order_item')->load($orderItemId);
        if(!$orderItemId || !$orderItem){
            $this->norouteAction();
            return;
        }
        $quoteItemId = $orderItem->getQuoteItemId();
        $this->loadLayout();
        $layout = $this->getLayout();
        $rootBlock = $layout->getBlock('root');
        $giftCertModel = Mage::getModel('giftcards/giftcards');
        $picture = $giftCertModel->getItemImagePath($orderItem);
        if(!$picture){
            $productOptions = $orderItem->getProductOptions();
            if($productOptions && isset($productOptions['options'])){
                foreach($productOptions['options'] as $option){
                    if(isset($option['option_value'])){
                        $pictureToShow = Mage::helper('giftcards')->getImageByOptionTypeId($option['option_value']);
                        if($pictureToShow){
                            $picture = $pictureToShow;
                        }
                        break;
                    }
                }
            }
        }
        $rootBlock->setSelectionPicture($picture);
        $giftCardModel = $giftCertModel->getCollection()->addFieldToFilter('quote_item_id', array('eq' => $quoteItemId))->getFirstItem();

        if($giftCardModel && $giftCardModel->getId()){
            $rootBlock->setGiftCardModel($giftCardModel);
            $this->renderLayout();
            return;
        } else {
            $giftCardIdParam = $this->getRequest()->getParam('gift_card_id', false);
            if($giftCardIdParam){
                $giftCardModel = $giftCertModel->load($giftCardIdParam);
                if($giftCardModel && $giftCardModel->getId()){
                    $rootBlock->setGiftCardModel($giftCardModel);
                    $this->renderLayout();
                    return;
                }
            }
        }
        $this->norouteAction();
        return;
    }

}