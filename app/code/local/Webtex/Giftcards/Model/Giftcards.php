<?php

class Webtex_Giftcards_Model_Giftcards extends Mage_Core_Model_Abstract
{
    /**
     * Cards statuses
     * INACTIVE - just created
     * USED - gift card used and its balance is 0
     * ACTIVE - ready for using for discount
     */
    const INACTIVE = 0;
    const ACTIVE = 1;
    const USED = 2;

    protected function _construct()
    {
        $this->_init('giftcards/giftcards');
        parent::_construct();
    }

    public function activateCard()
    {
        $this->setCardStatus(1);
        $this->save();
    }

    /**
     * Assign card to customer and set active (ready for use)
     *
     * @param $customerId
     */
    public function activateCardForCustomer($customerId)
    {
        if ($this->getId() && $customerId) {
            $this->setCardStatus(1);
            $this->setCustomerId($customerId);
            $this->save();
        }
    }

    /**
     * Generates unique gift card code string
     *
     * @return string
     */
    public function getUniqueCardCode()
    {
        $cardCodes = $this->getResourceCollection()->getColumnValues('card_code');
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $mask = '#####-#####-#####';

        do {
            $cardCode = $mask;
            while (strpos($cardCode, '#') !== false) {
                $cardCode = substr_replace($cardCode, $characters[mt_rand(0, strlen($characters)-1)], strpos($cardCode, '#'), 1);
            }
        } while (in_array($cardCode, $cardCodes));
        
        return $cardCode;
    }


    protected function _getOrderItemByOrderAndProductId($order, $productId)
    {
        foreach ($order->getAllItems() as $item){
            $itemProductId = $item->getProductId();
            if($itemProductId == $productId){
                return $item;
            }
        }
        return false;
    }


    protected function _sendEmailCard($storeId = 0)
    {
        if($order = Mage::getModel('sales/order')->load($this->getOrderId())){
            $storeId = $order->getStoreId();
        } else {
            $storeId = 1;
        }
        $amount = number_format(Mage::app()->getStore($storeId)->convertPrice($this->getCardAmount(), false, false),2);
        $product = Mage::getModel('catalog/product')->load($this->getProductId());
        $orderItemToSend = false;
        try {

            if($order && $product){
                $orderItem = $this->_getOrderItemByOrderAndProductId($order, $product->getId());
                if ($product->getTypeId() == 'giftcards') {
                    $itemOptions = $orderItem->getProductOptions();
                    if(isset($itemOptions['options']) && !empty($itemOptions['options'])){
                        foreach($itemOptions['options'] as $option){
                            if(isset($option['option_type']) && isset($option['option_value']) && isset($option['option_type']) && $option['option_type'] == 'drop_down'){
                                $pictureToSend = Mage::helper('giftcards')->getImageByOptionTypeId($option['option_value']);
                                if($pictureToSend){
                                    $orderItemToSend = $orderItem;
                                    $picture = $pictureToSend;
                                    break;
                                } else {
                                    if(Mage::helper('giftcards')->isUseDefaultPicture() || !$this->getProductId()) {
                                        $picture = Mage::getDesign()->getSkinUrl('images/giftcard.png',array('_area'=>'frontend'));
                                    } else {
                                        if (!$product->getId() || $product->getImage() != 'no_selection') {
                                            $picture = Mage::helper('catalog/image')->init($product, 'image');
                                        } else {
                                            $picture = Mage::getDesign()->getSkinUrl('images/giftcard.png',array('_area'=>'frontend'));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
        } catch(Exception $e){
            Mage::logException($e);
        }

         $post = array(
             'amount'        => $this->_addCurrencySymbol($amount,$this->getCardCurrency()),
             'code'          => $this->getCardCode(),
             'email-to'      => $this->getMailTo(),
             'email-from'    => $this->getMailFrom(),
             'recipient'     => $this->getMailToEmail(),
             'email-message' => nl2br($this->getMailMessage()),
             'store-phone'   => Mage::getStoreConfig('general/store_information/phone'),
             'picture'       => $picture,
             );
         if($orderItemToSend){
             $post['picture_link'] = Mage::getBaseUrl().'gift-cert-print?item_id='.$orderItemToSend->getId().'&gift_card_id='.$this->getId();
         }
         $mail = trim($this->getMailToEmail()) ;
         
         if(empty($mail)) {
              $mail = $order->getCustomerEmail() ;
         }

         $this->_send($post, 'giftcards/email/email_template', $mail, $storeId);
    }

    protected function _sendPrintCard($storeId)
    {
        if($order = Mage::getModel('sales/order')->load($this->getOrderId())){
            $storeId = $order->getStoreId();
        } else {
            $storeId = 1;
        }

        $amount = number_format(Mage::app()->getStore($storeId)->convertPrice($this->getCardAmount(), false, false),2);

        if(Mage::helper('giftcards')->isUseDefaultPicture() || !$this->getProductId()) {
           $picture = Mage::getDesign()->getSkinUrl('images/giftcard.png',array('_area'=>'frontend'));
        } else {
            $product = Mage::getModel('catalog/product')->load($this->getProductId());
            if (!$product->getId() || $product->getImage() != 'no_selection') {
                $picture = Mage::helper('catalog/image')->init($product, 'image');
            } else {
                $picture = Mage::getDesign()->getSkinUrl('images/giftcard.png',array('_area'=>'frontend'));
            }
        }

        $post = array(
            'amount'        => $this->_addCurrencySymbol($amount,$this->getCardCurrency()),
            'code'          => $this->getCardCode(),
            'email-to'      => $this->getMailTo(),
            'email-from'    => $this->getMailFrom(),
            'link'          => Mage::app()->getStore($storeId)->getUrl('customer/giftcards/print/') . 'code/' . $this->getCardCode(),
            'email-message' => nl2br($this->getMailMessage()),
            'store-phone'   => Mage::getStoreConfig('general/store_information/phone'),
            'picture'       => $picture,
        );
        $this->_send($post, 'giftcards/email/print_template', $order->getCustomerEmail(), $storeId);
    }

    protected function _sendOfflineCard($storeId)
    {
        if($order = Mage::getModel('sales/order')->load($this->getOrderId())){
            $storeId = $order->getStoreId();
        } else {
            $storeId = 1;
        }

        $amount = number_format(Mage::app()->getStore($storeId)->convertPrice($this->getCardAmount(), false, false),2);

        if(Mage::helper('giftcards')->isUseDefaultPicture() || !$this->getProductId()) {
             $picture = Mage::getDesign()->getSkinUrl('images/giftcard.png',array('_area'=>'frontend'));
        } else {
             $product = Mage::getModel('catalog/product')->load($this->getProductId());
             if (!$product->getId() || $product->getImage() != 'no_selection') {
                 $picture = Mage::helper('catalog/image')->init($product, 'image');
             } else {
                 $picture = Mage::getDesign()->getSkinUrl('images/giftcard.png',array('_area'=>'frontend'));
             }
        }
        $post = array(
            'amount'        => $this->_addCurrencySymbol($amount,$this->getCardCurrency()),
            'code'          => $this->getCardCode(),
            'email-to'      => $this->getMailTo(),
            'email-from'    => $this->getMailFrom(),
            'link'          => Mage::app()->getStore($storeId)->getUrl('customer/giftcards/print/') . 'id/' . $this->getId(),
            'email-message' => nl2br($this->getMailMessage()),
            'store-phone'   => Mage::getStoreConfig('general/store_information/phone'),
            'picture'       => $picture,
        );
        $this->_send($post, 'giftcards/email/offline_template', $order->getCustomerEmail(), $storeId);
    }


    protected function _send($post, $template, $email, $storeId)
    {
        if ($email) {
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $postObject = new Varien_Object();
            $postObject->setData($post);
            $postObject->setStoreId($storeId);
            $mailTemplate = Mage::getModel('core/email_template');
            $pdfGenerator = new Webtex_Giftcards_Model_Email_Pdf();
            //$this->_addAttachment($mailTemplate, $pdfGenerator->getPdf($postObject), 'giftcard.pdf');
            $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
                ->sendTransactional(
                    Mage::getStoreConfig($template, $storeId),
                    'general',
                    $email,
                    null,
                    array('data' => $postObject)
                );
            $translate->setTranslateInline(true);
        } else {
            throw new Exception('Invalid recipient email address.');
        }
    }

    private function _addAttachment($mailTemplate, $file, $filename){
        $attachment = $mailTemplate->getMail()->createAttachment($file);
        $attachment->type = 'application/pdf';
        $attachment->filename = $filename;
    }

    /**
     * Send card email (code, amount etc) to customer
     * TODO: remade this
     *
     * @param null $storeId
     */
    public function send($storeId = null)
    {
        if (!$storeId && $this->getOrderId()) {
            $order = Mage::getModel('sales/order')->load($this->getOrderId());
            $storeId = $order->getStoreId();
        }
        if ($this->getCardType() == 'email') {
            $this->_sendEmailCard($storeId);
        } else if ($this->getCardType() == 'print') {
            $this->_sendPrintCard($storeId);
        } else if ($this->getCardType() == 'offline') {
            $this->_sendOfflineCard($storeId);
        }
        return true;
    }

    public function isValidCode($card)
    {
        $cardCodes = $this->getResourceCollection()->getColumnValues('card_code');
        $code = $card->getCardCode();
        if(in_array($code,$cardCodes) || empty($code)){
           return false;
        }
        return true;
    }

    /**
     * If card is just created, adds card code and set initial balance equal to card amount
     */
    protected function _beforeSave()
    {
        if (!$this->getId()) {
            $code = $this->getCardCode();
            if(!isset($code)) {
                $this->setCardCode($this->getUniqueCardCode());
            }
            $this->setCardBalance($this->getCardAmount());
        }
    }


   public function _addCurrencySymbol($amount, $currencyCode)
   {
        if(empty($currencyCode)) {
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        }
        
        $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();
        if($currencySymbol == '€') {
            $currencySymbol = '&euro;';
        } elseif($currencySymbol == '£') {
            $currencySymbol = '&pound;';
        }
        return $currencySymbol.$amount;
   }
}
