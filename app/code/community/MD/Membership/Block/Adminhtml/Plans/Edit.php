<?php
/**
* Magedelight
* Copyright (C) 2015 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category MD
* @package MD_Membership
* @copyright Copyright (c) 2015 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/
class MD_Membership_Block_Adminhtml_Plans_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
       parent::__construct();
                $planId = $this->getRequest()->getParam('id',null);
		$this->_objectId = 'id';
                $plans = Mage::getModel('md_membership/plans');
		$this->_blockGroup = 'md_membership';
		$this->_controller = 'adminhtml_plans';
		$this->_updateButton('save','label',Mage::helper('md_membership')->__('Save'));
		$this->_updateButton('delete','label',Mage::helper('md_membership')->__('Delete'));
		
		$this->_addButton('saveandcontinue',array(
			'label'=>Mage::helper('md_membership')->__('Save and Continue Edit'),
			'onclick'=>'saveAndContinueEdit()',
			'class'=>'save'
		),-100);
            if($planId){
                $this->_addButton('update_subscriptions', array(
                    'label'     => Mage::helper('md_membership')->__('Update Subscriber Data'),
                    'onclick'   => "confirmSetLocation('Are you sure want to fetch information?', '".$this->getUrl('*/*/fetchSubscriptions',array('plan_id'=>$planId))."')",
                ));
            }
		$this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit('".$this->getSaveAndContinueUrl()."');
            }
            Event.observe('start_date_defined','change',function(event){
                var sourceElement = event.findElement();
                $(sourceElement).select('option').each(function(optionElement){
                    
                    switch($(optionElement).value){
                        case '".$plans::DEFINED_DAY_OF_MONTH."':
                                  if($(optionElement).selected == true){
                                        $('day_of_month').writeAttribute(\"class\",\"validate-digits-range digits-range-1-31 input-text\");
                                        $('day_of_month').disabled = false;
                                    }
                                  break;
                        default:
                                if($(optionElement).selected == true){
                                    $('day_of_month').writeAttribute(\"class\",\"input-text disabled\");
                                    $('day_of_month').disabled = true;
                                }
                                break;
                    }
                });
            });
            Event.observe('is_limited','change',function(event){
                var sourceElement = event.findElement();
                $(sourceElement).select('option').each(function(optionElement){
                    switch($(optionElement).value){
                        case '".$plans::SUBSCRIPTION_LIFETIME."':
                                        if($(optionElement).selected == true){
                                            $('total_occurences').writeAttribute(\"class\",\"input-text disabled\");
                                            $('total_occurences').writeAttribute(\"value\",\"0\");
                                            $('total_occurences').disabled = true;
                                        }
                                        break;
                        case '".$plans::SUBSCRIPTION_LIMITED."':
                                        if($(optionElement).selected == true){
                                            $('total_occurences').writeAttribute(\"class\",\"input-text validate-not-negative-number validate-greater-than-zero\");
                                            $('total_occurences').disabled = false;
                                        }
                                        break;
                    }
                });
            });
            Validation.add('validate-mambership-trial-periods', 'Trial occurrences should be less than regular occurrences.', function(v, elm) {
                var totalOccurence = parseInt($('total_occurences').value);
                var trialOccurence = parseInt(v);
                
                if($('is_limited').value == ".MD_Membership_Model_Plans::SUBSCRIPTION_LIMITED." && v != ''){
                    if(totalOccurence > trialOccurence){
                        return true;
                    }else{
                        return false;
                    }
                    
                }
                return true;
            });
        ";	
	}
	
	public function getHeaderText()
	{
		if(Mage::registry('membership_data') && Mage::registry('membership_data')->getId())
		{
                    return Mage::helper('md_membership')->__('Edit Plan %s',$this->htmlEscape(Mage::registry('membership_data')->getTitle()));
                }else{
                    return Mage::helper('md_membership')->__('Add Membership Plan');
		}
	}
        
        public function getSaveAndContinueUrl()
        {
            return $this->getUrl('*/*/save', array(
                '_current'   => true,
                'back'       => 'edit',
                'tab'        => '{{tab_id}}',
                'active_tab' => null
            ));
        }
}

