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
class Aitoc_Aitexporter_Model_System_Config_Source_Cron
{
    const FREQ_10MIN = '*/10 * * * *';//'10min';
    const FREQ_20MIN = '*/20 * * * *';//'20min';
    const FREQ_30MIN = '*/30 * * * *';//'30min';
    const FREQ_1HR   = '0 * * * *';//'1hr';
    const FREQ_2HRS  = '0 */2 * * *';//'2hrs';
    const FREQ_4HRS  = '0 */4 * * *';//'4hrs';
    const FREQ_8HRS  = '0 */8 * * *';//'8hrs';
    const FREQ_12HRS = '0 */12 * * *';//'12hrs';
    const FREQ_1D    = '0 1 * * *';//'1d';

    public function toOptionArray()
    {
        $helper  = Mage::helper('aitexporter');
        $options = array(
            array('value' => '', 'label' => 'Disable'),
            array('value' => self::FREQ_10MIN, 'label' => $helper->__('%d minutes', 10)),
            array('value' => self::FREQ_20MIN, 'label' => $helper->__('%d minutes', 20)),
            array('value' => self::FREQ_30MIN, 'label' => $helper->__('%d minutes', 30)),
            array('value' => self::FREQ_1HR, 'label' => $helper->__('1 hour')),
            array('value' => self::FREQ_2HRS, 'label' => $helper->__('%d hours', 2)),
            array('value' => self::FREQ_4HRS, 'label' => $helper->__('%d hours', 4)),
            array('value' => self::FREQ_8HRS, 'label' => $helper->__('%d hours', 8)),
            array('value' => self::FREQ_12HRS, 'label' => $helper->__('%d hours', 12)),
            array('value' => self::FREQ_1D, 'label' => $helper->__('1 day')),
            );

        return $options;
    }

    public function toStrToTimeArray()
    {
        $options = array(
            self::FREQ_10MIN    => '10 minutes',
            self::FREQ_20MIN    => '20 minutes',
            self::FREQ_30MIN    => '30 minutes',
            self::FREQ_1HR      => '1 hour',
            self::FREQ_2HRS     => '2 hours',
            self::FREQ_4HRS     => '4 hours',
            self::FREQ_8HRS     => '8 hours',
            self::FREQ_12HRS    => '12 hours',
            self::FREQ_1D       => '1 day',
        );

        return $options;
    }

}