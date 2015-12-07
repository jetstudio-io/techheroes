<?php
class Onibi_StoreLocator_Adminhtml_StoreController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_initAction();
        $id  = $this->getRequest()->getParam('entity_id');
        $model = Mage::getModel('onibi_storelocator/store');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This store no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($model->getId() ? $model->getName() : $this->__('New Store'));
        $data = Mage::getSingleton('adminhtml/session')->getStoreData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        Mage::register('onibi_storelocator', $model);
        $this->_initAction()
            ->_addBreadcrumb($id ? Mage::helper('onibi_storelocator')->__('Edit Store') : Mage::helper('onibi_storelocator')->__('New Store'), $id ? Mage::helper('onibi_storelocator')->__('Edit Store') : Mage::helper('onibi_storelocator')->__('New Store'))
            ->renderLayout();


    }

    public function saveAction()
    {
        if ($postData = $this->getRequest()->getPost()) {
            if(isset($_FILES['image']['name']) and (file_exists($_FILES['image']['tmp_name']))) {
                try {
                    $uploader = new Varien_File_Uploader('image');
                    $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);
                    $locImg = 'storelocator/images/';
                    $path = Mage::getBaseDir('media') . DS . $locImg ;
                    $uploader->save($path, $_FILES['image']['name']);
                    $postData['image'] = $_FILES['image']['name'];
                }catch(Exception $e) {

                }
            }else{
                if(isset($postData['image']['delete']) && $postData['image']['delete'] == 1){
                    $postData['image'] = '';
                }else{
                    unset($postData['image']);
                }
            }
            
            if(isset($_FILES['marker']['name']) and (file_exists($_FILES['marker']['tmp_name']))) {
                try {
                    $uploader = new Varien_File_Uploader('marker');
                    $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);
                    $locMarker = 'storelocator/markers/';
                    $path = Mage::getBaseDir('media') . DS . $locMarker ;
                    $uploader->save($path, $_FILES['marker']['name']);
                    $postData['marker'] = $_FILES['marker']['name'];
                }catch(Exception $e) {
            
                }
            }else{
                if(isset($postData['marker']['delete']) && $postData['marker']['delete'] == 1){
                    $postData['marker'] = '';
                }else{
                    unset($postData['marker']);
                }
            }

            $model = Mage::getSingleton('onibi_storelocator/store');


            if ($id = $this->getRequest()->getParam('entity_id')) {
                $model->load($id);
            }

            $model->setData($postData);

            try {
                if(is_null($model->getCreatedTime()) || $model->getCreatedTime() == ''){
                    $model->setCreatedTime(time());
                }
                $model->setUpdateTime(time());
                //die(var_dump(substr_compare($model->getStoreUrl(), "http://", 0, 7)));
                if(substr_compare($model->getStoreUrl(), "http://", 0, 7) > 0 && substr_compare($model->getStoreUrl(), "https://", 0, 8) > 0){
                    $model->setStoreUrl("http://".$model->getStoreUrl());
                }

                if(!is_null($model->getImage()) && $model->getImage() != ''){
                    $filename = str_replace(" ", "_", $model->getImage());
                    $filename = str_replace(":", "_", $filename);
                    $model->setImage($locImg.$filename);
                }
                
                if(!is_null($model->getMarker()) && $model->getMarker() != ''){
                    $filename = str_replace(" ", "_", $model->getMarker());
                    $filename = str_replace(":", "_", $filename);
                    $model->setMarker($locMarker.$filename);
                }
                
                $model->save();
                
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The store has been saved.'));
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('entity_id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            }
            catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while saving this store.'));
            }

            Mage::getSingleton('adminhtml/session')->setStoreData($postData);
            $this->_redirectReferer();
        }
    }

    public function deleteAction() {
        if( $this->getRequest()->getParam('entity_id') > 0 ) {
            try {
                $model = Mage::getModel('onibi_storelocator/store');

                $model->setId($this->getRequest()->getParam('entity_id'))
                    ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('onibi_storelocator')->__('Store was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $storelocatorIds = $this->getRequest()->getParam('onibi_storelocator');
        if(!is_array($storelocatorIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('onibi_storelocator')->__('Please select store(s)'));
        } else {
            try {
                foreach ($storelocatorIds as $storelocatorId) {
                    $storelocator = Mage::getModel('onibi_storelocator/store')->load($storelocatorId);
                    $storelocator->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('onibi_storelocator')->__(
                        'Total of %d record(s) were successfully deleted', count($storelocatorIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction()
    {
        $storelocatorIds = $this->getRequest()->getParam('onibi_storelocator');
        if(!is_array($storelocatorIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select store(s)'));
        } else {
            try {
                foreach ($storelocatorIds as $storelocatorId) {
                    $storelocator = Mage::getSingleton('onibi_storelocator/store')
                        ->load($storelocatorId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setUpdateTime(time())
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($storelocatorIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function messageAction()
    {
        $data = Mage::getModel('onibi_storelocator/store')->load($this->getRequest()->getParam('entity_id'));
        echo $data->getContent();
    }

    /**
     * Initialize action
     *
     * Here, we set the breadcrumbs and the active menu
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {

        $this->loadLayout()
        // Make the active menu match the menu config nodes (without 'children' inbetween)
            ->_setActiveMenu('onibi/onibi_storelocator')
            ->_title($this->__('Onibi'))->_title($this->__('Store'))
            ->_addBreadcrumb($this->__('Onibi'), $this->__('Onibi'))
            ->_addBreadcrumb($this->__('Store'), $this->__('Store'));
        return $this;
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('onibi/onibi_storelocator');
    }

    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'stores.csv';
        $grid    = $this->getLayout()->createBlock('onibi_storelocator/adminhtml_store_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }
}