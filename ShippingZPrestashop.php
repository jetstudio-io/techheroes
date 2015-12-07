<?php

define("SHIPPINGZPRESTASHOP_VERSION","3.0.0.55139");

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
function Check_Include_File($filename,$presta_library_file=0)
{
	if(file_exists($filename))
	{
		return true;
	}
	else
	{
		if($presta_library_file)
		{
			echo "PrestaShop Web Service Library  file ($filename) is missing.<br>Please, make sure \"$filename\" file is present in the root folder of store i.e. same folder where ShippingZ integration files are placed.";
			exit;
		}
		else
		{
			echo "\"$filename\" is not accessible.";
			exit;
		}
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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZPRESTASHOP_VERSION && SHIPPINGZPRESTASHOP_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZPrestaShop.php [".SHIPPINGZPRESTASHOP_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


#########################################################################################################################
  
//Check for prestashop include files
$presta_library_file=1;
if(Check_Include_File("PSWebServiceLibrary.php",$presta_library_file))
require "PSWebServiceLibrary.php";

if(Check_Include_File(dirname(__FILE__).'/config/config.inc.php'))
require (dirname(__FILE__).'/config/config.inc.php');

$db_pdo=new PDO("mysql:host="._DB_SERVER_.";dbname="._DB_NAME_, _DB_USER_, _DB_PASSWD_);

############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZPrestaShop ######################################
class ShippingZPrestaShop extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		global $db_pdo;
		
		//check if prestashop database can be acessed or not
		$shipping = $db_pdo->prepare('SHOW COLUMNS FROM `'._DB_PREFIX_.'orders`');
		$shipping->execute();
		
    	if ($shipping->rowCount()) 
		{
			$this->display_msg=DB_SUCCESS_MSG;
			
		}
		else
		{
			$this->display_msg=DB_ERROR_MSG;
		}
		
	}
	
	############################################## Function GetOrderCountByDate #################################
	//Get order count
	#######################################################################################################
	function GetOrderCountByDate($datefrom,$dateto)
	{
		
		global $db_pdo;
		
		//Get order count based on data range
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$order_status_filter=$this->PreparePrestaShopOrderStatusFilter();
		
		$sql = "SELECT COUNT(*) as total_pending_orders FROM `"._DB_PREFIX_."orders` a
		LEFT JOIN `"._DB_PREFIX_."order_history` oh ON (oh.`id_order` = a.`id_order`)
		WHERE ".$order_status_filter."  (( DATE_FORMAT(a.date_add,\"%Y-%m-%d %T\") between :dateform and :dateto) OR  ( DATE_FORMAT(a.date_upd,\"%Y-%m-%d %T\") between :dateform  and :dateto)) AND oh.`id_order_history` = (SELECT MAX(`id_order_history`) FROM `"._DB_PREFIX_."order_history` moh WHERE moh.`id_order` = a.`id_order`)";
		
		$orders = $db_pdo->prepare($sql);
		
		$data=array(':dateform' => $this->GetServerTimeLocal(true,$datefrom), ':dateto' => $this->GetServerTimeLocal(true,$dateto));
		
		$orders->execute($data);
		
		foreach ($orders AS $order_result)
		{
			return $order_result['total_pending_orders'];
		}
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		
		global $db_pdo;
		
		$result = $db_pdo->prepare("SELECT id_customer FROM `"._DB_PREFIX_."orders` WHERE id_order=:id_order");
		$result->execute(array(':id_order' => $OrderNumber));
		
		//check if order number is valid
		if($result->rowCount()>0)
		{
			   foreach ($result AS $row)
		      {
					if($ShipDate!="")
						$shipped_on=$ShipDate;
					else
						$shipped_on=date("m/d/Y H:i:s");
						
					$shipping_str="";	
					$shipping_sql="";
						
					if($Carrier!="")
					{
						$shipping_str=$Carrier;
						$Carrier=" via ".$Carrier;
					}
					
					if($Service!="")
					{
						$Service=" [".$Service."]";
						$shipping_str.=$Service;
					}
					
					$TrackingNumberString="";
					if($TrackingNumber!="")
					$TrackingNumberString=", Tracking number $TrackingNumber";
						
					if($shipping_str!="")
					$shipping_sql=" ,shipping='".$shipping_str."'";
					
					$id_customer=$row['id_customer'];
			    }
				//get current status
								
				$result2 = $db_pdo->prepare("SELECT * FROM `"._DB_PREFIX_."order_history` WHERE  id_order_history=(SELECT MAX(`id_order_history`) FROM `"._DB_PREFIX_."order_history` WHERE `id_order` = :id_order)");
				$result2->execute(array(':id_order' => $OrderNumber));
				
				
				foreach($result2 as $row2)
				{
					$current_order_status=$row2['id_order_state'];
									
				}
							
				//prepare $comments
				$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
				
				
				//check prestashop version
				$prestashop_version=_PS_VERSION_;
				$prestashop_version_array=explode(".",$prestashop_version);
				$prestashop_version_beginning=$prestashop_version_array[0].".".$prestashop_version_array[1];
				
					
				//Update Prestashop Database
				if(PRESTASHOP_SHIPPED_STATUS_SET_TO_STATUS_3_SHIPPED==1)
				{
					//update order history table
									
					 $upate_order_history=$db_pdo->prepare("Insert into `"._DB_PREFIX_."order_history`
						  (id_order_state, date_add,id_order)
						  values (:id_order_state, :date_add,:id_order)");
				     $upate_order_history->execute(array(':id_order_state' =>4,':date_add' => date('Y-m-d H:i:s'),':id_order' =>$OrderNumber));	
					
									
					
					if($prestashop_version_beginning>1.4)
					{
						//update order table (current_state)
						$upate_order=$db_pdo->prepare("Update `"._DB_PREFIX_."orders` set current_state=:current_state where id_order=:id_order");
						$upate_order->execute(array(':current_state'=>4,':id_order' => $OrderNumber));
						
					}
				}
				else
				{
					if($current_order_status=='1' || $current_order_status=='12' || $current_order_status=='10' || $current_order_status=='11')
						$change_order_status=3;
					else if($current_order_status=='3')
						$change_order_status='5';
					else
						$change_order_status=$current_order_status;
						
						//update order history table
						 $upate_order_history=$db_pdo->prepare("Insert into `"._DB_PREFIX_."order_history`
						  (id_order_state, date_add,id_order)  values (:id_order_state, :date_add,:id_order)");
				 		 $upate_order_history->execute(array(':id_order_state' => $change_order_status,':date_add' => date('Y-m-d H:i:s'),':id_order' =>$OrderNumber));	
						
						if($prestashop_version_beginning>1.4)
						{
							//update order table (current_state)
							$upate_order=$db_pdo->prepare("Update `"._DB_PREFIX_."orders` set current_state=:current_state where id_order=:id_order");
						    $upate_order->execute(array(':current_state'=>$current_order_status,':id_order' => $OrderNumber));
						}
					
				}		
					//update message table
					 $upate_message_table=$db_pdo->prepare("Insert into `"._DB_PREFIX_."message`
						  (id_customer,id_employee, id_order,message,private,date_add) values (:id_customer,:id_employee, :id_order,:message,:private,:date_add)");
				     $upate_message_table->execute(array(':id_customer' => $id_customer,':id_employee' => 1,':id_order' =>$OrderNumber,':message' =>$comments,':private' => 1,':date_add' => date('Y-m-d H:i:s')));	
					 
					
					//update invoice and delivery details
					$order = new Order((int)($OrderNumber));
					$order->setInvoice(true);
					if($prestashop_version_beginning>1.4)
					{
						$order->setDelivery();
					}
					
				$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
			}
			else
			{
				//display error message
				$this->display_msg=str_replace("ENTERED_ORDERED_NUMBER","#$OrderNumber",INVAID_ORDER_NUMBER_ERROR_MSG);
				$this->SetXmlError(1,$this->display_msg);
			
			}
		
	}
	############################################## Function Fetch_DB_Orders #################################
	//Perform Database query & fetch orders based on date range
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		
		global $db_pdo;
		
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$order_status_filter=$this->PreparePrestaShopOrderStatusFilter();
		
		
		$sql = "SELECT  a.id_order, oh.id_order_state  FROM `"._DB_PREFIX_."orders` a
		LEFT JOIN `"._DB_PREFIX_."order_history` oh ON (oh.`id_order` = a.`id_order`)
		WHERE ".$order_status_filter."  (( DATE_FORMAT(a.date_add,\"%Y-%m-%d %T\") between :dateform and :dateto) OR  ( DATE_FORMAT(a.date_upd,\"%Y-%m-%d %T\") between :dateform  and :dateto)) AND oh.`id_order_history` = (SELECT MAX(`id_order_history`) FROM `"._DB_PREFIX_."order_history` moh WHERE moh.`id_order` = a.`id_order`)";
		
		$result = $db_pdo->prepare($sql);
		
		$data=array(':dateform' => $this->GetServerTimeLocal(true,$datefrom), ':dateto' => $this->GetServerTimeLocal(true,$dateto));
		
		$result->execute($data);
		
		$counter=0;
		foreach ($result AS $order_result)
		{
		
			$order_id=$order_result['id_order'];
			$id_order_state=$order_result['id_order_state'];
			
			//Extract Path to Prestashop
			$folder_path=$_SERVER['SCRIPT_NAME'];
			$folder_path_temp=explode("/",$folder_path);
			$actual_file_name=$folder_path_temp[count($folder_path_temp)-1];
			$folder_path="http://".$_SERVER['HTTP_HOST'].str_replace($actual_file_name,"",$folder_path);
			
			// call to retrieve order details
			$webService = new PrestaShopWebservice($folder_path, PRESTASHOP_API_KEY, false);

			try{
			
			//Get Order Details
			$xml = $webService->get(array('resource' => 'orders', 'id' => $order_id));
			$order_resources = $xml->children()->children();
				 
				 
			//Get Customer Details	 
			$opt = array('resource' => 'customers');			
			$opt['id'] = $order_resources->id_customer;
			
			$xml = $webService->get($opt);
			
			$prestashop_orders_temp = $xml->children()->children();
			
				 
			//Prepare order array
			$this->prestashop_orders[$counter]->orderid=$this->GetFieldNumber($order_resources,"id",-2);
			
						
			//Shipping details
			$address_result = $db_pdo->prepare("SELECT * FROM `"._DB_PREFIX_."address` where `id_address`=:id_address");
			$address_result->execute(array(':id_address' => $this->GetFieldNumber($order_resources,"id_address_delivery",-2)));
			
			
			foreach ($address_result AS $shipping_address)
			{
				
				$this->prestashop_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($shipping_address,"firstname");
				$this->prestashop_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($shipping_address,"lastname");							
				$this->prestashop_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($shipping_address,"company");
				$this->prestashop_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($shipping_address,"address1");
				$this->prestashop_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($shipping_address,"address2");
				$this->prestashop_orders[$counter]->order_shipping["City"]=$this->GetFieldString($shipping_address,"city");
				$this->prestashop_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($shipping_address,"postcode");
				$this->prestashop_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($shipping_address,"phone");
				
				$this->prestashop_orders[$counter]->order_shipping["State"]="";
				
							
				$state_result = $db_pdo->prepare("SELECT iso_code  from `"._DB_PREFIX_."state`  where `id_state` =:id_state");
				$state_result->execute(array(':id_state' =>$this->GetFieldNumber($shipping_address,"id_state")));
				
				foreach ($state_result AS $state_code)
				{
					$this->prestashop_orders[$counter]->order_shipping["State"]=$this->GetFieldString($state_code,"iso_code");
				}
			
				$country_result = $db_pdo->prepare("SELECT iso_code  from `"._DB_PREFIX_."country`  where `id_country` = :id_country");
				$country_result->execute(array(':id_country'=>$this->GetFieldNumber($shipping_address,"id_country")));
				
				
				foreach ($country_result AS $country_code)
				{
					$this->prestashop_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($country_code,"iso_code");
				}	
				
			
				
			}
			$this->prestashop_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($prestashop_orders_temp,"email",-2);
			
			//billing details
						
			$billing_address_result = $db_pdo->prepare("SELECT * FROM `"._DB_PREFIX_."address`  where `id_address` = :id_address");
			$billing_address_result->execute(array(':id_address' =>$this->GetFieldNumber($order_resources,"id_address_invoice",-2)));
			
			
			foreach ($billing_address_result AS $billing_address)
			{
				$this->prestashop_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($billing_address,"firstname");
				$this->prestashop_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($billing_address,"lastname");
				$this->prestashop_orders[$counter]->order_billing["Company"]=$this->GetFieldString($billing_address,"company");
				$this->prestashop_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($billing_address,"address1");
				$this->prestashop_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($billing_address,"address2");
				$this->prestashop_orders[$counter]->order_billing["City"]=$this->GetFieldString($billing_address,"city");
				$this->prestashop_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($billing_address,"postcode");
				$this->prestashop_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($billing_address,"phone");
				
				$this->prestashop_orders[$counter]->order_billing["State"]="";
				
				$state_result = $db_pdo->prepare("SELECT iso_code  from `"._DB_PREFIX_."state`  where `id_state` =:id_state");
				$state_result->execute(array(':id_state' =>$this->GetFieldNumber($billing_address,"id_state")));
				
				foreach ($state_result AS $state_code)
				{
					$this->prestashop_orders[$counter]->order_billing["State"]=$this->GetFieldString($state_code,"iso_code");
				}
			
				
				$sql_country = $db_pdo->prepare("SELECT iso_code  from `"._DB_PREFIX_."country`  where `id_country` = :id_country");
				$country_result->execute(array(':id_country'=>$this->GetFieldNumber($billing_address,"id_country")));
				
				foreach ($country_result AS $country_code)
				{
					$this->prestashop_orders[$counter]->order_billing["Country"]=$this->GetFieldString($country_code,"iso_code");
				}	
				
			}
			
			//order info
			$this->prestashop_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,strtotime(stripslashes($this->GetFieldString($order_resources,"date_add",-2))));
			
			$this->prestashop_orders[$counter]->order_info["ItemsTotal"]=stripslashes($this->GetField($order_resources,"total_products",-2));
			$this->prestashop_orders[$counter]->order_info["Total"]=stripslashes($this->GetField($order_resources,"total_paid",-2));
			$this->prestashop_orders[$counter]->order_info["ShippingChargesPaid"]=stripslashes($this->GetField($order_resources,"total_shipping",-2));
			
			$shipping_result = $db_pdo->prepare("SELECT name  from `"._DB_PREFIX_."carrier`  where `id_carrier` =:id_carrier");
			$shipping_result->execute(array(':id_carrier' => $this->GetFieldNumber($order_resources,"id_carrier",-2)));
			
			
			
			$this->prestashop_orders[$counter]->order_info["ShipMethod"]="Not Available";
			if($shipping_result->rowCount()>0)
			{
				foreach ($shipping_result AS $shipping_method_details)
				{
					$this->prestashop_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($shipping_method_details,"name");
				}
			}
			
			$this->prestashop_orders[$counter]->order_info["ItemsTax"]=0;
			$this->prestashop_orders[$counter]->order_info["Comments"]=""; 
			
			$this->prestashop_orders[$counter]->order_info["OrderNumber"]=$this->prestashop_orders[$counter]->orderid;
			
			//get payment type
			$this->prestashop_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($order_resources,"payment",-2));
			
			if($id_order_state!="1" && $id_order_state!="10" && $id_order_state!="11")
				$this->prestashop_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->prestashop_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status	
			if($id_order_state=="4" || $id_order_state=="5")
				$this->prestashop_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->prestashop_orders[$counter]->order_info["IsShipped"]=0;
			
			$this->prestashop_orders[$counter]->order_info["Comments"]="";
			
			//Customer Comments
			$comments_result = $db_pdo->prepare("SELECT *  from `"._DB_PREFIX_."message`  where `id_customer`=:id_customer and `id_order` =:id_order ");
			$comments_result->execute(array('id_customer'=>$order_resources->id_customer,':id_order' => $this->prestashop_orders[$counter]->orderid));
			
			
			foreach ($comments_result AS $comment_data_arr)
			{
				$this->prestashop_orders[$counter]->order_info["Comments"]=$this->GetFieldString($comment_data_arr,"message");
			}
			
			//Gift message
			if($this->GetFieldNumber($order_resources,"gift",-2)=="1")
			{
				if($this->prestashop_orders[$counter]->order_info["Comments"]!="")
				$this->prestashop_orders[$counter]->order_info["Comments"].=" (Gift Message-".stripslashes($this->GetFieldString($order_resources,"gift_message",-2)).")";
				else
				$this->prestashop_orders[$counter]->order_info["Comments"].="Gift Message-".stripslashes($this->GetFieldString($order_resources,"gift_message",-2));
			}
			//Get Products
			$product_result = $db_pdo->prepare("SELECT *  from `"._DB_PREFIX_."order_detail`  where `id_order` =:id_order ");
			$product_result->execute(array(':id_order' => $this->prestashop_orders[$counter]->orderid));
			
			
			$i=0;
			foreach ($product_result AS $product_data_arr)
			{
				$this->prestashop_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_data_arr,"product_name");
				$this->prestashop_orders[$counter]->order_product[$i]["Price"]=$this->GetFieldMoney($product_data_arr,"product_price");
				
				//reduction price
				$reduction_amount=$this->GetFieldMoney($product_data_arr,"reduction_amount");
				$reduction_percent=$this->GetFieldMoney($product_data_arr,"reduction_percent");
				
				if($reduction_percent!="0.00")
				$reduction_amount=($reduction_percent/100)*$this->prestashop_orders[$counter]->order_product[$i]["Price"];
				
				$this->prestashop_orders[$counter]->order_product[$i]["Price"]=$this->prestashop_orders[$counter]->order_product[$i]["Price"]-$reduction_amount;
				
				$this->prestashop_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_data_arr,"product_quantity");
				$this->prestashop_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->prestashop_orders[$counter]->order_product[$i]["Price"]*$this->GetFieldNumber($product_data_arr,"product_quantity"));	
				 $this->prestashop_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($product_data_arr,"product_weight")*$this->GetFieldNumber($product_data_arr,"product_quantity");
				 
				$tax= ($this->prestashop_orders[$counter]->order_product[$i]["Price"]*$this->GetFieldNumber($product_data_arr,"tax_rate")*$this->GetFieldNumber($product_data_arr,"product_quantity"))/100;
				 $this->prestashop_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_data_arr,"product_id");
				 $this->prestashop_orders[$counter]->order_info["ItemsTax"]+=$tax;
				 $i++;
			}
			 $this->prestashop_orders[$counter]->num_of_products=$i;
			
			
			}
			catch (PrestaShopWebserviceException $ex)
			{
				 //$trace = $ex->getTrace();
				 echo "Could not access prestashop API";
				 exit;
			}
			
			
			
			$counter++;
			
		}
		
			
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			

			if (isset($this->prestashop_orders))
				return $this->prestashop_orders;
			else
                       		return array();  
			
	}
	################################################ Function PrepareOrderStatusString #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PreparePrestaShopOrderStatusFilter()
	{
			
			$order_status_filter="";
			
			if(PRESTASHOP_RETRIEVE_ORDER_STATUS_1_PAYMENT_ACCEPTED==1)//Payment accepted,Payment remotely accepted
			{
				$order_status_filter="  oh.id_order_state='13' OR  oh.id_order_state='12'  OR  oh.id_order_state='2' ";
			
			}
			if(PRESTASHOP_RETRIEVE_ORDER_STATUS_2_AWAITING_PAYMENT==1)//Payment pending
			{
				if($order_status_filter=="")
				{
					$order_status_filter.="  oh.id_order_state='1' OR oh.id_order_state='10' OR oh.id_order_state='11'   ";
				}
				else
				{
					$order_status_filter.=" OR  oh.id_order_state='1' OR oh.id_order_state='10' OR oh.id_order_state='11' ";
				}
			
			}
			if(PRESTASHOP_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)//Preparation in progress
			{
				if($order_status_filter=="")
				{
					$order_status_filter.="  oh.id_order_state='3' ";
				}
				else
				{
					$order_status_filter.=" OR  oh.id_order_state='3' ";
				}
			
			}
			if(PRESTASHOP_RETRIEVE_ORDER_STATUS_3_SHIPPED==1)//Shipped,Delivered
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" oh.id_order_state='4' OR oh.id_order_state='5'";
				}
				else
				{
					$order_status_filter.=" OR oh.id_order_state='4' OR oh.id_order_state='5'";
				}
			
			}
			
			if($order_status_filter!="")
			$order_status_filter="( ".$order_status_filter." ) and";
			return $order_status_filter;
			
	}
		
	
}
######################################### End of class ShippingZPrestaShop ###################################################

	// create object & perform tasks based on command

	$obj_shipping_prestashop=new ShippingZPrestaShop;
	$obj_shipping_prestashop->ExecuteCommand();

?>