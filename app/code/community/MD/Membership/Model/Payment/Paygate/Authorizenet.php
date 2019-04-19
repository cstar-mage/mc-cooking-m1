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
class MD_Membership_Model_Payment_Paygate_Authorizenet extends MD_Membership_Model_Payment_Abstract
{
    const AUTHORIZE_API_LOGIN_ID = 'payment/authorizenet/login';
    const AUTHORIZE_TRANS_KEY = 'payment/authorizenet/trans_key';
    const AUTHORIZE_TEST_MODEL = 'payment/authorizenet/test';
    const AUTHORIZE_PAYMENT_ACTION = 'payment/authorizenet/payment_action';
    
    protected $_statusMap = array(
        'active'=>1,
        'expired'=>2,
        'suspended'=>3,
        'canceled'=>4,
        'terminated'=>5,
    );
    
    protected $_periodMap = array(
        //MD_Membership_Model_Plans::BILLING_PERIOD_DAILY=>array('period'=>'Day','frequency'=>'1'),
        MD_Membership_Model_Plans::BILLING_PERIOD_MONTHLY=>array('period'=>'months','frequency'=>'1'),
        MD_Membership_Model_Plans::BILLING_PERIOD_WEEKLY=>array('period'=>'days','frequency'=>'7'),
        MD_Membership_Model_Plans::BILLING_PERIOD_QUARTERLY=>array('period'=>'months','frequency'=>'3'),
        MD_Membership_Model_Plans::BILLING_PERIOD_BIMONTHLY=>array('period'=>'days','frequency'=>'15'),
        MD_Membership_Model_Plans::BILLING_PERIOD_YEARLY=>array('period'=>'months','frequency'=>'12'),
    );
    
    protected $_dependsAction = array(
        4 => array('name'=>'Cancel','depend_status'=>array(MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_ACTIVE,MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_SUSPENDED)),
    );
    
    public function getApiLoginId()
    {
        return Mage::getStoreConfig(self::AUTHORIZE_API_LOGIN_ID,$this->getStoreId());
    }
    
    public function getApiUrl(){
        $apiUrl = 'https://api.authorize.net/xml/v1/request.api';
        if($this->getIsTestMode()){
            $apiUrl = 'https://apitest.authorize.net/xml/v1/request.api';
        }
        return $apiUrl;
    }
    
    public function getTransKey()
    {
        return Mage::getStoreConfig(self::AUTHORIZE_TRANS_KEY,$this->getStoreId());
    }
    
    public function getIsTestMode()
    {
        return (boolean)Mage::getStoreConfig(self::AUTHORIZE_TEST_MODEL,$this->getStoreId());
    }
    
    public function getPaymentAction()
    {
        $action = Mage::getStoreConfig(self::AUTHORIZE_PAYMENT_ACTION,$this->getStoreId());
        $convertedActio = '';
        switch($action){
            case 'authorize':
                            $convertedAction = Mage_Paygate_Model_Authorizenet::REQUEST_TYPE_AUTH_ONLY;
                            break;
            case 'authorize_capture':
                                    $convertedAction = Mage_Paygate_Model_Authorizenet::REQUEST_TYPE_AUTH_CAPTURE;
                                    break;
        }
        return $convertedActio;
    }
    
    protected function _captureInitialAmount($amount, $cardDetails, MD_Membership_Model_Plans $plan, $customer, $address = null)
    {
        $initialResponse = true;
        $request = new Varien_Object();
        $nextReservedId = Mage::getModel('md_membership/subscribers')->getReservedIncrementId();
        $request->setXVersion(3.1)->setXDelimData('True')->setXRelayResponse('False')->setXTestRequest(($this->getIsTestMode()) ? 'TRUE' : 'FALSE')
                ->setXLogin($this->getApiLoginId())->setXTranKey($this->getTransKey())->setXType(Mage_Paygate_Model_Authorizenet::REQUEST_TYPE_AUTH_CAPTURE)
                ->setXMethod(Mage_Paygate_Model_Authorizenet::REQUEST_METHOD_CC)->setXInvoiceNum($nextReservedId)->setXAmount(number_format($amount,2,'.',''))
                ->setXCurrencyCode('USD')->setXRecurringBilling('True');
        
        if(!is_null($address)){
            $request->setXFirstName($customer->getFirstname())->setXLastName($customer->getLastname())->setXCompany($address->getCompany())
                    ->setXAddress($address->getStreet(1))->setXCity($address->getCity())->setXState($address->getRegion())
                    ->setXZip($address->getPostcode())->setXCountry($address->getCountry())->setXPhone($address->getTelephone())
                    ->setXFax($address->getFax())->setXCustId($customer->getCustomerId())->setXEmail($customer->getEmail())->setXEmailCustomer('TRUE');
                    //->setXMerchantEmail($methodObject->getConfigData('merchant_email'));
        }
        if(isset($cardDetails['cc_number'])){
            $request->setXCardNum($cardDetails['cc_number'])->setXExpDate(sprintf('%02d-%04d', $cardDetails['cc_exp_month'], $cardDetails['cc_exp_year']));
            if(isset($cardDetails['cc_cid']) && strlen($cardDetails['cc_cid']) > 0){    
                $request->setXCardCode($cardDetails['cc_cid']);
            }
        }
        $lineItem = sprintf('%s<|>%s<|>%s<|>%s<|>%.2f<|>%s',$nextReservedId,$plan->getTitle(),$plan->getTitle(),'1',$plan->getAmount(),'FALSE');
        $request->setXLineItem($lineItem);
        
        $result = new Varien_Object();
        $client = new Varien_Http_Client();
        $uri = ($this->getIsTestMode()) ? 'https://test.authorize.net/gateway/transact.dll' : 'https://secure.authorize.net/gateway/transact.dll';
        $client->setUri($uri);
        $client->setConfig(array(
            'maxredirects'=>0,
            'timeout'=>60,
            //'ssltransport' => 'tcp',
        ));
        foreach ($request->getData() as $key => $value) {
            $request->setData($key, str_replace(Mage_Paygate_Model_Authorizenet::RESPONSE_DELIM_CHAR, '', $value));
        }
        $request->setXDelimChar(Mage_Paygate_Model_Authorizenet::RESPONSE_DELIM_CHAR);
        $client->setParameterPost($request->getData());
        $client->setMethod(Zend_Http_Client::POST);
        try {
            $response = $client->request();
        }catch (Exception $e) {
            $result->setResponseCode(-1)
                ->setResponseReasonCode($e->getCode())
                ->setResponseReasonText($e->getMessage());
            
            $initialResponse = array();
            $initialResponse['error'] = $e->getMessage();
        }
        $responseBody = $response->getBody();
        $r = explode(Mage_Paygate_Model_Authorizenet::RESPONSE_DELIM_CHAR, $responseBody);
        if ($r) {
            $result->setResponseCode((int)str_replace('"','',$r[0]))->setResponseSubcode((int)str_replace('"','',$r[1]))->setResponseReasonCode((int)str_replace('"','',$r[2]))
                ->setResponseReasonText($r[3])->setApprovalCode($r[4])->setAvsResultCode($r[5])
                ->setTransactionId($r[6])->setInvoiceNumber($r[7])->setDescription($r[8])->setAmount($r[9])
                ->setMethod($r[10])->setTransactionType($r[11])->setCustomerId($r[12])->setMd5Hash($r[37])->setCardCodeResponseCode($r[38])
                ->setCAVVResponseCode( (isset($r[39])) ? $r[39] : null)->setSplitTenderId($r[52])->setAccNumber($r[50])->setCardType($r[51])->setRequestedAmount($r[53])
                ->setBalanceOnCard($r[54]);
            
            if(in_array($result->getResponseCode(),array(2,3,4))){
                
                $initialResponse = array();
                $initialResponse['error'] = $result->getResponseReasonText();
            }else{
                
                $initialResponse= true;
            }
        }
        else {
           
            $initialResponse = array();
            $initialResponse['error'] = Mage::helper('paygate')->__('Error in payment gateway.');
        }
        
        return $initialResponse;
    }
    
    public function pay()
    {
        $planId = $this->getMembershipPlanId();
        $plan = Mage::getModel('md_membership/plans')->load($planId);
        $addressId = $this->getBillingAddressId();
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $addressObj = ($addressId > 0) ?$customer->getAddressById($addressId) : null;
        $startDate = ($this->getSubscriptionStartDate()) ? $this->getSubscriptionStartDate(): $plan->getProfileStartDate();
        $cardDetails = $this->getCardDetails();
        $initialPaymentResponse = true;
        if($plan->getInitialAmount() > 0){
            $initialPaymentResponse = $this->_captureInitialAmount($plan->getInitialAmount(),$cardDetails,$plan,$customer,$addressObj);
        }
        if(!is_array($initialPaymentResponse)){
            $request = $this->_callCreateSubscriptionRequest($plan,$customer,$addressObj,$startDate,$cardDetails);
            $response = $this->_sendCreateSubscriptionRequest($request,$this->getApiUrl());
            $response['plan_id'] = $plan->getId();
            $response['redirect_url'] = $plan->getPlanUrl();
            $response['plan_title'] = $plan->getTitle();
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
            $response['payment_method'] = Mage_Paygate_Model_Authorizenet::METHOD_CODE;
            $response['status'] = ($response['resultCode'] != 'Ok') ? 0: 1;
            $response['profile_start_date'] = date("Y-m-d",strtotime($startDate));
            return $response;
        }else{
            return $initialPaymentResponse;
        }
    }
    
    protected function _callCreateSubscriptionRequest($plan,$customer,$address = null,$startDate,$cardDetails = array())
    {
        $trialOccurences = (int)$plan->getTrialPeriodCount();
        $totalOccurences = (int)$plan->getTotalOccurences() + $trialOccurences;
        if(!$plan->getIsLimited()){
           $totalOccurences = 9999; 
        }
        
        $string = '<?xml version="1.0" encoding="utf-8"?><ARBCreateSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $string .= '<merchantAuthentication><name>'.$this->getApiLoginId().'</name><transactionKey>'.$this->getTransKey().'</transactionKey></merchantAuthentication>';
        $string .= '<refId>'.Mage::getModel('md_membership/subscribers')->getReservedIncrementId().'</refId>';
        $string .= '<subscription>';
        $string .= '<name>'.$plan->getTitle().'</name>';
        $string .= '<paymentSchedule>';
        $string .= '<interval><length>'.$this->_periodMap[$plan->getBillingPeriod()]['frequency'].'</length><unit>'.$this->_periodMap[$plan->getBillingPeriod()]['period'].'</unit></interval><startDate>'.date("Y-m-d",strtotime($startDate)).'</startDate><totalOccurrences>'.$totalOccurences.'</totalOccurrences>';
        if($trialOccurences > 0){
            $string .= '<trialOccurrences>'.$trialOccurences.'</trialOccurrences>';
        }
        $string .= '</paymentSchedule>';
        $string .= '<amount>'.number_format($plan->getAmount(),2,'.','').'</amount>';
        if($trialOccurences > 0){
            $string .= '<trialAmount>'.number_format($plan->getTrialAmount(),2,'.','').'</trialAmount>';
        }
        $string .= '<payment>';
        $string .= '<creditCard>';
        $string .= '<cardNumber>'.$cardDetails['cc_number'].'</cardNumber><expirationDate>'.sprintf('%04d-%02d', $cardDetails['cc_exp_year'], $cardDetails['cc_exp_month']).'</expirationDate>';
        if(isset($cardDetails['cc_cid']) && strlen($cardDetails['cc_cid']) > 0){
            $string .= '<cardCode>'.$cardDetails['cc_cid'].'</cardCode>';
        }
        $string .= '</creditCard>';
        $string .= '</payment>';
        $string .= '<customer>';
        $string .= '<id>'.$customer->getId().'</id><email>'.$customer->getEmail().'</email>';
        $string .= '</customer>';
        if($address){
        $string .= '<billTo>';
        $string .= '<firstName>'.$address->getFirstname().'</firstName><lastName>'.$address->getLastname().'</lastName>';
        if($address->getCompany()){
            $string .= '<company>'.$address->getCompany().'</company>';
        }
        $string .= '<address>'.$address->getStreetFull().'</address><city>'.$address->getCity().'</city><state>'.$address->getRegionCode().'</state><zip>'.$address->getPostcode().'</zip><country>'.$address->getCountryId().'</country>';
        $string .= '</billTo>';
        }else{
           $string .= '<billTo>';
            $string .= '<firstName>'.$customer->getFirstname().'</firstName><lastName>'.$customer->getLastname().'</lastName>'; 
            $string .= '</billTo>';
        }
        $string .= '</subscription>';
        $string .= '</ARBCreateSubscriptionRequest>';
        return $string;
    }
    
    protected function _sendCreateSubscriptionRequest($request, $apiUrl){
        $error = array();
        $response = array();
        $xmlToArrayResponse = array();
        try {
            $http = new Varien_Http_Adapter_Curl();
            $config = array(
                'timeout'    => 60,
                'verifypeer' => false,
                'header'=>false,
            );
            
            $http->setConfig($config);
            $http->setOptions(CURLOPT_HEADER,false);
            $http->write(
                Zend_Http_Client::POST,
                $apiUrl,
                '1.1',
                array('Content-Type: application/xml'),
                $request
            );
            $response = $http->read();
            $http->close();
            $xml = new Varien_Simplexml_Element($response);
            $xmlToArrayResponse = $xml->asArray();
        }catch (Exception $e) {
            $xmlToArrayResponse = array();
            $xmlToArrayResponse['messages']['resultCode'] = 'Error';
            $xmlToArrayResponse['messages']['message']['text'] = $e->getMessage();
        }
        $processedResponse = $this->_processCreateSubscriptionResponse($xmlToArrayResponse);
        return $processedResponse;
    }
    
    protected function _processCreateSubscriptionResponse($response)
    {
        
        $messages = $response['messages'];
        $result = array();
        if(array_key_exists('subscriptionId',$response) && array_key_exists('resultCode',$messages) && $messages['resultCode'] == 'Ok'){
            if(array_key_exists('refID',$response)){
                $result['reference_id'] = $response['refID'];
            }
            $result['profile_id'] = $response['subscriptionId'];
            $result['resultCode'] = $messages['resultCode'];
        }else{
            $result['error'] = $messages['message']['text'];
        }
        return $result;
    }
    
    protected function _callGetSubscriptionStatusRequest()
    {
        $subscriber = $this->getSubscriber();
        
        $string = '<?xml version="1.0" encoding="utf-8"?><ARBGetSubscriptionStatusRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $string .= '<merchantAuthentication><name>'.$this->getApiLoginId().'</name><transactionKey>'.$this->getTransKey().'</transactionKey></merchantAuthentication>';
        $string .= '<refId>'.$subscriber->getReferenceId().'</refId>';
        $string .= '<subscriptionId>'.$subscriber->getProfileId().'</subscriptionId>';
        $string .= '</ARBGetSubscriptionStatusRequest>';
        return $string;
    }
    
    public function getSubscriptionStatus()
    {
        $request = $this->_callGetSubscriptionStatusRequest();
        $response = $this->_sendGetSubscriptionStatusRequest($request,$this->getApiUrl());
        if(count($response) > 0){
            $response['subscriber_id'] = $this->getSubscriber()->getId();
        }
        return $response;
    }
    
    protected function _sendGetSubscriptionStatusRequest($request, $apiUrl){
        $error = array();
        $response = array();
        $xmlToArrayResponse = array();
        try {
            $http = new Varien_Http_Adapter_Curl();
            $config = array(
                'timeout'    => 60,
                'verifypeer' => false,
                'header'=>false,
            );
            
            $http->setConfig($config);
            $http->write(
                Zend_Http_Client::POST,
                $apiUrl,
                '1.1',
                array('Content-Type: application/xml'),
                $request
            );
            $response = $http->read();
            
            $http->close();
            $xml = new Varien_Simplexml_Element($response);
            $xmlToArrayResponse = $xml->asArray();
        }catch (Exception $e) {
            $xmlToArrayResponse = array();
            $xmlToArrayResponse['messages']['resultCode'] = 'Error';
            $xmlToArrayResponse['messages']['message']['text'] = $e->getMessage();
            
        }
        $processedResponse = $this->_processGetSubscriptionStatusResponse($xmlToArrayResponse);
        return $processedResponse;
    }
    
    protected function _processGetSubscriptionStatusResponse($response)
    {
        
        $result = array();
        
        $messages = $response['messages'];
        if($messages['resultCode'] == 'Ok' || array_key_exists('status',$response) || array_key_exists('Status',$response))
        {
            $result['reference_id'] = $response['refId'];
            $result['status'] = $this->_statusMap[$response['status']];
            if(isset($response['Status']) && strlen($response['Status']) > 0){
                $result['status'] = $this->_statusMap[$response['Status']];
            }
            $result['next_billing_date'] = null;
            $result['last_payment_date'] = null;
            $result['billing_cycles_completed'] = null;
            $result['billing_cycles_remains'] = null;
            $result['regular_billing_cycles'] = null;
            $result['trial_billing_cycles'] = null;
            $result['payment_failed_count'] = null;
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
            $request = $this->_callCancelSubscriptionRequest();
            
            $response = $this->_sendCancelSubscriptionRequest($request,$this->getApiUrl());
            if(count($response) > 0){
                $response['subscriber_id'] = $subscription->getId();
                $response['profile_id'] = $subscription->getProfileId();
                $response['status'] = MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_CANCELLED;
            }
        }
        return $response;
    }
    
    protected function _callCancelSubscriptionRequest(){
        $subscriber = $this->getSubscriber();
        $string = '<ARBCancelSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                        <merchantAuthentication>
                            <name>'.$this->getApiLoginId().'</name>
                            <transactionKey>'.$this->getTransKey().'</transactionKey>
                        </merchantAuthentication>
                        <refId>'.$subscriber->getReferenceId().'</refId>
                        <subscriptionId>'.$subscriber->getProfileId().'</subscriptionId>
                    </ARBCancelSubscriptionRequest>';
        return $string;
    }
    
    protected function _sendCancelSubscriptionRequest($request, $apiUrl)
    {
        $error = array();
        $response = array();
        $xmlToArrayResponse = array();
        try {
            $http = new Varien_Http_Adapter_Curl();
            $config = array(
                'timeout'    => 60,
                'verifypeer' => false,
                'header'=>false,
            );
            
            $http->setConfig($config);
            $http->write(
                Zend_Http_Client::POST,
                $apiUrl,
                '1.1',
                array('Content-Type: application/xml'),
                $request
            );
            $response = $http->read();
            
            $http->close();
            $xml = new Varien_Simplexml_Element($response);
            $xmlToArrayResponse = $xml->asArray();
        }catch (Exception $e) {
            $xmlToArrayResponse = array();
            $xmlToArrayResponse['messages']['resultCode'] = 'Error';
            $xmlToArrayResponse['messages']['message']['text'] = $e->getMessage();
        }
        $processedResponse = $this->_processCancelSubscriptionResponse($xmlToArrayResponse);
        return $processedResponse;
    }
    
    protected function _processCancelSubscriptionResponse($response)
    {
        $result = array();
        $messages = $response['messages'];
        if($messages['resultCode'] == 'Ok' || array_key_exists('status',$response) || array_key_exists('Status',$response))
        {
            $result['reference_id'] = $response['refId'];
            $result['status'] = $this->_statusMap[$response['status']];
            if(isset($response['Status']) && strlen($response['Status']) > 0){
                $result['status'] = $this->_statusMap[$response['Status']];
            }
            $result['next_billing_date'] = null;
            $result['last_payment_date'] = null;
            $result['billing_cycles_completed'] = null;
            $result['billing_cycles_remains'] = null;
            $result['regular_billing_cycles'] = null;
            $result['trial_billing_cycles'] = null;
            $result['payment_failed_count'] = null;
        }elseif($messages['resultCode'] == 'Error'){
            $result['error'] = $messages['message']['text'];
        }
        return $result;
    }
    
    public function reActivateProfile()
    {
        
    }
}

