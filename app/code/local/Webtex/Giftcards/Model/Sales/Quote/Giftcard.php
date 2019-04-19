<?php
/**
 *
 *
 *
 *
 **/

class Webtex_Giftcards_Model_Sales_Quote_Giftcard extends Mage_Sales_Model_Quote_Address_Total_Discount
//Abstract
{
    protected $_session;
    protected $_cards;
    protected $_cardsBalance;
    protected $_cardsBaseBalance;
    protected $_cardCodes = array();
    protected $_cardIds = array();

    public function __construct(){
        $this->setCode('discount');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $items = $address->getAllItems();
        foreach($items as $item){
            $item->setDiscountAmount(0);
            $item->setBaseDiscountAmount(0);
            $address->setShippingDiscountAmount(0);
            $address->setBaseShippingDiscountAmount(0);
        }

        parent::collect($address);
        $this->_session = Mage::getSingleton('giftcards/session');
        $this->_cards = $this->_session->getCards();
        $this->_cardCodes = array();

        if(!$this->_session || !$this->_session->getActive()){
            return $this;
        }

        $this->_resetCards();
        $quote = $address->getQuote();
        $totalDiscountAmount = 0;
        $subtotalWithDiscount = 0;
        $baseTotalDiscountAmount = 0;
        $baseSubtotalWithDiscount = 0;

        foreach($this->_cards as $_id => $_card) {
            $shippingDiscountAmount     = (float) $address->getShippingDiscountAmount();
            $baseShippingDiscountAmount = (float) $address->getBaseShippingDiscountAmount();

            if($shippingDiscountAmount < $address->getShippingAmount()){
                $applyDiscountAmount = min($address->getShippingAmount() - $shippingDiscountAmount, $_card['card_balance']);
                $shippingDiscountAmount += $applyDiscountAmount;
                $_card['card_balance'] -= $applyDiscountAmount;

                $baseApplyDiscountAmount = min($address->getBaseShippingAmount() - $baseShippingDiscountAmount, $_card['base_card_balance']);
                $baseShippingDiscountAmount += $baseApplyDiscountAmount;
                $_card['base_card_balance'] -= $baseApplyDiscountAmount;

                $address->setShippingDiscountAmount($shippingDiscountAmount);
                $address->setBaseShippingDiscountAmount($baseShippingDiscountAmount);
            }


            foreach($items as $item){
                if($item->getParentItemId()){
                    continue;
                }

                if($item->hasChildren() && $item->isChildrenCalculated()){
                    foreach($item->getChildren() as $child){
                        $childDiscountAmount = (float) $child->getDiscountAmount();
                        $childBaseDiscountAmount = (float) $child->getBaseDiscountAmount();

                        if(($child->getRowTotal() - $childDiscountAmount) > 0){
                            $applyDiscountAmount = min(($child->getRowTotal() - $childDiscountAmount), $_card['card_balance']);
                            $childDiscountAmount += $applyDiscountAmount;
                            $_card['card_balance'] -= $applyDiscountAmount;

                            $baseApplyDiscountAmount = min(($child->getBaseRowTotal() - $childBaseDiscountAmount), $_card['base_card_balance']);
                            $childBaseDiscountAmount += $baseApplyDiscountAmount;
                            $_card['base_card_balance'] -= $baseApplyDiscountAmount;

                            $totalDiscountAmount += $childDiscountAmount;
                            $baseTotalDiscountAmount += $childBaseDiscountAmount;

                            $child->setDiscountAmount($applyDiscountAmount);
                            $child->setBaseDiscountAmount($baseApplyDiscountAmount);

                            $subtotalWithDiscount += ($child->getRowTotal() - $child->getDiscountAmount());
                            $baseSubtotalWithDiscount += ($child->getBaseRowTotal() - $child->getBaseDiscountAmount());
                        }

                    }
                } else {
                    $itemDiscountAmount = (float) $item->getDiscountAmount();
                    $itemBaseDiscountAmount = (float) $item->getBaseDiscountAmount();
                    if(($item->getRowTotal()-$itemDiscountAmount) > 0){
                        $applyDiscountAmount = min(($item->getRowTotal()-$itemDiscountAmount), $_card['card_balance']);
                        $itemDiscountAmount += $applyDiscountAmount;
                        $_card['card_balance'] -= $applyDiscountAmount;

                        $baseApplyDiscountAmount = min(($item->getBaseRowTotal()-$itemBaseDiscountAmount), $_card['base_card_balance']);
                        $itemBaseDiscountAmount += $baseApplyDiscountAmount;
                        $_card['base_card_balance'] -= $baseApplyDiscountAmount;

                        $totalDiscountAmount += $applyDiscountAmount;
                        $baseTotalDiscountAmount += $baseApplyDiscountAmount;

                        $item->setDiscountAmount($itemDiscountAmount);
                        $item->setBaseDiscountAmount($itemBaseDiscountAmount);

                        $subtotalWithDiscount += ($item->getRowTotal() - $itemDiscountAmount);
                        $baseSubtotalWithDiscount += ($item->getBaseRowTotal() - $itemBaseDiscountAmount);
                    }
                }

            }

            $this->_cardCodes[] = $_card['card_code'];
            $this->_cards[$_id] = $_card;
        }
        $address->setDiscountAmount(-$totalDiscountAmount - $shippingDiscountAmount);
        $address->setBaseDiscountAmount(-$baseTotalDiscountAmount - $baseShippingDiscountAmount);

        $address->setGrandTotal($address->getGrandTotal() + $address->getDiscountAmount());
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBaseDiscountAmount());
        $this->_session->setCards($this->_cards);
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if(!$this->_session || !$this->_session->getActive()){
            return parent::fetch($address);
        }
        $amount = $address->getDiscountAmount();
        if($amount != 0){
            $title = $address->getDiscountDescription();
            $codes  = implode(",", $this->_cardCodes);
            if(strlen($codes) && !strpos($title, 'Cards')){
                $title .= Mage::helper('giftcards')->__(' Gift Cards (%s)', $codes);
            }
            $address->setDiscountDescription($title);
            $address->addTotal(array(
                'code'  => 'discount',
                'title' => $title,
                'value' => $amount,
            ));
        }
        return $this;
    }

    private function _resetCards()
    {
        $_cardModel = Mage::getModel('giftcards/giftcards');
        foreach($this->_cards as $_id => $_card) {
            $card = $_cardModel->load($_id);
            $this->_cards[$_id] =  array('card_code' => $card->getCardCode(),
                'card_balance' => $card->getCurrentBalance(),
                'base_card_balance' => $card->getBaseBalance(),
                'original_card_balance' => $card->getCurrentBalance(),
                'original_base_card_balance' => $card->getBaseBalance());

        }
        return;
    }

    public function getLabel()
    {
        return Mage::helper('giftcards')->__('Gift Card');
    }
}