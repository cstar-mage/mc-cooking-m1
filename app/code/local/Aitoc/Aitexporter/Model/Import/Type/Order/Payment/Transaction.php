<?php
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     xwB5BO6b8ADSZdWMZBvj82HAnNdHAZvfJuyG2JiSFP
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
class Aitoc_Aitexporter_Model_Import_Type_Order_Payment_Transaction implements Aitoc_Aitexporter_Model_Import_Type_Interface
{
    /**
     * 
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $config
     * @param string $orderIncrementId
     * @return boolean is valid 
     */
    public function validate(SimpleXMLElement $orderXml, $itemXpath, array $config, $orderIncrementId = '')
    {
        $isValid        = true;
        $transactionXml = current($orderXml->xpath($itemXpath));

        if (!Mage::helper('aitexporter/version')->isPaymentTransactionsExist())
        {
            return $isValid;
        }

        // empty items from CSV 
        if (!trim(strip_tags($transactionXml->asXML())))
        {
            return $isValid;
        }

        return $isValid;
    }

    /**
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $importConfig
     * @param Mage_Sales_Model_Order_Payment $parentItem
     */
    public function import(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem = null)
    {
        /* @var $parentItem Mage_Sales_Model_Order_Payment */
        $transaction = Mage::getModel('sales/order_payment_transaction');
        /* @var $transaction Mage_Sales_Model_Order_Payment_Transaction */
        $transactionXml = current($orderXml->xpath($itemXpath));

        if (!Mage::helper('aitexporter/version')->isPaymentTransactionsExist())
        {
            return false;
        }

        // empty items from CSV 
        if (!trim(strip_tags($transactionXml->asXML())))
        {
            return false;
        }

        foreach ($transactionXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'order_id':
                case 'payment_id':
                    break;

                case 'transaction_id':
                case 'parent_id':
                    $transaction->setData('old_'.$field->getName(), (string)$field);
                    break;

                default:
                    $transaction->setData($field->getName(), (string)$field);
                    break;
            }
        }

        $transactions = $parentItem->getImportTransactions();
        if (empty($transactions))
        {
            $transactions = array();
        }
        $transactions[] = $transaction;
        $parentItem->setImportTransactions($transactions);
    }

    public function getXpath()
    {
        return '/transactions/transaction';
    }

    /**
     * @see Aitoc_Aitexporter_Model_Import_Type_Interface::getErrorType()
     * return boolean
     */
    public function getErrorType()
    {
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    public function postProcess(Mage_Sales_Model_Order $order)
    {
        foreach ($order->getPaymentsCollection() as $payment)
        {
            $transactions = $payment->getImportTransactions();

            if ($transactions)
            {
                $transactionIds = array();
                foreach ($transactions as $transaction)
                {
                    /* @var $transaction Mage_Sales_Model_Order_Payment_Transaction */
                    $transaction
                        ->setOrderPaymentObject($payment)
                        ->setPaymentId($payment->getId())
                        ->setOrderId($order->getId())
                        ->setOldCreatedAt($transaction->getCreatedAt())
                        ->save();
                    $transactionIds[$transaction->getOldTransactionId()] = $transaction->getId();
                }

                foreach ($transactions as $transaction)
                {
                    $transaction->setCreatedAt($transaction->getOldCreatedAt());
                    if ($transaction->getOldParentId() && isset($transactionIds[$transaction->getOldParentId()]))
                    {
                        $transaction->setParentId($transactionIds[$transaction->getOldParentId()]);
                    }
                    $transaction->save();
                }
            }
        }
    }
}