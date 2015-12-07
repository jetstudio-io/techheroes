<?php
class Onibi_StoreLocator_Block_Store extends Mage_Core_Block_Template
{
    public function getDefaultMarker(){
        $defaultMarker = '';
        if(!is_null(Mage::getStoreConfig('storelocator/general/mapicon')) && Mage::getStoreConfig('storelocator/general/mapicon') != ''){
            $defaultMarker = 'storelocator/markers/'.Mage::getStoreConfig('storelocator/general/mapicon');
        }
        return $defaultMarker;
    }
    public function getStores(){
        $stores = Mage::getModel('onibi_storelocator/store')->getCollection()
            ->addFieldToFilter('status',1)
            ->addStoreFilter($this->getCurrentStore())
            ->addFieldToSelect(
                array(
                    'entity_id',
                    'name',
                    'address',
                    'zipcode',
                    'city',
                    'country_id',
                    'phone',
                    'fax',
                    'description',
                    'store_url',
                    'image',
                    'marker',
                    'lat',
                    'long'));

        $storesCollection = new Varien_Data_Collection();
        foreach($stores as $store){
           if(!is_null($store->getCountryId())){
               $store->setCountryId($this->getCountryByCode($store->getCountryId()));
           }else{
               $store->setCountryId($this->__('NC'));
           }

            if(!is_null($store->getImage()) || $store->getImage() != ''){
                $imgUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$store->getImage();
            }elseif (!is_null(Mage::getStoreConfig('storelocator/general/defaultimage')) && Mage::getStoreConfig('storelocator/general/defaultimage') != ''){
                $imgUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'storelocator/images/'.Mage::getStoreConfig('storelocator/general/defaultimage');
            }else{
                $imgUrl = $this->getLogoSrc();
            }
            $store->setImage($imgUrl);
           $storesCollection->addItem($store);
        }
        
        return $storesCollection;
    }

    public function getGoogleApiUrl(){
        $apiUrl = Mage::getStoreConfig('storelocator/general/apiurl');
        if(is_null($apiUrl))
            $apiUrl = "http://maps.googleapis.com/maps/api/js?v=3";
        $apiKey = "&key=".Mage::getStoreConfig('storelocator/general/apikey');
        $apiSensor = Mage::getStoreConfig('storelocator/general/apisensor');
        $sensor = ($apiSensor == 0) ? 'false' : 'true';
        $urlGoogleApi = $apiUrl."&sensor=".$sensor.$apiKey."&callback=initialize&libraries=places";
        
        return $urlGoogleApi;
    }
    
    /**
     * retrieve current store
     *
     * @return Mage_Core_Model_Store
     */
    public function getCurrentStore()
    {
        return Mage::app()->getStore()->getId();
    }

    public function getCountryByCode($code){
        return Mage::getModel('directory/country')->loadByCode($code)->getName();
    }

    public function getLogoSrc()
    {
        if (empty($this->_data['logo_src'])) {
            $this->_data['logo_src'] = Mage::getStoreConfig('design/header/logo_src');
        }
        return $this->getSkinUrl($this->_data['logo_src']);
    }
}