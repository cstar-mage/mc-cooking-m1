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
class MD_Membership_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    public function initControllerRouters($observer)
    {
        $front = $observer->getEvent()->getFront();
        $membership = new MD_Membership_Controller_Router();
        $front->addRouter('md_membership', $membership);

    }
    
    public function match(Zend_Controller_Request_Http $request)
    {
        
        if (!Mage::isInstalled()) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect(Mage::getUrl('install'))
                ->sendResponse();
            exit;
        }
        if(!Mage::getStoreConfig('md_membership/general/enable')){
            return false;
        }
        $identifier = trim($request->getPathInfo(), '/');
        
        $replaceBaseUrl = Mage::helper('md_membership')->getMembershipUrl(true);
        $urlKey = trim(Mage::getStoreConfig("md_membership/membership_list/url_key"),'/');
        $suffix = trim(Mage::getStoreConfig("md_membership/membership_list/url_suffix"),'/');
        $strippedUrlSuffix = str_replace(array($urlKey.'/',$suffix),'',$identifier);
        
        if($identifier === $replaceBaseUrl){
            
            $request->setModuleName('md_membership')
                    ->setControllerName('index')
                    ->setActionName('list');
            return true;
        }else{
            $plan = Mage::getModel('md_membership/plans')->getPlanByUrlKey($strippedUrlSuffix);
            if($plan){
                $request->setModuleName('md_membership')
                    ->setControllerName('index')
                    ->setActionName('view')
                    ->setParam('id',$plan->getId());
            return true;
            }else{
                return false;
            }
        }
        
    }
}

