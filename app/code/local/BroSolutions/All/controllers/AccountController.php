<?php
require_once 'Mage'.DS.'Customer'.DS.'controllers'.DS.'AccountController.php';

class BroSolutions_All_AccountController extends Mage_Customer_AccountController
{
    const GOOGLE_RECAPTCHA_URL = 'https://www.google.com/recaptcha/api/siteverify';
    public function createPostAction()
    {
        $errUrl = $this->_getUrl('*/*/create', array('_secure' => true));

        if (!$this->_validateFormKey()) {
            $this->_redirectError($errUrl);
            return;
        }

        /** @var $session Mage_Customer_Model_Session */
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $recaptchaCheckResult = $this->_checkRecaptcha($this->getRequest()->getPost());

        if (!$recaptchaCheckResult) {
            $session->addError(Mage::helper('contacts')->__('Invalid reCAPTCHA.'));                    
            $this->_redirect('*/*/*/');
            return;
        }

        if (!$this->getRequest()->isPost()) {
            $this->_redirectError($errUrl);
            return;
        }

        $customer = $this->_getCustomer();

        try {
            $errors = $this->_getCustomerErrors($customer);

            if (empty($errors)) {
                $customer->cleanPasswordsValidationData();
                $customer->save();
                $this->_dispatchRegisterSuccess($customer);
                $this->_successProcessRegistration($customer);
                return;
            } else {
                $this->_addSessionError($errors);
            }
        } catch (Mage_Core_Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost());
            if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                $url = $this->_getUrl('customer/account/forgotpassword');
                $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
            } else {
                $message = $this->_escapeHtml($e->getMessage());
            }
            $session->addError($message);
        } catch (Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost());
            $session->addException($e, $this->__('Cannot save the customer.'));
        }

        $this->_redirectError($errUrl);
    }

    protected function _checkRecaptcha($post)
    {
        $secretKey = $this->_getSecretKey();
        $websiteKey = $this->getWebsiteKey();
        if(empty($secretKey) || empty($websiteKey)){
            return true;
        }

        if(isset($post['g-recaptcha-response'])){
            $post = array(
                'secret' => $this->_getSecretKey(),
                'response' => $post['g-recaptcha-response'],
            );

            try {
                $ch = curl_init(self::GOOGLE_RECAPTCHA_URL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);


                $response = curl_exec($ch);
                curl_close($ch);
                $isJson = $this->_isJson($response);

                if($isJson){
                    $responceDecoded = json_decode($response, true);
                    if(isset($responceDecoded['success']) && $responceDecoded['success']){
                        return true;
                    }
                }

            } catch(Exception $e){
                var_dump($e); die;
            }
        }
        return false;
    }
    protected function _isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    protected function _getSecretKey()
    {
        $store = Mage::app()->getStore();
        $code  = $store->getCode();
        $secretKey = Mage::getStoreConfig('bsgooglerecaptchasection/general/secretkey', $code);
        return $secretKey;
    }

    protected function getWebsiteKey()
    {
        $store = Mage::app()->getStore();
        $code  = $store->getCode();
        $secretKey = Mage::getStoreConfig('bsgooglerecaptchasection/general/websitekey', $code);
        return $secretKey;
    }
    protected function _loginPostRedirect()
    {
        $session = $this->_getSession();
        if($session->getWasRedirectedFromCheckout()){
            $this->_redirect('checkout/onepage/index');
            $session->setWasRedirectedFromCheckout(false);
            return;
        }
        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl()) {
            // Set default URL to redirect customer to
            $session->setBeforeAuthUrl($this->_getHelper('customer')->getAccountUrl());
            // Redirect customer to the last page visited after logging in
            if ($session->isLoggedIn()) {
                if (!Mage::getStoreConfigFlag(
                    Mage_Customer_Helper_Data::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD
                )) {
                    $referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME);
                    if ($referer) {
                        // Rebuild referer URL to handle the case when SID was changed
                        $referer = $this->_getModel('core/url')
                            ->getRebuiltUrl( $this->_getHelper('core')->urlDecodeAndEscape($referer));
                        if ($this->_isUrlInternal($referer)) {
                            $session->setBeforeAuthUrl($referer);
                        }
                    }
                } else if ($session->getAfterAuthUrl()) {
                    $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
                }
            } else {
                $session->setBeforeAuthUrl( $this->_getHelper('customer')->getLoginUrl());
            }
        } else if ($session->getBeforeAuthUrl() ==  $this->_getHelper('customer')->getLogoutUrl()) {
            $session->setBeforeAuthUrl( $this->_getHelper('customer')->getDashboardUrl());
        } else {
            if (!$session->getAfterAuthUrl()) {
                $session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }
        $this->_redirectUrl($session->getBeforeAuthUrl(true));
    }
}
