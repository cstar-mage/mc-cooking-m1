<?php 
class Msa_Square_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
	 * Called from a CRON job
     * Retrieves Square Transactions
	 *
	 * @return void
	 * @author José Blanco 
     * @link https://docs.connect.squareup.com/api/connect/v1/
	 */
     public function getSquareTransactions() 
     {
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);
   
        $authorization = Mage::getStoreConfig('msa/square_settings/square_api_token');
        $location_id   = Mage::getStoreConfig('msa/square_settings/square_api_location');
        $begin_time    = '2017-01-01T06:00:00Z';   // Initial value to populate empty table
        $end_time      = str_replace('+00:00', 'Z', gmdate('c'));
        $end_time_sql  = date('Y-m-d H:i:s');

        $square_transactions = Mage::getModel('msa_square/transaction')
            ->getCollection()
            ->getLastItem();

        if (!is_null($square_transactions->getId())) {
            $begin_time = str_replace(' ', 'T', $square_transactions->getBatchDate());
        }

        // Square UP URL for API v1        
        $url = "https://connect.squareup.com/v1/" . $location_id . "/payments?begin_time=" . $begin_time . "&end_time=" . $end_time;
        
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $authorization,
                'Accept: application/json',
                'Content-Type: application/json'
            ));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $server_output = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), Zend_Log::ERR);
        }
        
        $payments = json_decode($server_output);

        if (is_array($payments) && count($payments) > 0) {
            $st = Mage::getModel('msa_square/transaction');
            // $st->setBatchDate($end_time_sql);
            $st->setNumberOfTransactions(count($payments));
            $st->setCreatedAt(Mage::getModel('core/date')->gmtDate());
            $st->setUpdatedAt(Mage::getModel('core/date')->gmtDate());

            foreach($payments as $payment) {
                if ($this->isDuplicatedTransaction($payment->id))
                {
                    continue;
                }
                
                // Transaction dates are saved with local time to 
                // make it easy on reporting outside Magento
                $trans_date = new DateTime(str_replace('T', '', str_replace('Z', '', $payment->created_at)), new DateTimeZone('UTC'));
                $trans_date->setTimeZone(new DateTimeZone(Mage::getStoreConfig('general/locale/timezone')));
                $st->setBatchDate($trans_date->format('Y-m-d H:i:s'));
                $st->save();
                
                

                if ($payment->tip_money->amount/100 > 0) {
                    $square_payment = Mage::getModel('msa_square/item');
                    $square_payment->setTransactionId($st->getId());
                    $square_payment->setSquareId($payment->id);
                    $square_payment->setCategory('Tip');
                    $square_payment->setAmount($payment->tip_money->amount/100);                
                    $square_payment->setTransactionDate($trans_date->format('Y-m-d H:i:s'));
                    $square_payment->setName('Tip');
                    $square_payment->setCreatedAt(Mage::getModel('core/date')->gmtDate());
                    $square_payment->setUpdatedAt(Mage::getModel('core/date')->gmtDate());
                    $square_payment->save();
                }

                foreach($payment->itemizations as $item) {
                    $square_payment = Mage::getModel('msa_square/item');
                    $square_payment->setTransactionId($st->getId());
                    $square_payment->setSquareId($payment->id);
                    $square_payment->setCategory($item->item_detail->category_name);
                    $square_payment->setAmount($item->total_money->amount/100);                
                    $square_payment->setTransactionDate($trans_date->format('Y-m-d H:i:s'));
                    $square_payment->setName($item->name);
                    $square_payment->setCreatedAt(Mage::getModel('core/date')->gmtDate());
                    $square_payment->setUpdatedAt(Mage::getModel('core/date')->gmtDate());
                    $square_payment->setTaxes($item->taxes[0]->applied_money->amount/100);
                    $square_payment->save();
                }
            }
        } 
     }
    
    private function isDuplicatedTransaction($trans_id)
    {
        $items = Mage::getModel('msa_square/item')
            ->getCollection()
            ->addFieldToFilter('square_id', array('eq' => $trans_id));
        
        if (count($items) > 0)
        {
            return true;
        }
        
        return false;
    }
}