<?php
$installer = $this;
$installer->startSetup();
$installer->addAttribute('catalog_product', 'release_interval', array(
    'type'              => 'varchar',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Release Interval (hours)',
    'input'             => 'text',
    'class'             => 'validate-digits validate-greater-than-zero',
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
    'position'          => 100,
    'group'             => 'General',
    'system'            => false,
));
$installer->endSetup();