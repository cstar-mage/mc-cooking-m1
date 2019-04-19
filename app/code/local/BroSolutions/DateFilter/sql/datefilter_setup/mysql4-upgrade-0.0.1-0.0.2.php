<?php
$installer = $this;
$installer->startSetup();
$installer->addAttribute('catalog_product', 'release_month', array(
    'type'                       => 'int',
    'label'                      => 'Release Month',
    'input'                      => 'text',
    'required'                   => false,
    'visible'                    => true,
    'searchable'                 => false,
    'filterable'                 => true,
    'comparable'                 => false,
    'used_for_sort_by'           => true,
    'user_defined'               => false,
    'is_configurable'            => false,
    'visible_on_front'           => true,
    'visible_in_advanced_search' => false,
    'used_in_product_listing'    => true,
    'group'                      => 'General',
    'system'                     => false,
));
$installer->endSetup();