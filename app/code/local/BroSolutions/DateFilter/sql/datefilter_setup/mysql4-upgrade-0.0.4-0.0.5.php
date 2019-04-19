<?php
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();
$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','release_time');
if ($attributeId) {
    $installer->updateAttribute('catalog_product', 'release_time', 'html_allowed_on_front', 1);
    $installer->updateAttribute('catalog_product', 'release_time', 'visible_on_front', 1);
    $installer->updateAttribute('catalog_product', 'release_time', 'visible', 1);
    $installer->updateAttribute('catalog_product', 'release_time', 'global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL);

}
$installer->endSetup();