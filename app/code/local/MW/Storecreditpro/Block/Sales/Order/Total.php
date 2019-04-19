<?php
class MW_Storecreditpro_Block_Sales_Order_Total extends Mage_Core_Block_Template
{
    /**
     * Get label cell tag properties
     *
     * @return string
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * Get order store object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Get totals source object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get value cell tag properties
     *
     * @return string
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    public function initTotals()
    {
    	
 		$total = new Varien_Object(array(
            'code'      => $this->getNameInLayout(),
            'block_name'=> $this->getNameInLayout(),
            'area'      => $this->getArea()
        ));
        
       
        $after = $this->getAfterTotal();
        if (!$after) {
            $after = 'discount';
        }

        $this->getParentBlock()->addTotal($total, $after);
    	

        return $this;
    }
}
