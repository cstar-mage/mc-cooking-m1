<?php
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();
$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','release_time');
if ($attributeId) {
    $installer->updateAttribute('catalog_product', 'release_time', 'frontend_class', 'validate-delivery-time');
}
$installer->endSetup();