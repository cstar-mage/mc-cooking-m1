<?php
$installer = $this;
$installer->startSetup();
$installer->addAttribute('catalog_product', 'release_date', array(
    'type'                       => 'datetime',
    'label'                      => 'Release Date',
    'input'                      => 'datetime',
    'backend'                    => 'eav/entity_attribute_backend_datetime',
    'required'                   => false,
    'visible'                    => true,
    'searchable'                 => false,
    'filterable'                 => true,
    'comparable'                 => false,
    'used_for_sort_by'           => true,
    'is_configurable'            => false,
    'visible_on_front'           => true,
    'visible_in_advanced_search' => false,
    'used_in_product_listing'    => true,
    'group'                      => 'General',
    'system'                     => false,
));
$installer->endSetup();