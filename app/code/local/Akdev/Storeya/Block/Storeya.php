<?php
class Akdev_Storeya_Block_Storeya extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getStoreya()     
     { 
        if (!$this->hasData('storeya')) {
            $this->setData('storeya', Mage::registry('storeya'));
        }
        return $this->getData('storeya');
        
    }
}