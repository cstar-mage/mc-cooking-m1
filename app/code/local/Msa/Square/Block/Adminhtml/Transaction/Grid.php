<?php
class Msa_Square_Block_Adminhtml_Transaction_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
    parent::__construct();
    
    $this->setId('transaction_grid');
    $this->setDefaultSort('id');
    $this->setDefaultDir('DESC');
  }
  
  protected function _prepareCollection()
  {
    $collection = Mage::getModel('msa_square/transaction')->getCollection();
    $this->setCollection($collection);
    return parent::_prepareCollection();
  }
  
  protected function _prepareColumns()
  {
    $this->addColumn('id', array (
      'index' => 'id',
      'header' => Mage::helper('msa_square')->__('ID'),
      'type' => 'number',
      'sortable' => true,
      'width' => '100px',
    ));
    
    $this->addColumn('created_at', array (
      'index' => 'created_at',
      'header' => Mage::helper('msa_square')->__('Created At'),
      'sortable' => false,
      'type' => 'datetime',
      'gmtoffset' => false
    ));
    
    $this->addColumn('batch_date', array (
      'index' => 'batch_date',
      'header' => Mage::helper('msa_square')->__('Batch Date'),
      'sortable' => false,
      'type' => 'datetime',
    ));
    
    $this->addColumn('number_of_transactions', array (
      'index' => 'number_of_transactions',
      'header' => Mage::helper('msa_square')->__('Transactions'),
      'type' => 'number',
      'sortable' => true,
      'width' => '100px',
    ));

    return parent::_prepareColumns();
  }
  
  public function getGridUrl()
  {
    return $this->getUrl('*/*/grid', array(
      '_current' => true,
    ));
  }
}