<?php

define("SHIPPINGZMAGENTO_VERSION","3.0.0.55139");

# ################################################################################
# 	
#   (c) 2010, 2011, 2012 Z-Firm LLC, ALL RIGHTS RESERVED.
#   Licensed to current Stamps.com customers. 
#
#   The terms of your Stamps.com license 
#   apply to the use of this file and the contents of the  
#   Stamps_ShoppingCart_Integration_Kit__See_README_file.zip   file.
#   
#   This file is protected by U.S. Copyright. Technologies and techniques herein are
#   the proprietary methods of Z-Firm LLC. 
#  
#   For use only by customers in good standing of Stamps.com
#
#
# 	IMPORTANT
# 	=========
# 	THIS FILE IS GOVERNED BY THE STAMPS.COM LICENSE AGREEMENT
#
# 	Using or reading this file indicates your acceptance of the Stamps.com License Agreement.
#
# 	If you do not agree with these terms, this file and related files must be deleted immediately.
#
# 	Thank you for using Stamps.com!
#
################################################################################



//Function for checking Include Files
function Check_Include_File($filename)
{
	if(file_exists($filename))
	{
		return true;
	}
	else
	{
		echo "\"$filename\" is not accessible.";
		exit;
	}

}
//Check for ShippingZ integration files
if(Check_Include_File("ShippingZSettings.php"))
include("ShippingZSettings.php");
if(Check_Include_File("ShippingZClasses.php"))
include("ShippingZClasses.php");
if(Check_Include_File("ShippingZMessages.php"))
include("ShippingZMessages.php");

// TEST all the files are all the same version
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZMAGENTO_VERSION && SHIPPINGZMAGENTO_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZMagento.php [".SHIPPINGZMAGENTO_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}

if(!defined("Magento_Store_Code_To_Service"))
define("Magento_Store_Code_To_Service","-ALL-");

//Include mage model for gift messages
if(Magento_RetrieveOrderGiftMessage==1 || Magento_RetrieveProductGiftMessage==1 || Magento_Store_Code_To_Service!='-ALL-')
{
	require_once 'app/Mage.php';
	$app = Mage::app();
	if(Magento_Store_Code_To_Service!='-ALL-')
	{
		$allStores = Mage::app()->getStores();
		foreach ($allStores as $_eachStoreId => $val)
		{
			$_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
			$_storeName = Mage::app()->getStore($_eachStoreId)->getName();
			$_storeId = Mage::app()->getStore($_eachStoreId)->getId();
			
			if($_storeCode==Magento_Store_Code_To_Service)
			$selected_store_id=$_storeId;
		}
	}
}
else
$selected_store_id="";
############################################### Check & adjust "default_socket_timeout"#######################################
$timeout_value="";
$timeout_value=@ini_get("default_socket_timeout");
if($timeout_value!="" && $timeout_value<120)
@ini_set("default_socket_timeout",120);
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
######################################### Find out the store URL #########################################

$url="http".(((empty($_SERVER['HTTPS'])&&$_SERVER['SERVER_PORT']!=443))?"" : "s")."://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

$url= str_replace("ShippingZMagento.php","",$url);
define("WebsiteUrl",$url);

############################################## Class ShippingZMagento ##########################################
class ShippingZMagento extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	######################################## Function EXECUTE_CURL ######################################
	function EXECUTE_CURL($url)
	{
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		//Additional curl options Following ZF Case 24497
		//To make sure curl works for SSL Server too
		//We don't have access to other servers.Hence using following two curl options is safe for our use.
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		 
		$fp = curl_exec($ch); 
		if($fp === false)
		{
			$this->CheckAndOverrideErrorMessage('Curl error: ' . curl_error($ch));
			
		}
		
		$http_code =curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $http_code."~=~".$fp;
	
	}
	############################################## Function Check_DB_Access #################################
	//Check Database access(for magento everything will be done using API so, we don't need database access.But need to check if API credentials are set properly)
	#######################################################################################################
	
	function Check_DB_Access()
	{
		
		global $proxy,$sessionId;
			
		if(WebsiteUrl!="" && Magento_Username!="" && Magento_Password!="")
		{
	
			$url = WebsiteUrl.'api/soap/?wsdl';
			$extra_path="";
			
			//check if the url is proper & we can access wsdl
			$curl_result=$this->EXECUTE_CURL($url);
			$curl_result_temp=explode("~=~",$curl_result);
			$fp = $curl_result_temp[1]; 
			$http_code =$curl_result_temp[0];
			
			
			$this->CheckAndOverrideErrorMessage($fp);//check for custom error 
			
			if($http_code!=200)
            {
			   if(substr(WebsiteUrl,strlen(WebsiteUrl)-1)!="/")
			   $extra_path="/index.php/";
			   else
			   $extra_path.="index.php/";
				
			   $url = WebsiteUrl.$extra_path.'api/soap/?wsdl'; 
			   //check if the url is proper & we can access wsdl
			   $curl_result=$this->EXECUTE_CURL($url);
			   $curl_result_temp=explode("~=~",$curl_result);
			   $fp = $curl_result_temp[1]; 
			   $http_code =$curl_result_temp[0];
			}
			
			if($http_code==200)
			{
				
							
				try
				{
					$proxy = new SoapClient(WebsiteUrl.$extra_path.'api/soap/?wsdl',array('exceptions' => 0,'trace' => 1,"connection_timeout" => 120));//path to magento wsdl
				}
				catch(Exception $e)
				{
				
					$this->display_msg=MAGENTO_TEMPORARY_ERROR_MSG;
					$this->SetXmlError(1,$this->display_msg, $fp . $url);
					exit;
				}
										
				try //See if API credentials are proper
				{
					$sessionId = $proxy->login(Magento_Username, Magento_Password);//create session id
		
					$this->display_msg=DB_SUCCESS_MSG;
				}
				catch(Exception $e)
				{
					//Wrong API credentials
					$this->display_msg=MAGENTO_WRONG_API_DETAILS_ERROR_MSG;
					$this->SetXmlError(1,$this->display_msg, $fp . $url);
					exit;
				
				}
			}
			else
			{
				//Wrong store url
				$this->display_msg=MAGENTO_WRONG_STORE_URL_ERROR_MSG;
				$this->SetXmlError(1,$this->display_msg, $fp . $url);
				exit;
			
			}
		}
		else
		{
			//Store URL or API credentials not set
			$this->display_msg=MAGENTO_API_NOT_SET_ERROR_MSG;
			$this->SetXmlError(1,$this->display_msg);
			exit;
		}	
				
	}
	############################################## Function GetMagentoField #################################
	//Get fields returned by Magento
	#######################################################################################################
	function GetMagentoField($arr_key,$field,$item_counter=-1)
	{
		if($arr_key!="")
		{
			if($item_counter>-1)
			{	//for order items
				if(isset($this->magento_orders_temp[$arr_key][$item_counter][$field]))
				{
					return $this->magento_orders_temp[$arr_key][$item_counter][$field];
				}
				else
				{
					return '';
				}
			
			}
			else
			{
				//shipping or billing array fields
				if(isset($this->magento_orders_temp[$arr_key][$field]))
				{
					return $this->magento_orders_temp[$arr_key][$field];
				}
				else
				{
					return '';
				}
			}
		}
		else
		{
			//for direct fields
			if(isset($this->magento_orders_temp[$field]))
			{
				return $this->magento_orders_temp[$field];
			}
			else
			{
				return '';
			}
		}
	}
	############################################## Function UpdateDatefrom  #################################
	//if Day(DateFrom) = Day(DateTo) then set DateFrom to previous day
	#######################################################################################################
	function UpdateDatefrom($datefrom,$dateto)
	{
		
		$day_datefrom=substr($datefrom,0,10);
		$day_dateto=substr($dateto,0,10);
		
		$time_str_datefrom=substr($datefrom,10);
		
		if($day_datefrom==$day_dateto)
		{
			$updated_date_from=date("Y-m-d",strtotime("-1 day", strtotime($day_datefrom)));
			$updated_date_from=$updated_date_from.$time_str_datefrom;
			return $updated_date_from;
		}
		else
		{
			return $datefrom;
		}
		
	}
	############################################## Function SafeUnserialize  #################################
	//This will return false in case the passed string is not unserializeable
	#######################################################################################################
	function SafeUnserialize($serialized_string) 
	{
   		if (strpos($serialized_string, "\0") === false &&  is_string($serialized_string) ) {
			if (strpos($serialized_string, 'O:') === false) {
			  
				return @unserialize($serialized_string);
			} else if (!preg_match('/(^|;|{|})O:[0-9]+:"/', $serialized_string)) {
			   
				return @unserialize($serialized_string);
			}
		}
		return false;
	}
	############################################## Function GetProductOptions  #################################
	//Used to get product attributes and sku for product variations
	#######################################################################################################
	function GetProductOptions($option_string,$code='')
	{
			
			$option_arr=$this->SafeUnserialize($option_string);
			
			if($code=="")
			{
				//get attribute details
				$formatted_option_variation_details="";
							
				if(isset($option_arr['attributes_info']))
				{
					//print_r($option_arr['attributes_info']);
					foreach($option_arr['attributes_info'] as $key=>$val)
					{
						foreach($val as $key2=>$value2)
						{
							
							if($key2=="label")
							{
								$curr_label=$value2;
							}
							else if($key2=="value")
							{
								if($formatted_option_variation_details!="")
									$formatted_option_variation_details.=", ".$value2;
								else
									$formatted_option_variation_details=$value2;
							}
							
						}
						
					}
					
					if($formatted_option_variation_details!="")
					{
						return " (".$formatted_option_variation_details.")";	
					}
					else
					{
					
						return '';
					}
					
				}
			}
			else
			{
					//get simple sku
					if(isset($option_arr[$code]))
						return "-".$option_arr[$code];
					else
						return '';
								
			}
		
	}
	############################################## Function GetProductOptionValuebyLabel  #################################
	//Used to get product attributes by label
	#######################################################################################################
	function GetProductOptionValuebyLabel($option_string,$label='')
	{
		
			$option_arr=$this->SafeUnserialize($option_string);
			
			//get attribute details
			$formatted_option_variation_details="";
						
			if(isset($option_arr['attributes_info']))
			{
				foreach($option_arr['attributes_info'] as $key=>$val)
				{
					$curr_label=0;
					
					foreach($val as $key2=>$value2)
					{
						if($key2=="label")
						{
							if($value2==$label)
							{
								$curr_label=1;
							}
						}
						else if($key2=="value" && $curr_label==1)
						{
							return $value2;
						}
						
					}
					
				}
			}
			
			
		
	}
	############################################## Function DebugApiError  #################################
	//Track Api Error
	#######################################################################################################
	function DebugApiError($Result,$Method,$line,$params)
	{
		
		global $proxy,$sessionId;
		
		if(is_soap_fault($Result)) 
		{
    		
			if($this->GetValues('show_api_error')==1)
			{
			 
				print "<br><b>SHIPPINGZCLASSES Version:</b>".SHIPPINGZCLASSES_VERSION;
				print "<br><b>SHIPPINGZSETTINGS Version:</b>".SHIPPINGZSETTINGS_VERSION;
				print "<br><b>SHIPPINGZMAGENTO Version:</b>".SHIPPINGZMAGENTO_VERSION;
				print "<br><b>SHIPPINGZMESSAGES Version:</b>".SHIPPINGZMESSAGES_VERSION;
				print("<br><b>Magento API Func Called:</b> $Method , SOAP Fault: (faultcode: {$Result->faultcode}, faultstring: {$Result->faultstring}), <b>Called at Line:</b> ".$line);
				echo "<br><b>Parameters:</b> <br>";
				print_r($params)."<br>";
				echo "<br><b>Response:</b><br> ";
				print_r($Result)."<br>";
				exit;
			}
			else
			{
				$this->CheckAndOverrideErrorMessage($Result->faultstring); //check for custom errors
				
				$this->SetXmlError("{$Result->faultcode}","Unable to communicate with the Magento API. Please review the Magento setup documentation. Check that the Magento API User Name and password are set correctly. Check that the API account is Active.","Magento API Func Called-$Method , SOAP Fault-({$Result->faultstring}),Called at Line-".$line);
				
			
			}
		}
		
	}
	############################################## Function ShowRawData  #################################
	//To check raw data returned by api
	#######################################################################################################
	function ShowRawData($Param,$Result,$is_exit=0)
	{
		
		global $proxy,$sessionId;
		
		if($this->GetValues($Param)==1)
		{
			if(count($Result)>0)
			{
				print_r($Result);
				echo "<br>=====================<br>";
			}
			if($is_exit)
			{
				exit;
			}
		}
		
	}
	############################################## Function GetOrderCountByDate #################################
	//Get order count
	#######################################################################################################
	function GetOrderCountByDate($datefrom,$dateto)
	{
		global $proxy,$sessionId,$selected_store_id;
		
		$order_array_pending=array();
		$order_array_processing=array();
		$order_array_complete=array();
		$order_array_closed=array();
		$order_array_cancelled=array();
		
		$datefrom=$this->UpdateDatefrom($datefrom,$dateto);
		
		if(Magento_Store_Code_To_Service!="-ALL-" && is_numeric($selected_store_id))
		{
				
				
				
				//count orders from specific store
					
				if(MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
				{
					$order_array_pending=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'pending'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					
					$this->DebugApiError($order_array_pending,"sales_order.list",__LINE__,array(array('status'=>array('='=>'pending'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_pending);
					
				}
				if(MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
				{
					$order_array_processing=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'processing'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_processing,"sales_order.list",__LINE__,array(array('status'=>array('='=>'processing'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_processing);
				}
				if(MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
				{
					$order_array_complete=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'complete'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_complete,"sales_order.list",__LINE__,array(array('status'=>array('='=>'complete'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_complete);
				}
				if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED==1)
				{
					$order_array_closed=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'closed'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_closed,"sales_order.list",__LINE__,array(array('status'=>array('='=>'closed'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_closed);
				}
				if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
				{
					$order_array_cancelled=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'canceled'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_cancelled,"sales_order.list",__LINE__,array(array('status'=>array('='=>'canceled'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_cancelled);
				}
		}
		else
		{
				//count all orders irrespective of store id
				if(MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
				{
					$order_array_pending=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'pending'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_pending,"sales_order.list",__LINE__,array(array('status'=>array('='=>'pending'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_pending);
					
				}
				if(MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
				{
					$order_array_processing=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'processing'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_processing,"sales_order.list",__LINE__,array(array('status'=>array('='=>'processing'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_processing);
				}
				if(MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
				{
					$order_array_complete=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'complete'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_complete,"sales_order.list",__LINE__,array(array('status'=>array('='=>'complete'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_complete);
				}
				if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED==1)
				{
					$order_array_closed=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'closed'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_closed,"sales_order.list",__LINE__,array(array('status'=>array('='=>'closed'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_closed);
				}
				if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
				{
					$order_array_cancelled=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'canceled'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->DebugApiError($order_array_cancelled,"sales_order.list",__LINE__,array(array('status'=>array('='=>'canceled'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
					
					$this->ShowRawData('rawdata',$order_array_cancelled);
				}
		
		
		
		}
		if($this->GetValues('rawdata')==1)
		exit;
				
		$total_count=count($order_array_pending)+count($order_array_processing)+count($order_array_complete)+count($order_array_closed)+count($order_array_cancelled);
		
		return $total_count;
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		
		global $proxy,$sessionId;
		
		if($ShipDate!="")
			$shipped_on=$ShipDate;
		else
			$shipped_on=date("m/d/Y");
		
		if($Carrier!="")
		{
			$SelectedCarrier=$Carrier;
			$Carrier=" via ".$Carrier;
						
		}
		else
		{
			$SelectedCarrier="ups";
		}
			
		if($Service!="")
			$ServiceString=" [".$Service."]";
		else
			$ServiceString="";
		
		if(Magento_SendsShippingEmail==1)
			$send_email_flag=true;
		else
			$send_email_flag=false;
			
			
		if(Magento_SendsShippingEmail_AddComments==1)
			$send_email_include_comments=true;
		else
			$send_email_include_comments=false;
			
			
		if(Magento_SendsBuyerEmail==1)
			$send_buyer_email_flag=true;
		else
			$send_buyer_email_flag=false;
	
		
		//prepare $comments 
		$TrackingString="";
		if($TrackingNumber!="")
		$TrackingString=", Tracking number $TrackingNumber";
		
		$comments="Shipped on $shipped_on".$Carrier.$ServiceString.$TrackingString;
		
		$magento_orders_temp=$proxy->call($sessionId, 'sales_order.info', $OrderNumber);
		$this->DebugApiError($magento_orders_temp,"sales_order.info",__LINE__ ,$OrderNumber);
		$current_order_status=$magento_orders_temp['status'];
		$related_store_id=$magento_orders_temp['store_id'];
		
		$this->ShowRawData('show_current_order_status',$current_order_status."-".$comments,1);
		
		if(MAGENTO_SHIPPED_STATUS_COMPLETE_ALL_SHIPPED_ORDERS==1)
		{
			$change_order_status="complete";
		}
		else
		{   
		    
			if(strtolower($current_order_status)=="pending")
				$change_order_status="processing";
			else if(strtolower($current_order_status)=="processing")
				$change_order_status="complete";
			else
			$change_order_status=$current_order_status;
		}
		
		if(Magento_StoreShippingInComments==1)
		{
			try
			{
				// add comment using sales_order.addComment method
				$result=$proxy->call($sessionId, 'sales_order.addComment', array($OrderNumber, $change_order_status, $comments, $send_buyer_email_flag));
				$this->DebugApiError($result,"sales_order.addComment",__LINE__, array($OrderNumber, $change_order_status, $comments, $send_buyer_email_flag));
				$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
			}
			catch( Exception $e )
			{
				//display error message
				$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
				$this->SetXmlError(1,$this->display_msg);
			}
	   }
	   else
	   {
	   		try
			{
				
				
				//get order details by id
				$magento_orders_temp=$proxy->call($sessionId, 'sales_order.info', $OrderNumber);
				$this->DebugApiError($magento_orders_temp,"sales_order.info",__LINE__,$OrderNumber);
				
		
				$exists = $proxy->call($sessionId, 'sales_order_shipment.list',array(array('order_increment_id'=>array('='=>$OrderNumber ),'store_id'=>array('='=>$related_store_id))));
				
			  
			  if(is_soap_fault($exists) )
			  {
				//call api again for magento version 1.4.1.1 
				$related_order_id=$magento_orders_temp['order_id'];
				
				$exists = $proxy->call($sessionId, 'sales_order_shipment.list', array(array('order_id'=>array('='=>$related_order_id),'store_id'=>array('='=>$related_store_id))));	
				$this->DebugApiError($exists,"sales_order_shipment.list",__LINE__,array(array('order_id'=>array('='=>$related_order_id),'store_id'=>array('='=>$related_store_id))));	
				
			  }
			
				if(isset($exists[0]['increment_id']))
				{
					$newShipmentId=$exists[0]['increment_id'];
					
				}
				else
				{
					//create new shipment
					$newShipmentId = $proxy->call($sessionId, 'sales_order_shipment.create',array($OrderNumber,array() ,$comments, $send_email_flag,$send_email_include_comments) );
					$this->DebugApiError($newShipmentId,"sales_order_shipment.create",__LINE__,array($OrderNumber,array() ,$comments, $send_email_flag,$send_email_include_comments));
				}
				
				#add tracking number
				if($Service=="")
				$Service="Shipping Tracking";
				
				
				$newTrackId = $proxy->call($sessionId, 'sales_order_shipment.addTrack', array($newShipmentId, strtolower($SelectedCarrier), $Service, $TrackingNumber));
				$this->DebugApiError($newTrackId,"sales_order_shipment.addTrack",__LINE__,array($newShipmentId, strtolower($SelectedCarrier), $Service, $TrackingNumber));
				
			
				#force status change
				$result=$proxy->call($sessionId, 'sales_order.addComment', array($OrderNumber, $change_order_status, $comments, $send_buyer_email_flag));
				$this->DebugApiError($result,"sales_order.addComment",__LINE__, array($OrderNumber, $change_order_status, $comments, $send_buyer_email_flag));
				$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success")); 

			}
			catch( Exception $e )
			{
				
				//display error message
				$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
				$this->SetXmlError(1,$e->getMessage());
			}
	   
	   }
		
	}
	############################################## Function Fetch_DB_Orders #################################
	//Fetch orders based on date range using sales_order.list method
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		global $proxy,$sessionId,$selected_store_id;
		
		$order_array_pending=array();
		$order_array_processing=array();
		$order_array_complete=array();
		$order_array_closed=array();
		$order_array_cancelled=array();
		
		$datefrom=$this->UpdateDatefrom($datefrom,$dateto);
		
		if(Magento_Store_Code_To_Service!="-ALL-" && is_numeric($selected_store_id))
		{	
			//fetch orders from specific store
			if(MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
			{
				$order_array_pending=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'pending'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_pending,"sales_order.list",__LINE__, array(array('status'=>array('='=>'pending'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_pending);
				
			}
			if(MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
			{
				$order_array_processing=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'processing') ,'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_processing,"sales_order.list",__LINE__,array(array('status'=>array('='=>'processing') ,'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_processing);
			}
			if(MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
			{
				$order_array_complete=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'complete') ,'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_complete,"sales_order.list",__LINE__,array(array('status'=>array('='=>'complete') ,'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_complete);
			}
			if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED==1)
			{
				$order_array_closed=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'closed'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_closed,"sales_order.list",__LINE__,array(array('status'=>array('='=>'closed'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_closed);
			}
			if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
			{
				$order_array_cancelled=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'canceled'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_cancelled,"sales_order.list",__LINE__,array(array('status'=>array('='=>'canceled'),'store_id'=>array('='=>$selected_store_id),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_cancelled);
			
			}
		}
		else
		{
			//fetch all orders irrespective of store
			if(MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
			{
				$order_array_pending=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'pending'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_pending,"sales_order.list",__LINE__, array(array('status'=>array('='=>'pending'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_pending);
				
			}
			if(MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
			{
				$order_array_processing=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'processing') ,'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_processing,"sales_order.list",__LINE__,array(array('status'=>array('='=>'processing') ,'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_processing);
			}
			if(MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
			{
				$order_array_complete=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'complete') ,'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_complete,"sales_order.list",__LINE__,array(array('status'=>array('='=>'complete') ,'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_complete);
			}
			if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED==1)
			{
				$order_array_closed=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'closed'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_closed,"sales_order.list",__LINE__,array(array('status'=>array('='=>'closed'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_closed);
			}
			if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
			{
				$order_array_cancelled=$proxy->call($sessionId, 'sales_order.list', array(array('status'=>array('='=>'canceled'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->DebugApiError($order_array_cancelled,"sales_order.list",__LINE__,array(array('status'=>array('='=>'canceled'),'updated_at'=>array("from"=>$this->GetServerTimeLocalMagento($datefrom), "to"=>$this->GetServerTimeLocalMagento($dateto)))));
				
				$this->ShowRawData('rawdata',$order_array_cancelled);
			
			}
		
		
		
		}
		
		$order_array=array_merge($order_array_pending,$order_array_processing,$order_array_complete,$order_array_closed,$order_array_cancelled);
		
		$this->magento_orders=array();
		
		for($counter=0;$counter<count($order_array);$counter++) 
		{
			
			$order_id=$order_array[$counter]['increment_id'];
			
			//get order details by id
			$this->magento_orders_temp=$proxy->call($sessionId, 'sales_order.info', $order_id);
			$this->DebugApiError($this->magento_orders_temp,"sales_order.info",__LINE__,$order_id);
			
			//Debug order array
			if($counter==(count($order_array)-1))
				$this->ShowRawData('rawdata',$this->magento_orders_temp,1);
			else
				$this->ShowRawData('rawdata',$this->magento_orders_temp);
			
			
			//prepare order array
			$this->magento_orders[$counter]->orderid=$order_id;
			
			
			if(MAGENTO_READ_INVOICES)
			{
			
					//Retrieve invoice numbers
					$order_beginning_number=substr($order_id,0,1);
					$order_base=$order_beginning_number."00000000";
					$order_id_filter=$order_id-$order_base;
								
					$invoice = $proxy->call($sessionId, 'sales_order_invoice.list',array(array('order_id'=>array('='=>$order_id_filter))));
					$invoice_str="";
					
					if(is_array($invoice))
					{
						$num_invoices=count($invoice);
									
						for($k=0;$k<$num_invoices;$k++)
						{
							if($invoice_str!="")
							$invoice_str.=" ";
							
							$invoice_str.=$invoice[$k]['increment_id'];
							
						}
						
						$invoice_str=substr($invoice_str,0,50); //consider upto 50 chars
						$invoice_str=trim($invoice_str);				
					}
			}
			
			//shipping details
			$this->magento_orders[$counter]->order_shipping["FirstName"]=$this->GetMagentoField('shipping_address','firstname');
			$this->magento_orders[$counter]->order_shipping["LastName"]=$this->GetMagentoField('shipping_address','lastname');
			$this->magento_orders[$counter]->order_shipping["Company"]=$this->GetMagentoField('shipping_address','company');
			$this->magento_orders[$counter]->order_shipping["Address1"]=$this->GetMagentoField('shipping_address','street');
			$this->magento_orders[$counter]->order_shipping["City"]=$this->GetMagentoField('shipping_address','city');
			$this->magento_orders[$counter]->order_shipping["State"]=$this->GetMagentoField('shipping_address','region');
			$this->magento_orders[$counter]->order_shipping["PostalCode"]=$this->GetMagentoField('shipping_address','postcode');
			$this->magento_orders[$counter]->order_shipping["Country"]=$this->GetMagentoField('shipping_address','country_id');
			$this->magento_orders[$counter]->order_shipping["Phone"]=$this->GetMagentoField('shipping_address','telephone');
			$this->magento_orders[$counter]->order_shipping["EMail"]=$this->GetMagentoField('','customer_email');
			
			//billing details
			$this->magento_orders[$counter]->order_billing["FirstName"]=$this->GetMagentoField('billing_address','firstname');
			$this->magento_orders[$counter]->order_billing["LastName"]=$this->GetMagentoField('billing_address','lastname');
			$this->magento_orders[$counter]->order_billing["Company"]=$this->GetMagentoField('billing_address','company');
			$this->magento_orders[$counter]->order_billing["Address1"]=$this->GetMagentoField('billing_address','street');
			$this->magento_orders[$counter]->order_billing["City"]=$this->GetMagentoField('billing_address','city');
			$this->magento_orders[$counter]->order_billing["State"]=$this->GetMagentoField('billing_address','region');
			$this->magento_orders[$counter]->order_billing["PostalCode"]=$this->GetMagentoField('billing_address','postcode');
			$this->magento_orders[$counter]->order_billing["Country"]=$this->GetMagentoField('billing_address','country_id');
			$this->magento_orders[$counter]->order_billing["Phone"]=$this->GetMagentoField('billing_address','telephone');
			
			//order info
			$this->magento_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTCMagento($this->GetMagentoField('','created_at'));
			
			if(MAGENTO_READ_INVOICES)
			$this->magento_orders[$counter]->order_info["ExternalID"]=$invoice_str;
			
			$this->magento_orders[$counter]->order_info["ItemsTotal"]=number_format($this->GetMagentoField('','base_subtotal'),2,'.','');
			$this->magento_orders[$counter]->order_info["Total"]=number_format($this->GetMagentoField('','base_grand_total'),2,'.','');
			$this->magento_orders[$counter]->order_info["ItemsTax"]=number_format($this->GetMagentoField('','base_tax_amount'),2,'.','');
			$this->magento_orders[$counter]->order_info["OrderNumber"]=$order_id;
			$this->magento_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetMagentoField('payment','method'));
			$this->magento_orders[$counter]->order_info["ShippingChargesPaid"]=number_format($this->GetMagentoField('','shipping_amount'),2,'.','');
			$this->magento_orders[$counter]->order_info["ShipMethod"]=$this->GetMagentoField('','shipping_description');
			$this->magento_orders[$counter]->order_info["Comments"]="";			

			if($this->GetMagentoField('','status')!="pending")
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status
			if($this->GetMagentoField('','status')=="complete")
				$this->magento_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->magento_orders[$counter]->order_info["IsShipped"]=0;
				
			//show if cancelled
			if($this->GetMagentoField('','status')=="canceled")
				$this->magento_orders[$counter]->order_info["IsCancelled"]=1;
			else
				$this->magento_orders[$counter]->order_info["IsCancelled"]=0;
				
				
			 //handle closed order
			if($this->GetMagentoField('','status')=="closed")
			{
				$this->magento_orders[$counter]->order_info["IsCancelled"]=1;
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=0;
				$this->magento_orders[$counter]->order_info["IsShipped"]=0;
			}
			
			//Order Level Gift Message
			if(Magento_RetrieveOrderGiftMessage==1)
			{
				$message = Mage::getModel('giftmessage/message');
				$gift_message_id = $this->GetMagentoField('','gift_message_id');
				
				if(!is_null($gift_message_id)) 
				{
						$message->load((int)$gift_message_id);
						$this->magento_orders[$counter]->order_info["Comments"]=$this->GetGiftMessageText($message);
				}
			}
			
			
			
			//Get order products
			$actual_number_of_products=0;
			
			
			//Debug product data
			if($counter==(count($order_array)-1))
				$this->ShowRawData('print_product_array',$this->magento_orders_temp['items'],1);
			else
				$this->ShowRawData('print_product_array',$this->magento_orders_temp['items']);
			
			
			for($i=0;$i<count($this->GetMagentoField('','items'));$i++)
			{
				if(!isset($this->magento_orders_temp['items'][$i]["parent_item_id"]))
				{
				
					
					
					
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Name"]=$this->GetMagentoField('items','name',$i).$this->GetProductOptions($this->GetMagentoField('items','product_options',$i));
				
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=number_format($this->GetMagentoField('items','base_price',$i),2,'.','');
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["ExternalID"]=$this->GetMagentoField('items','sku',$i).$this->GetProductOptions($this->GetMagentoField('items','product_options',$i),"simple_sku");
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]=number_format($this->GetMagentoField('items','qty_ordered',$i),2,'.','');
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Total"]=number_format(($this->GetMagentoField('items','base_price',$i)*$this->GetMagentoField('items','qty_ordered',$i)),2,'.','');
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Total_Product_Weight"]=number_format(($this->GetMagentoField('items','weight',$i)*$this->GetMagentoField('items','qty_ordered',$i)),2,'.','');
				
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Notes"]="";
				
				//Product Level Gift Message
				if(Magento_RetrieveProductGiftMessage==1)
				{
					$gift_message_id = $this->GetMagentoField('items','gift_message_id',$i);
					if(!is_null($gift_message_id)) 
					{
							$message->load((int)$gift_message_id);
							$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Notes"]=$message->getData('message');
					}
				
				}
				
				$actual_number_of_products++;
				
				}
			}
			
			$this->magento_orders[$counter]->num_of_products=$actual_number_of_products;
			
			//Debug number of products
			if($counter==(count($order_array)-1))
				$this->ShowRawData('count_products',$order_id."-".$actual_number_of_products,1);
			else
				$this->ShowRawData('count_products',$order_id."-".$actual_number_of_products);
			
			
		}	
	
		
		
	}

	function GetGiftMessageText($message)
	{
           $result = "";
           if ($message->getData('sender')) $result = $result."From: ".$message->getData('sender')."\r\n";
           if ($message->getData('recipient')) $result = $result."To: ".$message->getData('recipient')."\r\n\r\n";
           $result = $result.$message->getData('message');
           return $result;
        }

	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			

			if (isset($this->magento_orders))
				return $this->magento_orders;
			else
               return array();  

			
	}
	  
	  #################################### Convert UTC time to Magento Format ################################################
	  /* Magento stores all times in UTC but not in ISO 8601 format.Hence, change "YYYY-MM-DDThh:mm:ssZ" to "YYYY-MM-DD hh:mm:ss"*/
	  #########################################################################################################################
	  function GetServerTimeLocalMagento($server_date_iso) 
	  {
			
			if(strpos($server_date_iso,"Z"))
			{
				$utc_fotmat_temp=str_replace("Z","",$server_date_iso);
				$server_date_utc=str_replace("T"," ",$utc_fotmat_temp);;//"T" & "Z" removed from UTC format(in ISO 8601)
				
			}
			return $server_date_utc;
	  }	
	   #################################### Convert Magento Format to UTC################################################
	  /* Magento stores all times in UTC but not in ISO 8601 format.Hence, format date to ISO 8601 i.e "YYYY-MM-DDThh:mm:ssZ" */
	  #########################################################################################################################
	  function ConvertServerTimeToUTCMagento($server_date_utc) 
	  {
		$utc_fotmat_temp=$server_date_utc."Z";
		$server_date_iso=str_replace(" ","T",$utc_fotmat_temp);;//"T" & "Z" removed from UTC format(in ISO 8601)
		return $server_date_iso;
	  }	

	#######################################################################################################

	
	
}
######################################### End of class ShippingZMagento ###################################################

	//create object & perform tasks based on command
	$obj_shipping_magento=new ShippingZMagento;
	$obj_shipping_magento->ExecuteCommand();	

?>