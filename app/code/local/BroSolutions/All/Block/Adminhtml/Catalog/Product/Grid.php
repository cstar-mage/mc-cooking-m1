<?php
class BroSolutions_All_Block_Adminhtml_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid
{
    protected function _prepareLayout()
    {
        $this->setChild('export_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Export'),
                    'onclick'   => $this->getJsObjectName().'.doExport()',
                    'class'   => 'task'
                ))
        );
        $this->setChild('reset_filter_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Reset Filter'),
                    'onclick'   => $this->getJsObjectName().'.resetFilter()',
                ))
        );
        $this->setChild('search_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Search'),
                    'onclick'   => $this->getJsObjectName().'.doFilter()',
                    'class'   => 'task'
                ))
        );
        $this->setChild('fast_clone_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('catalog')->__('Private Event'),
                    'onclick'   => 'setLocation(\'' . $this->getFastCloneUrl() . '\')',
                    'class'  => 'add'
                ))
        );
        return $this;
    }

    public function getFastCloneUrl()
    {
        return $this->getUrl('*/*/fastclone');
    }

    public function getFastCloneButtonHtml()
    {
        return $this->getChildHtml('fast_clone_button');
    }

    public function getMainButtonsHtml()
    {
        $html = '';
        if($this->getFilterVisibility()){
            $html.= $this->getResetFilterButtonHtml();
            $html.= $this->getSearchButtonHtml();
            $html.= $this->getFastCloneButtonHtml();
        }
        return $html;
    }
}
