<?php
########################################################## Config Parameters ######################################################################
//Set Prestashop webservice API Key
define("PRESTASHOP_API_KEY","DRXL7I1CS8W5WFLBEZSZMAEGYE58O8CF");
//enter an order id which exists in your prestashop
$test_order_id=1;


/********************************************************** How to Use: **************************************************************************

ZF Case 33331

1) Please, keep this file in the root folder of prestashop store 
   i.e. same folder where ShippingZ integration files are placed. Then run this file from browser.

2) For the prestashop API, be sure to re-generate the .htaccess file:
      generate the .htaccess file in "Preferences" > "SEO & URLs". 
	  v 1.5.2: go to "Preferences" > "SEO & URLs"  and just click on "save" button related to Set up URLs Section

3) Check that the Prestashop webservice is enabled at Configuration section under Advanced Parameters>Webservice.

  
*/
###################################################################################################################################################

//Check if prestashop webservice library(PSWebServiceLibrary.php) is accessible
echo "Checking accessibility of prestashop webservice library(PSWebServiceLibrary.php).........";
if(file_exists("PSWebServiceLibrary.php"))
{
	echo "OK<br>";
	include("PSWebServiceLibrary.php");
}
else
{
		echo "<br><br>";
		echo "PrestaShop Web Service Library  file ($filename) is missing.<br>Please, make sure \"$filename\" file is present in the root folder of store i.e. same folder where ShippingZ integration files are placed.";
		exit;
	
}
			
//Extract Path to Prestashop
$folder_path=$_SERVER['SCRIPT_NAME'];
$folder_path_temp=explode("/",$folder_path);
$actual_file_name=$folder_path_temp[count($folder_path_temp)-1];
$folder_path="http://".$_SERVER['HTTP_HOST'].str_replace($actual_file_name,"",$folder_path);


echo "Checking accessibility of prestashop webservice API and permission of required prestashop resources.........<br>";

try
{
	 //Make API call
     $webService = new PrestaShopWebservice($folder_path, PRESTASHOP_API_KEY, true);
}
catch (PrestaShopWebserviceException $ex)
{
	//get user friendly error message
	echo  "<br>".$ex->getMessage();  
}

try
{
	//Get Order Details using API
	$xml = $webService->get(array('resource' => 'orders', 'id' => $test_order_id));
	$order_resources = $xml->children()->children();
	 
	 
	//Get Customer Details	 
	$opt = array('resource' => 'customers');			
	$opt['id'] = $order_resources->id_customer;
	
	$xml = $webService->get($opt);

}
catch (PrestaShopWebserviceException $ex)
{
	 //get user friendly error message
	 echo  "<br>".$ex->getMessage();  
}
			
?>