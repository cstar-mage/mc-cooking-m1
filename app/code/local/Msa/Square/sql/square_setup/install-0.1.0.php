<?php
/**
 * @category    Msa
 * @package     Msa_square
 * @copyright   Copyright (c) 2017 MainStreet America, LLC
 * @author		José Blanco
 * 
 * This file creates the initial table
 */

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()->newTable($installer->getTable('msa_square/transaction'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Created at')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Updated at')
    ->addColumn('batch_date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable'  => false,
        ), 'Batch date')
    ->addColumn('number_of_transactions', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
        'unsigned'  => true,
        ), 'Number of Transactions')
    ->setComment('MSA Square Transactions');

$installer->getConnection()->createTable($table);

$table = $installer->getConnection()->newTable($installer->getTable('msa_square/item'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'id')
    ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        ), 'Transaction ID')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Created at')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Updated at')
    ->addColumn('transaction_date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable'  => false,
        ), 'Transaction Date')
    ->addColumn('square_id', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Square Transaction ID')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Item Name')
    ->addColumn('category', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'Item Category')
    ->addColumn('amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '10,2', array(
        'nullable'  => false,
        ), 'Amount')
    ->setComment('MSA Square Line Items');

$installer->getConnection()->createTable($table);

$installer->endSetup(); 

?>