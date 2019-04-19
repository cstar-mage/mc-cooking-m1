<?php

class MW_Storecreditpro_Block_Adminhtml_Customer_Edit_Tab_Storecredit_History_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('storecreditpro_Grid');
        $this->setDefaultSort('history_id');
        $this->setDefaultDir('desc');

        $this->setUseAjax(true);
        $this->setEmptyText(Mage::helper('storecreditpro')->__('No Transaction Found'));
    }

	public function getGridUrl()
    {
        return $this->getUrl('adminhtml/storecreditpro_customer/historyGrid', array('_current' => true));
    }
	public function getRowUrl($row)
    {
        return '';
    }

	protected function _prepareCollection()
  	{
  		$collection = Mage::getResourceModel('storecreditpro/history_collection')
           		->addFieldToFilter('customer_id',$this->getCustomerId());
      
      	$this->setCollection($collection);
      	return parent::_prepareCollection();
  	}
  	protected function _prepareColumns()
  	{
  		$this->addColumn('history_id', array(
            'header'    =>  Mage::helper('storecreditpro')->__('ID'),
            'align'     =>  'left',
            'index'     =>  'history_id',
            'width'     =>  10
        ));
        $this->addColumn('transaction_time', array(
            'header'    =>  Mage::helper('storecreditpro')->__('Transaction Time'),
            'type'      =>  'datetime',
            'align'     =>  'center',
            'index'     =>  'transaction_time'
            
        ));
        $this->addColumn('amount', array(
            'header'    =>  Mage::helper('storecreditpro')->__('Amount'),
            'align'     =>  'left a-right',
            'index'     =>  'amount',
        	'type'      =>  'number',
        	'renderer'  => 'storecreditpro/adminhtml_renderer_amount',
        ));

        $this->addColumn('balance', array(
            'header'    =>  Mage::helper('storecreditpro')->__('Customer Balance'),
            'align'     =>  'left a-right',
            'index'     =>  'balance',
        	'type'      =>  'number',
            'renderer'  => 'storecreditpro/adminhtml_renderer_balance',
        ));
        $this->addColumn('transaction_detail', array(
            'header'    =>  Mage::helper('storecreditpro')->__('Transaction Details'),
            'align'     =>  'left',
        	'width'		=>  400,
            'index'     =>  'transaction_detail',
        	'renderer'  => 'storecreditpro/adminhtml_renderer_transaction',
        ));
      	 $this->addColumn('status', array(
          	'header'    => Mage::helper('storecreditpro')->__('Status'),
          	'align'     =>'center',
          	'index'     => 'status',
		  	'type'      => 'options',
          	'options'   => Mage::getSingleton('storecreditpro/statushistory')->getOptionArray(),
      	));
      	
      	return parent::_prepareColumns();
  	}

}
