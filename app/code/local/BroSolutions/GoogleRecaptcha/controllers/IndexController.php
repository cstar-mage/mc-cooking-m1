<?php
require_once Mage::getModuleDir('controllers', 'Mage_Contacts') . DS . 'IndexController.php';
class BroSolutions_GoogleRecaptcha_IndexController extends Mage_Contacts_IndexController
{
    const GOOGLE_RECAPTCHA_URL = 'https://www.google.com/recaptcha/api/siteverify';
    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;
                $recaptchaCheckResult = $this->_checkRecaptcha($this->getRequest()->getPost());
                $session = Mage::getSingleton('customer/session');
                if (!$recaptchaCheckResult) {
                    $session->addError(Mage::helper('contacts')->__('Invalid reCAPTCHA.'));                    
                    $this->_redirect('*/*/*/');
                    return;
                }
                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }

                if ($error) {
                    throw new Exception();
                }
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                $this->_redirect('*/*/');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
                $this->_redirect('*/*/');
                return;
            }

        } else {
            $this->_redirect('*/*/');
        }
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
}

