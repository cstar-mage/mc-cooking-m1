<?php
$installer = $this;
$installer->startSetup();
$installer->addAttribute('catalog_product', 'cancel_reason', array(
    'type'              => 'int',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Cancel Reason',
    'input'             => 'select',
    'source'            => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => true,
    'default'           => '',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => true,
    'unique'            => false,
    'position'          => 110,
    'group'             => 'General',
    'system'            => false,
    'option' =>
        array (
            'values' =>
                array (
                    0 => 'Reason 0',
                    1 => 'Reason 1',
                    2 => 'Reason 2',
                    3 => 'Reason 3',
                ),
        ),
));
$installer->endSetup();