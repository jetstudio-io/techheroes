<?php
class Akdev_Storeya_IndexController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/storeya?id=15 
    	 *  or
    	 * http://site.com/storeya/id/15 	
    	 */
    	/* 
		$storeya_id = $this->getRequest()->getParam('id');

  		if($storeya_id != null && $storeya_id != '')	{
			$storeya = Mage::getModel('storeya/storeya')->load($storeya_id)->getData();
		} else {
			$storeya = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($storeya == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$storeyaTable = $resource->getTableName('storeya');
			
			$select = $read->select()
			   ->from($storeyaTable,array('storeya_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$storeya = $read->fetchRow($select);
		}
		Mage::register('storeya', $storeya);
		*/

			
		$this->loadLayout();
		
		 //create a text block with the name of "example-block"
        $block = $this->getLayout()
        ->createBlock('core/text', 'example-block')
        ->setText('<h1>No Access Area</h1>');

        $this->_addContent($block);

		     
		$this->renderLayout();
    }
}
