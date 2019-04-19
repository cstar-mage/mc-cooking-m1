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
class MD_Membership_Model_Payment_Paypal_Express extends MD_Membership_Model_Payment_Abstract
{
    const EXPRESS_CHECKOUT_APIUSERNAME = 'paypal/wpp/api_username';
    const EXPRESS_CHECKOUT_APIPASSWORD = 'paypal/wpp/api_password';
    const EXPRESS_CHECKOUT_APISIGNATURE = 'paypal/wpp/api_signature';
    const EXPRESS_CHECKOUT_TESTMODE = 'paypal/wpp/sandbox_flag';
    
    protected $_periodMap = array(
        MD_Membership_Model_Plans::BILLING_PERIOD_DAILY=>array('period'=>'Day','frequency'=>'1'),
        MD_Membership_Model_Plans::BILLING_PERIOD_MONTHLY=>array('period'=>'Month','frequency'=>'1'),
        MD_Membership_Model_Plans::BILLING_PERIOD_WEEKLY=>array('period'=>'Week','frequency'=>'1'),
        MD_Membership_Model_Plans::BILLING_PERIOD_QUARTERLY=>array('period'=>'Month','frequency'=>'3'),
        MD_Membership_Model_Plans::BILLING_PERIOD_BIMONTHLY=>array('period'=>'SemiMonth','frequency'=>'1'),
        MD_Membership_Model_Plans::BILLING_PERIOD_YEARLY=>array('period'=>'Year','frequency'=>'1'),
    );
    
    protected $_statusMap = array(
        'Active'=>1,
        'Expired'=>2,
        'Suspended'=>3,
        'Cancelled'=>4,
        'Pending'=>5,
    );
    
    protected $_dependsAction = array(
        4 => array('name'=>'Cancel','depend_status'=>array(MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_ACTIVE,MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_SUSPENDED)),
        3 => array('name'=>'Suspend','depend_status'=>array(MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_ACTIVE)),
        1 => array('name'=>'Reactivate','depend_status'=>array(MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_SUSPENDED)),
    );
    
    
    public function pay()
    {
        
    }
    
    public function callSetExpressCheckoutMethod()
    {
        $request = array();
        $debugData = array();
        $successUrl = Mage::getUrl('md_membership/index/success',array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
        $cancelUrl = Mage::getUrl('md_membership/index/cancel',array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
        $plan = Mage::getModel('md_membership/plans')->load($this->getMembershipPlanId());
        $storeId= Mage::app()->getStore()->getId();
        $sandbox_flag = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_TESTMODE, $storeId);
        $request['USER'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIUSERNAME,$storeId);
        $request['PWD'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIPASSWORD,$storeId);
        $request['SIGNATURE'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APISIGNATURE,$storeId);
        $request['METHOD'] = 'SetExpressCheckout';
        $request['VERSION'] = '86';
        $request['LOCALECODE'] = Mage::app()->getLocale()->getLocaleCode();
        $request['L_BILLINGTYPE0'] = 'RecurringPayments';
        $request['L_BILLINGAGREEMENTDESCRIPTION0'] = substr($plan->getTitle(),0,125);
        $request['L_PAYMENTTYPE0'] = 'InstantOnly';
        //$request['L_PAYMENTREQUEST_0_ITEMCATEGORY0'] = "Digital";
        $request['L_PAYMENTREQUEST_0_NAME0'] = $plan->getTitle();
        $request['L_PAYMENTREQUEST_0_AMT0'] = number_format($plan->getAmount(),2);
        $request['L_PAYMENTREQUEST_0_NUMBER0'] = $plan->getId();
        $request['L_PAYMENTREQUEST_0_QTY0'] = 1;
        $request['L_PAYMENTREQUEST_0_TAXAMT0'] = number_format(0,2);
        $request['L_PAYMENTREQUEST_0_ITEMURL0'] = $plan->getPlanUrl();
        $request['L_PAYMENTREQUEST_0_NAME1'] = Mage::helper('md_membership')->__('Initial Fee');
        $request['L_PAYMENTREQUEST_0_AMT1'] = number_format($plan->getInitialAmount(),2);
       
        $request['PAYMENTREQUEST_0_AMT'] = number_format($plan->getAmount() + $plan->getInitialAmount(),2);
        $request['PAYMENTREQUEST_0_ITEMAMT'] = number_format($plan->getAmount() + $plan->getInitialAmount(),2);
        $request['PAYMENTREQUEST_0_SHIPPINGAMT'] = number_format(0,2);
        $request['PAYMENTREQUEST_0_HANDLINGAMT'] = number_format(0,2);
        $request['PAYMENTREQUEST_0_CURRENCYCODE'] = Mage::app()->getStore()->getCurrentCurrencyCode();
        $request['PAYMENTREQUEST_0_TAXAMT'] = number_format(0,2);
        $request['PAYMENTREQUEST_0_INVNUM'] = Mage::getModel('md_membership/subscribers')->getReservedIncrementId();
        $request['cancelUrl'] = $cancelUrl;
        $request['returnUrl'] = $successUrl;
        $request['TOTALTYPE'] = 'Total';
        $request['SOLUTIONTYPE'] = 'Sole';
        
        $apiUrl = 'https://api-3t.paypal.com/nvp';
        if($sandbox_flag == 1){
              $apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';
            }
        $debugData['SetExpressCheckout']['request'] = $request;

        $response = $this->_postRequest($request, $apiUrl);
        return $response;
    }
    
    public function prepareSetExpressCheckoutMethodRequest()
    {
        
    }
    
    public function getExpressCheckoutDetails($token)
    {
        $request = array();
        $debugData = array();
        $storeId= Mage::app()->getStore()->getId();
        $sandbox_flag = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_TESTMODE, $storeId);
        $request['USER'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIUSERNAME,$storeId);
        $request['PWD'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIPASSWORD,$storeId);
        $request['SIGNATURE'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APISIGNATURE,$storeId);
        $request['METHOD'] = 'GetExpressCheckoutDetails';
        $request['VERSION'] = '86';
        $request['TOKEN'] = $token;
        $apiUrl = 'https://api-3t.paypal.com/nvp';
        if($sandbox_flag == 1){
              $apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';
            }
            $debugData['GetExpressCheckoutDetails']['request'] = $request;
        $response = $this->_postRequest($request, $apiUrl);
        return $response;
    }
    
    protected function _buildRequest($plan,$customer,$billingAddress = null)
    {
        
    }
    
    protected function _postRequest($request,$apiUrl)
    {
        $error = array();
        try {
            $http = new Varien_Http_Adapter_Curl();
            $config = array(
                'timeout'    => 60,
                'verifypeer' => false
            );
            $http->setConfig($config);
            $http->write(
                Zend_Http_Client::POST,
                $apiUrl,
                '1.1',
                array(),
                http_build_query($request)
            );
            $response = $http->read();
        }catch (Exception $e) {
            $error['error']=$e->getMessage();
        }
        
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);
        $response = $this->_deformatNVP($response);
        Mage::log($response,false,'request.log');
        $result = $this->_processResponse($response);
        
        return $result;
    }
    
    protected function _deformatNVP($nvpstr)
    {
        $intial=0;
        $nvpArray = array();

        $nvpstr = strpos($nvpstr, "\r\n\r\n")!==false ? substr($nvpstr, strpos($nvpstr, "\r\n\r\n")+4) : $nvpstr;

        while(strlen($nvpstr)) {
            //postion of Key
            $keypos= strpos($nvpstr,'=');
            //position of value
            $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

            /*getting the Key and Value values and storing in a Associative Array*/
            $keyval=substr($nvpstr,$intial,$keypos);
            $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
            //decoding the respose
            $nvpArray[urldecode($keyval)] =urldecode( $valval);
            $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
         }
        return $nvpArray;
    }
    
    protected function _processResponse($response)
    {
        $result = array();
        $debug = array();
        if(count($response) > 0){
           if(!array_key_exists('PAYERID',$response) && array_key_exists('TOKEN',$response) && array_key_exists('ACK',$response) && $response['ACK'] == 'Success') 
           {
               $storeId= Mage::app()->getStore()->getId();
               $sandbox_flag = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_TESTMODE, $storeId);
               $keyword = ($sandbox_flag == 1) ? 'sandbox.': '';
               $debug['SetExpressCheckout']['response'] = $response;
               $result['redirect_url'] = Mage::getUrl('md_membership/index/expressRedirect');
               $result['express_checkout_post_url'] = 'https://www.'.$keyword.'paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$response['TOKEN'];
           }elseif(array_key_exists('PAYERID',$response) && array_key_exists('ACK',$response) && $response['ACK'] == 'Success'){
                $debug['GetExpressCheckoutDetails']['response'] = $response;
                $result['token'] = $response['TOKEN'];
                $result['payer_id'] = $response['PAYERID'];
                $result['billing_agreement_accepted'] = $response['BILLINGAGREEMENTACCEPTEDSTATUS'];
            }elseif(array_key_exists('PROFILEID',$response) && array_key_exists('ACK',$response) && $response['ACK'] == 'Success'){
                $debug['CreateRecurringPaymentsProfile']['response'] = $response;
                $result['profile_id'] = $response['PROFILEID'];
                $result['profile_status'] = $response['PROFILESTATUS'];
            }else{
                $debug[0]['response'] = $response;
               $result['error'] = $response['L_LONGMESSAGE0'];
           }
        }else{
            $debug[0]['response'] = $response;
            $result['error'] = $response['L_LONGMESSAGE0'];
        }
        
        return $result;
    }
    
    public function requestRecurringProfile($token,$payerId){
        
        $request = array();
        $debugData = array();
        $planId = $this->getMembershipPlanId();
        $plan = Mage::getModel('md_membership/plans')->load($planId);
        $addressId = $this->getBillingAddressId();
        $startDate = ($this->getSubscriptionStartDate()) ? $this->getSubscriptionStartDate(): $plan->getProfileStartDate();
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $addressObj = ($addressId > 0) ?$customer->getAddressById($addressId) : null;
        $storeId= Mage::app()->getStore()->getId();
        $sandbox_flag = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_TESTMODE, $storeId);
        $request['USER'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIUSERNAME,$storeId);
        $request['PWD'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIPASSWORD,$storeId);
        $request['SIGNATURE'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APISIGNATURE,$storeId);
        $request['METHOD'] = 'CreateRecurringPaymentsProfile';
        $request['VERSION'] = '86';
        $request['TOKEN'] = $token;
        $request['PAYERID'] = $payerId;
        $request['PROFILEREFERENCE'] = Mage::getModel('md_membership/subscribers')->getReservedIncrementId();
        $request['PROFILESTARTDATE'] = Mage::getSingleton('core/date')->gmtDate('Y-m-d\TH:i:s\Z',$startDate);//date('Y-m-d',strtotime($startDate)).'T05:38:48Z';
        $request['DESC'] = substr($plan->getTitle(),0,125);
        $request['BILLINGPERIOD'] = $this->_periodMap[$plan->getBillingPeriod()]['period'];
        $request['BILLINGFREQUENCY'] = $this->_periodMap[$plan->getBillingPeriod()]['frequency'];
        $request['AMT'] = number_format($plan->getAmount(),2);
        $request['CURRENCYCODE'] = Mage::app()->getStore()->getCurrentCurrencyCode();
        if($addressObj){
            $request['COUNTRYCODE'] = $addressObj->getCountryId();
            $request['STREET'] = $addressObj->getStreet(1);
            $request['STREET2'] = $addressObj->getStreet(2);
            $request['CITY'] = $addressObj->getCity();
            $request['STATE'] = $addressObj->getRegionCode();
            $request['ZIP'] = $addressObj->getPostcode();
        }
        $request['MAXFAILEDPAYMENTS'] = $plan->getMaxFailedPayment();
        $request['SUBSCRIBERNAME'] = $customer->getFirstname().' '.$customer->getLastname();
        $request['TOTALBILLINGCYCLES'] = ($plan->getIsLimited()) ? $plan->getTotalOccurences() : 0;;
        if($plan->getTrialPeriodCount() > 0 && $plan->getTrialAmount() > 0){
        $request['TRIALBILLINGPERIOD'] = $this->_periodMap[$plan->getBillingPeriod()]['period'];
        $request['TRIALBILLINGFREQUENCY'] = $this->_periodMap[$plan->getBillingPeriod()]['frequency'];
        $request['TRIALTOTALBILLINGCYCLES'] = $plan->getTrialPeriodCount();
        $request['TRIALAMT'] = number_format($plan->getTrialAmount(),2);
        }
        $request['SHIPPINGAMT'] = number_format(0,2);
        $request['TAXAMT'] = number_format(0,2);
        if($plan->getInitialAmount() > 0){
            $request['INITAMT'] = number_format($plan->getInitialAmount(),2);
        }
        $request['FAILEDINITAMTACTION'] = 'ContinueOnFailure';
        $request['AUTOBILLOUTAMT'] = 'AddToNextBilling';
        $request['EMAIL'] = $customer->getEmail();
        $request['FIRSTNAME'] = $customer->getFirstname();
        $request['LASTNAME'] = $customer->getLastname();
        //$request['L_PAYMENTREQUEST_n_ITEMCATEGORY0'] = "Digital";
        $request['L_PAYMENTREQUEST_n_NAME0'] = $plan->getTitle();
        $request['L_PAYMENTREQUEST_n_AMT0'] = number_format($plan->getAmount(),2);
        $request['L_PAYMENTREQUEST_n_NUMBER0'] = $plan->getId();
        $request['L_PAYMENTREQUEST_n_QTY0'] = 1;
        $request['L_PAYMENTREQUEST_n_TAXAMT0'] = number_format(0,2);
        
        $apiUrl = 'https://api-3t.paypal.com/nvp';
        if($sandbox_flag == 1){
              $apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';
            }
        $debugData['CreateRecurringPaymentsProfile']['request'] = $request;
        Mage::log($request,false,'request.log');
        $response = $this->_postRequest($request, $apiUrl);
        $response['plan_id'] = $plan->getId();
        $response['customer_id'] = $customer->getId();
        $response['customer_address_id'] = $addressId;
        $response['name'] = $customer->getFirstname().' '.$customer->getLastname();
        $response['email'] = $customer->getEmail();
        $response['group_id'] = $customer->getGroupId();
        if($addressObj){
            $response['telephone'] = $addressObj->getTelephone();
            $response['postcode'] = $addressObj->getPostcode();
            $response['region'] = $addressObj->getRegionCode();
            $response['country_id'] = $addressObj->getCountryId();
        }
        $response['payment_method'] = Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS;
        $response['status'] = ($response['profile_status'] != 'ActiveProfile') ? 0: 1;
        $response['profile_start_date'] = date("Y-m-d",strtotime($startDate));
        return $response;
    }
    
    public function getSubscriptionStatus()
    {
        $request = array();
        $subscription = $this->getSubscriber();
        $storeId= $this->getStoreId();
        $sandbox_flag = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_TESTMODE, $storeId);
        $request['USER'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIUSERNAME,$storeId);
        $request['PWD'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIPASSWORD,$storeId);
        $request['SIGNATURE'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APISIGNATURE,$storeId);
        $request['METHOD'] = 'GetRecurringPaymentsProfileDetails';
        $request['VERSION'] = '86';
        $request['PROFILEID'] = $subscription->getProfileId();
        $apiUrl = 'https://api-3t.paypal.com/nvp';
        if($sandbox_flag == 1){
              $apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';
            }
        $response = $this->_postGetProfileDataRequest($request, $apiUrl);
        if(count($response) > 0){
            $response['subscriber_id'] = $this->getSubscriber()->getId();
            $response['reference_id'] = $this->getSubscriber()->getReferenceId();
        }
        return $response;
    }
    
    protected function _postGetProfileDataRequest($request, $apiUrl){
        $error = array();
        try {
            $http = new Varien_Http_Adapter_Curl();
            $config = array(
                'timeout'    => 60,
                'verifypeer' => false
            );
            $http->setConfig($config);
            $http->write(
                Zend_Http_Client::POST,
                $apiUrl,
                '1.1',
                array(),
                http_build_query($request)
            );
            $response = $http->read();
        }catch (Exception $e) {
            $error['error']=$e->getMessage();
        }
        
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);
        $response = $this->_deformatNVP($response);
        Mage::dispatchEvent('md_membership_paypal_express_response',array('paypal_response'=>$response,'subscriber'=>$this->getSubscriber()));
        $processedREsponse = $this->_prepareStatusResponse($response);
        return $processedREsponse;
    }
    
    protected function _prepareStatusResponse($response)
    {
        $result = array();
        $debug = array();
        if(count($response) > 0){
            if(array_key_exists('PROFILEID',$response) && array_key_exists('ACK',$response) && $response['ACK'] == 'Success'){
                $result['profile_id'] = $response['PROFILEID'];
                $result['status'] = $this->_statusMap[$response['STATUS']];
                $result['next_billing_date'] = date("Y-m-d H:i:s",strtotime($response['NEXTBILLINGDATE']));
                $result['last_payment_date'] = date("Y-m-d H:i:s",strtotime($response['LASTPAYMENTDATE']));
                $result['billing_cycles_completed'] = $response['NUMCYCLESCOMPLETED'];
                $result['billing_cycles_remains'] = $response['NUMCYCLESREMAINING'];
                $result['regular_billing_cycles'] = $response['REGULARTOTALBILLINGCYCLES'];
                $result['trial_billing_cycles'] = $response['TRIALTOTALBILLINGCYCLES'];
                $result['trial_billing_cycles'] = $response['TRIALTOTALBILLINGCYCLES'];
                $result['payment_failed_count'] = $response['FAILEDPAYMENTCOUNT'];
                $result['final_payment_date'] = date("Y-m-d H:i:s",strtotime($response['FINALPAYMENTDUEDATE']));
            }
        }
        return $result;
    }
    
    public function updateProfile($action)
    {
        $actionName = $this->_dependsAction[$action]['name'];
        $request = array();
        $response = array();
        $subscription = $this->getSubscriber();
        
        $currentSubscriptionStatus = $subscription->getStatus();
        $dependentStatus = $this->_dependsAction[$action]['depend_status'];
        $canUpdateProfile = (in_array($currentSubscriptionStatus,$dependentStatus)) ? true:false;
        if($canUpdateProfile){
            $storeId= $this->getStoreId();
            $sandbox_flag = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_TESTMODE, $storeId);
            $request['USER'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIUSERNAME,$storeId);
            $request['PWD'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APIPASSWORD,$storeId);
            $request['SIGNATURE'] = Mage::getStoreConfig(self::EXPRESS_CHECKOUT_APISIGNATURE,$storeId);
            $request['METHOD'] = 'ManageRecurringPaymentsProfileStatus';
            $request['VERSION'] = '86';
            $request['PROFILEID'] = $subscription->getProfileId();
            $request['ACTION'] = $actionName;
            $apiUrl = 'https://api-3t.paypal.com/nvp';
            if($sandbox_flag == 1){
              $apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';
            }
            
            $response = $this->_sendManageRecurringPaymentsProfileStatusRequest($request, $apiUrl, $action);
            if(count($response) > 0){
                $response['subscriber_id'] = $subscription->getId();
            }
        }
        return $response;
    }
    
    protected function _sendManageRecurringPaymentsProfileStatusRequest($request, $apiUrl, $action)
    {
        $error = array();
        try {
            $http = new Varien_Http_Adapter_Curl();
            $config = array(
                'timeout'    => 60,
                'verifypeer' => false
            );
            $http->setConfig($config);
            $http->write(
                Zend_Http_Client::POST,
                $apiUrl,
                '1.1',
                array(),
                http_build_query($request)
            );
            $response = $http->read();
        }catch (Exception $e) {
            $error['error']=$e->getMessage();
        }
        
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);
        $response = $this->_deformatNVP($response);
        Mage::log($response,false,'response.log');
        $processedResponse = $this->_prepareManageStatusResponse($response, $action);
        return $processedResponse;
    }
    
    protected function _prepareManageStatusResponse($response,$status){
        $result = array();
        if(count($response) > 0){
            if(array_key_exists('PROFILEID',$response) && array_key_exists('ACK',$response) && $response['ACK'] == 'Success'){
                $result['profile_id'] = $response['PROFILEID'];
                $result['status'] = $status;
            }elseif($response['ACK'] == 'Failure'){
                $result['error'] = sprintf('%s %s',$response['L_SHORTMESSAGE0'],$response['L_LONGMESSAGE0']);
            }
        }
        return $result;
    }
}

