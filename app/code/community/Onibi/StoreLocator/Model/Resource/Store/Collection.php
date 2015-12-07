<?php
class Onibi_StoreLocator_Model_Resource_Store_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Load data for preview flag
     *
     * @var bool
     */
    protected $_previewFlag;


    protected function _construct()
    {
        $this->_init('onibi_storelocator/store');
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
        $this->_map['fields']['storelocator_id']   = 'onibi_storelocator_stores.storelocator_id';
    }


    /**
     * Set first store flag
     *
     * @param bool $flag
     */
    public function setFirstStoreFlag($flag = false)
    {
        $this->_previewFlag = $flag;
        return $this;
    }

    /**
     * deprecated after 1.4.0.1, use toOptionIdArray()
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('identifier', 'title');
    }

    /**
     * Returns pairs identifier - title for unique identifiers
     * and pairs identifier|page_id - title for non-unique after first
     *
     * @return array
     */
    public function toOptionIdArray()
    {
        $res = array();
        $existingIdentifiers = array();
        foreach ($this as $item) {
            $identifier = $item->getData('identifier');

            $data['value'] = $identifier;
            $data['label'] = $item->getData('title');

            if (in_array($identifier, $existingIdentifiers)) {
                $data['value'] .= '|' . $item->getData('storelocator_id');
            } else {
                $existingIdentifiers[] = $identifier;
            }

            $res[] = $data;
        }

        return $res;
    }


    /**
     * Perform operations after collection load
     *
     */
    protected function _afterLoad()
    {
        if ($this->_previewFlag) {
            $items = $this->getColumnValues('entity_id');
            $connection = $this->getConnection();
            if (count($items)) {
                $select = $connection->select()
                    ->from(array('onibi_storelocator_stores'=>$this->getTable('onibi_storelocator/stores')))
                    ->where('onibi_storelocator_stores.storelocator_id IN (?)', $items);

                if ($result = $connection->fetchPairs($select)) {
                    foreach ($this as $item) {
                        if (!isset($result[$item->getData('entity_id')])) {
                            continue;
                        }
                        if ($result[$item->getData('entity_id')] == 0) {
                            $stores = Mage::app()->getStores(false, true);
                            $storeId = current($stores)->getId();
                            $storeCode = key($stores);
                        } else {
                            $storeId = $result[$item->getData('entity_id')];
                            $storeCode = Mage::app()->getStore($storeId)->getCode();
                        }

                        $item->setData('_first_store_id', $storeId);
                        $item->setData('store_code', $storeCode);
                    }
                }
            }
        }

        return parent::_afterLoad();
    }

    /**
     * Join store relation table if there is store filter
     */
    protected function _renderFiltersBefore()
    {
        if ($this->getFilter('store_id')) {
            $this->getSelect()->join(
                array('onibi_storelocator_stores' => $this->getTable('onibi_storelocator/stores')),
                'main_table.entity_id = onibi_storelocator_stores.storelocator_id',
                array()
            )->group('main_table.entity_id');

            /*
             * Allow analytic functions usage because of one field grouping
             */
            $this->_useAnalyticFunction = true;
        }
        return parent::_renderFiltersBefore();
    }

    /**
     * Add filter by store
     *
     * @param int|Mage_Core_Model_Store $store
     * @param bool $withAdmin
     */

    public function addStoreFilter($store, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            if ($store instanceof Mage_Core_Model_Store) {
                $store = array($store->getId());
            }

            if (!is_array($store)) {
                $store = array($store);
            }

            if ($withAdmin) {
                $store[] = Mage_Core_Model_App::ADMIN_STORE_ID;
            }

            $this->addFilter('store_id', array('in' => $store), 'public');

        }

        return $this;
    }


}