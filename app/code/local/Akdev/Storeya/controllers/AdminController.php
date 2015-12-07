<?php
 
/**
 * @category   Akdev
 * @package    Akdev_Storeya
 * @author     AjayKwatra@gmail.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Akdev_Storeya_AdminController extends Mage_Adminhtml_Controller_Action
{

private $apiKey = null;
	


	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('storeya/items');
			
		return $this;
	}  
	
	public function indexAction()
    {
	
			 //create a text block with the name of "storya-block"
	$this->loadLayout()->_setActiveMenu('storeya/items');
    $filepath =  Mage::getBaseDir('app').DS. 'code' .DS. 'local' .DS. 'Akdev' .DS. 'Storeya'.DS. 'Block' .DS. 'storeya.phtml';
	$fileContent = file_get_contents($filepath);
		$msg = '';
		$msg = 'Api Not created Yet. Click on Create Button to do now.';
			
		$isApiExits = $this->_checkApiExists();	
	if($isApiExits){		
		
		$siteUrl = 'https://www.storeya.com/createaccount'.$isApiExits;
		
		$msg = 'API setup completed, please <a href="'.$siteUrl.'" target="_blank">click here </a> to start';
		//$this->getResponse()->setRedirect($this->getUrl('storeya/admin/index'));
	}	
			
			
		$fileContent = str_replace('{{@@url@@}}', $this->getUrl('storeya/admin/create')
,$fileContent);
		$fileContent = str_replace('{{@@msg@@}}',$msg,$fileContent);

        //create a text block with the name of "storeya-block"
        $block = $this->getLayout()
        ->createBlock('core/text', 'storeya-block')
        ->setText($fileContent);

        $this->_addContent($block);

       // $this->_initAction()->renderLayout();
        
        $this->renderLayout();

    }
 
 public function createAction(){
	
		
		//AK 
	
	 $version = (string) substr(Mage::getVersion(),0,3);
	//1.4.x.x
	//1.5.1.0
	//1.6.0.0
	
	## check storeya role is exists or not
	$roleStoreyaExists = FALSE;
	$objApiRole = Mage::getSingleton('api/roles');
	$roleNames = $objApiRole->getCollection();
	$roleNames->getSelect()->where('role_name=?','storeya');//storeya
	$api_role_data = $roleNames->getData();
	
	//->setId($id)->delete()
	/*
	echo '<pre>';
	print_r($api_role_data);
	die;
	*/
	
	if(!empty($api_role_data)){
		$roleStoreyaExists = TRUE;
		//$objApiRole->setId($api_role_data[0]['role_id'])->delete();
}
	## check storeya api user is exists or not	
	$apiUserStoreyaExists = FALSE;
	$objApiUser = Mage::getSingleton('api/user');
	$apiUsernames = $objApiUser->getCollection();
	$apiUsernames->getSelect()->where('username=?','storeya_api_user');//storeya_api_user
	$api_user_data = $apiUsernames->getData();
	
	if(!empty($api_user_data)){
		$apiUserStoreyaExists = TRUE;			
		//$objApiUser->setId($api_user_data[0]['user_id'])->delete();
	}
	
	$this->apiKey = $this->_genRandomString();
	$appDir = Mage::getBaseDir('app'); # /data/htdocs/magento15/app
	//print_r(get_defined_constants());
	
	switch($version){
		
		case '1.4':
		$var14_file_1 = '/code/core/Mage/Api/Model/Server/Adapter/Soap.php';
		$txt_find = "\$this->fault('0', 'Unable to load Soap extension on the server');";
		$txt_replace = "\$this->fault('0', 'Unable to load Soap extension on the server'); \n } ";
		
		if(!file_exists($appDir . dirname($var14_file_1) . '/Soap_copy.php')){
			copy($appDir . $var14_file_1 , $appDir . dirname($var14_file_1) . '/Soap_copy.php' );
		}
		$file_content = file_get_contents($appDir . $var14_file_1);
		//if replace text not already there , then replace
		if( strpos($file_content,$txt_replace) === FALSE ) {	
			$file_content = str_ireplace($txt_find,$txt_replace,$file_content);
			file_put_contents($appDir . $var14_file_1,$file_content);
		}
	
		
		break;
		
		case '1.5':
		//app/code/core/Mage/Api/Model/Server/V2/Adapter/Soap.php
		$var15_file_1 = '/code/core/Mage/Api/Model/Server/V2/Adapter/Soap.php';
		$txt_find = "->setHeader('Content-Type','text/xml; charset='.\$apiConfigCharset)";
		$txt_replace = "->setHeader('Content-Type','text/xml; charset='.\$apiConfigCharset, true)";
		
		if(!file_exists($appDir . dirname($var15_file_1) . '/Soap_copy.php')){
			copy($appDir . $var15_file_1 , $appDir . dirname($var15_file_1) . '/Soap_copy.php' );
		}
		$file_content = file_get_contents($appDir . $var15_file_1);
		
		//if replace text not already there , then replace
		if( strpos($file_content,$txt_replace) === FALSE ) {		
			$file_content = str_ireplace($txt_find,$txt_replace,$file_content);
			file_put_contents($appDir . $var15_file_1,$file_content);
		}
		
		
		
		## second file
		$var15_file_2 = '/code/core/Mage/Api/Model/Server/Wsi/Adapter/Soap.php';
		
		if(!file_exists($appDir . dirname($var15_file_2) . '/Soap_copy.php')){
			copy($appDir . $var15_file_2 , $appDir . dirname($var15_file_2) . '/Soap_copy.php' );
		}
		$file_content = file_get_contents($appDir . $var15_file_2);
		
		//if replace text not already there , then replace
		if( strpos($file_content,$txt_replace) === FALSE ) {
			$file_content = str_ireplace($txt_find,$txt_replace,$file_content);
			file_put_contents($appDir . $var15_file_2, $file_content);
		}
				
		
		break;
				
		//case '1.6':		
		//break;
		
		default:
	}
	
	/*
	
	if($apiUserStoreyaExists && $roleStoreyaExists){
		
		/*
		 * Array
(
    [0] => Array
        (
            [role_id] => 3
            [parent_id] => 0
            [tree_level] => 1
            [sort_order] => 0
            [role_type] => G
            [user_id] => 0
            [role_name] => admin
        )

)

		 
		
		echo $api_user_data[0]['user_id'];
		die('<br>jkjkkj');
		
		//$roleNames->setApiKey($apiKey);
		//$roleNames->save();
		
		//$this->_setApiXml($api_user_data[0]['username'],$apiKey,$api_role_data[0]['role_id'],$api_user_data[0]['user_id']);
		
		
		
	}
		
	*/
	
	//die('dd');
	
	if( ($roleStoreyaExists) && (!$apiUserStoreyaExists) )
	{
		$this->_bindApiRoleToUser($api_role_data[0]['role_id']);
			
	}
	
	if( (!$roleStoreyaExists) && ($apiUserStoreyaExists) )
	{
		//$this->_bindApiRoleToUser($api_role_data[0]['role_id']);
		$this->_createStoreyaApiRoleUser(true,$api_user_data[0]['user_id']);//true - delete true to api user and create new	
	}
	
	if( (!$roleStoreyaExists) && (!$apiUserStoreyaExists) )
	{
		$this->_createStoreyaApiRoleUser();
			
	}	
		
		
	if( ($roleStoreyaExists) && ($apiUserStoreyaExists) ){
		
		$this->getResponse()->setRedirect($this->getUrl('storeya/admin/index'));
		return;
	
	}	
		
		
	//Mage::app()->cleanCache();
		//AK 
	
	$this->getResponse()->setRedirect($this->getUrl('storeya/admin/index'));
	return;
		//$this->_initAction()->renderLayout();
	
	 
 }
 
 //check the api is created yet or not
    public function _checkApiExists(){
	
		$this->_resetXmlApi();
		
		$xmlPath = Mage::getBaseDir('app').DS. 'code' .DS. 'local' .DS. 'Akdev' .DS. 'Storeya'.DS. 'etc' .DS. 'storeya_api.xml';
		//$xmlObj = new Varien_Simplexml_Config($xmlPath);
		//$xmlData = $this->xmlObj->getNode();
		//print_r($xmlData);
		
		//echo '<pre>';
		
		$xmlObj = simplexml_load_file($xmlPath);
		
		//$xmlObj->api_role_id = 22;
		//$xmlContent = $xmlObj->asXML();
		//file_put_contents($xmlPath,$xmlContent);
		//ADHGTYUIOK
		//print_r($xmlObj);
		//die;
		
		$storeyaApiKey = $xmlObj->api_key;
		$storeyaApiUser = $xmlObj->api_username;
		
		//chmod($xmlPath,0777);
		//echo (int) is_writable($xmlPath);
		
		//echo $storeyaApiKey;
		//die('chk');
		
	//	$objConfig = Mage::getSingleton('core/config_data')->getCollection();
		//$objConfig->getSelect()->where('path=?','web/unsecure/base_url');
		//$configData = $objConfig->getData();	
		$shopUrl =  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
		//$configData[0]['value'];
		

		if($storeyaApiKey !=''){			
			//$this->getResponse()->setRedirect($this->getUrl('storeya/admin/index'));
			return '?user='.$storeyaApiUser .'&amp;key=' .$storeyaApiKey.'&amp;shopurl='.$shopUrl;
		}else{
			return FALSE;
			}
		
	}
 
 
 public function _checkApiRoleExists(){
	 
	 ## check storeya role is exists or not
	$roleStoreyaExists = FALSE;
	$objApiRole = Mage::getSingleton('api/roles');
	$roleNames = $objApiRole->getCollection();
	$roleNames->getSelect()->where('role_name=?','storeya');//storeya
	$api_role_data = $roleNames->getData();
	
	return $api_role_data;
	 
 }
 
 public function _checkApiUserExists(){
	 
 
 ## check storeya api user is exists or not	
	$apiUserStoreyaExists = FALSE;
	$objApiUser = Mage::getSingleton('api/user');
	$apiUsernames = $objApiUser->getCollection();
	$apiUsernames->getSelect()->where('username=?','storeya_api_user');//storeya_api_user
	$api_user_data = $apiUsernames->getData();
	
	return $api_user_data;
 }
 
 
 public function _resetXmlApi(){
	 
	 	$api_role_data = $this->_checkApiRoleExists();	
	 	$api_user_data = $this->_checkApiUserExists();
	 	
		if( (empty($api_role_data)) || (empty($api_user_data)) ) {
	
		$xmlPath = Mage::getBaseDir('app').DS. 'code' .DS. 'local' .DS. 'Akdev' .DS. 'Storeya'.DS. 'etc' .DS. 'storeya_api.xml';
		//$xmlObj = new Varien_Simplexml_Config($xmlPath);
		$xmlObj = simplexml_load_file($xmlPath);
		//$xmlObj->api_role_id = 22;
		//$xmlContent = $xmlObj->asXML();
		//file_put_contents($xmlPath,$xmlContent);
		
		
		//$xmlObj->setNode('api_key','newkey');
		$xmlObj->api_key = '';
		$xmlObj->api_username = '';
		$xmlObj->api_role_id = '';
		$xmlObj->api_user_id = '';
		
		$xmlContent = $xmlObj->asXML();
		file_put_contents($xmlPath,$xmlContent);
	
		
	}		
	
	
}
 
 
 public function _bindApiRoleToUser($roleId){
	 
	// echo $this->apiKey,$roleId;
	// die('dsddd');
	 
	 try{
		 //create api user
        $user = Mage::getSingleton('api/user');
		$user->setData(array(
		'username' => 'storeya_api_user',
		'firstname' => 'Api',
		'lastname' => 'User',
		'email' => 'support@storeya.com',
		'api_key' => $this->apiKey,
		'api_key_confirmation' => $this->apiKey,
		'is_active' => 1,
		'user_roles' => '',
		'assigned_user_role' => '',
		'role_name' => '',
		'roles' => array($roleId)
		));
		$user->save()->load($user->getId());

		$user->setRoleIds(array($roleId))
		->setRoleUserId($user->getUserId())
		->saveRelations();
        
        $this->_setApiXml('storeya_api_user',$this->apiKey,$roleId,$user->getUserId());
        
        
        return TRUE;
        
    } catch (Exception $e){
		//echo $e->getMessage();     
		// echo 'Unable to create now, please try again.';
     return FALSE;
	 
 }

}
 
  //check the api is created yet or not
    public function _setApiXml($user,$key,$role_id,$user_id){
		
		//echo 'kkkk:: ',$user,$key,$role_id,$user_id;
		//die('dddd');
		
		$xmlPath = Mage::getBaseDir('app').DS. 'code' .DS. 'local' .DS. 'Akdev' .DS. 'Storeya'.DS. 'etc' .DS. 'storeya_api.xml';
		//$xmlObj = new Varien_Simplexml_Config($xmlPath);
		$xmlObj = simplexml_load_file($xmlPath);
		//$xmlObj->api_role_id = 22;
		//$xmlContent = $xmlObj->asXML();
		//file_put_contents($xmlPath,$xmlContent);
		
		
		//$xmlObj->setNode('api_key','newkey');
		$xmlObj->api_key = $key;
		$xmlObj->api_username = $user;
		$xmlObj->api_role_id = $role_id;
		$xmlObj->api_user_id = $user_id;
		
		$xmlContent = $xmlObj->asXML();
		file_put_contents($xmlPath,$xmlContent);
		
		$storeyaApiUser = $xmlObj->api_username;
		$storeyaApiKey = $xmlObj->api_key;
		
		if($storeyaApiKey !='' && $storeyaApiUser != ''){
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('storeya')->__('API setup completed'));			
			$this->getResponse()->setRedirect($this->getUrl('storeya/admin/index'));
			return ;
		}else{
			return FALSE;
			}
		
	}
 
	public function _createStoreyaApiRoleUser($delUser=false,$apiUserId=0){
	

	try {
			
        //create roles
        
			$role = Mage::getSingleton('api/roles')
			->setName('storeya')
			->setPid(false)
			->setRoleType('G')
			->save();

			Mage::getSingleton("api/rules")
			->setRoleId($role->getId())
			->setResources(array('all'))
			->saveRel();
        
        
        
        //create api user
        $user = Mage::getSingleton('api/user');
        
        if($delUser)
        {
			$user->setId($apiUserId)->delete();
		}
        
       $user->setData(array(
		'username' => 'storeya_api_user',
		'firstname' => 'Api',
		'lastname' => 'User',
		'email' => 'support@storeya.com',
		'api_key' => $this->apiKey,
		'api_key_confirmation' => $this->apiKey,
		'is_active' => 1,
		'user_roles' => '',
		'assigned_user_role' => '',
		'role_name' => '',
		'roles' => array($role->getId())
		));
		$user->save()->load($user->getId());

		$user->setRoleIds(array($role->getId()))
		->setRoleUserId($user->getUserId())
		->saveRelations();
        
        $this->_setApiXml('storeya_api_user',$this->apiKey,$role->getId(),$user->getUserId());
        
        
        return TRUE;
        
    } catch (Exception $e){
		//echo $e->getMessage();     
		// echo 'Unable to create now, please try again.';
     return FALSE;
 }


}


//ak api key random generator
  private function _genRandomString($length=10) {
		//$length = 10;
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';    

		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters))];
		}

		return $string;
	}
 
   

}
