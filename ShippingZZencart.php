<?php

define("SHIPPINGZZENCART_VERSION","3.0.0.55139");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZZENCART_VERSION && SHIPPINGZZENCART_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZZencart.php [".SHIPPINGZZENCART_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}

// Check for zencart include files
if(Check_Include_File('includes/application_top.php'))
require('includes/application_top.php');

###############################################################################################################
//Zencart uses "zen_catalog_href_link()" function to prepare formatted links with different parameters.
//This is not required for displaying our order details.
//It is observed, sometimes due to alteration/customization "zen_catalog_href_link()" function becomes not accessible.
//Hence, define this function to make sure that our code does not break due to zencart undefined zen_catalog_href_link() error.
if(!function_exists(zen_catalog_href_link))
{
	 function zen_catalog_href_link($page = '', $parameters = '', $connection = '')  { }
}
###############################################################################################################

if(Check_Include_File(ZENCART_ADMIN_DIRECTORY.'/includes/classes/object_info.php'))
require(ZENCART_ADMIN_DIRECTORY.'/includes/classes/object_info.php');

if(Check_Include_File(ZENCART_ADMIN_DIRECTORY.'/includes/classes/order.php'))
require(ZENCART_ADMIN_DIRECTORY.'/includes/classes/order.php');
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZZencart ######################################
class ShippingZZencart extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		global $db;
        if (!defined('DB_PREFIX')) define('DB_PREFIX', '');
			
		//check if zencart database can be acessed or not
		$sql = "SHOW COLUMNS FROM ".DB_PREFIX."orders";
		$result = $db->execute($sql);
		
        if ($result->RecordCount()) 
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
		global $db;
		
		$order_status_filter=$this->PrepareZencartOrderStatusFilter();
		
		//Get order count based on data range,options			
		$sql = "SELECT * FROM ".DB_PREFIX."orders WHERE ".$order_status_filter."  (( DATE_FORMAT(last_modified,\"%Y-%m-%d %T\") between '".$this->MakeSqlSafe($this->GetServerTimeLocal(true,$datefrom))."' and '".$this->MakeSqlSafe($this->GetServerTimeLocal(true,$dateto))."') OR  ( DATE_FORMAT(date_purchased,\"%Y-%m-%d %T\") between '".$this->MakeSqlSafe($this->GetServerTimeLocal(true,$datefrom))."' and '".$this->MakeSqlSafe($this->GetServerTimeLocal(true,$dateto))."') )";
		
		$result = $db->execute($sql);
		
		return $result->RecordCount();
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		global $db;
		
		$sql = "SELECT * FROM ".DB_PREFIX."orders WHERE orders_id=".$this->MakeSqlSafe($OrderNumber,1);
		$result = $db->execute($sql);
		
		//check if order number is valid
		if($result->RecordCount()>0)
		{
		
			if($ShipDate!="")
				$shipped_on=$ShipDate;
			else
				$shipped_on=date("m/d/Y");
				
			if($Carrier!="")
			$Carrier=" via ".$Carrier;
			
			if($Service!="")
			$Service=" [".$Service."]";
			
			$TrackingNumberString="";
			if($TrackingNumber!="")
			$TrackingNumberString=", Tracking number $TrackingNumber";
			
			$current_order_status=$result->fields['orders_status'];
								
			//prepare $comments & save it
			$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
			
			if(ZENCART_SHIPPED_STATUS_SET_TO_STATUS_3_DELIVERED==1)
			{
				 $db->execute("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
						  (orders_id, orders_status_id, date_added, customer_notified, comments)
						  values ('" . $this->MakeSqlSafe($OrderNumber,1) . "', '3', now(), '0', '" . $this->MakeSqlSafe($comments). "')");
						  
				//update order status
				 $db->execute(" update ".DB_PREFIX."orders set orders_status='3' where orders_id='". $this->MakeSqlSafe($OrderNumber,1) ."'");
			 } 
			 else
			 {
			 	
				if($current_order_status==1)
					$change_order_status=2;
				else if($current_order_status==2)
					$change_order_status=3;
				else
					$change_order_status=$current_order_status;
				
				 $db->execute("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
						  (orders_id, orders_status_id, date_added, customer_notified, comments)
						  values ('" . $this->MakeSqlSafe($OrderNumber,1) . "', '".$change_order_status."', now(), '0', '" . $this->MakeSqlSafe($comments). "')");
						  
				if($change_order_status!=$current_order_status)
				$db->execute(" update ".DB_PREFIX."orders set orders_status='$change_order_status' where orders_id='". $this->MakeSqlSafe($OrderNumber,1) ."'");

			 
			 }
			
			$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
		}
		else
		{
			//display error message
			$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
			$this->SetXmlError(1,$this->display_msg);
		
		}
	}
	############################################## Function Fetch_DB_Orders #################################
	//Perform Database query & fetch orders based on date range
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		global $db;
		$order_status_filter=$this->PrepareZencartOrderStatusFilter();
		
		$search=$order_status_filter."  (( DATE_FORMAT(last_modified,\"%Y-%m-%d %T\") between '".$this->MakeSqlSafe($this->GetServerTimeLocal(true,$datefrom))."' and '".$this->MakeSqlSafe($this->GetServerTimeLocal(true,$dateto))."') OR (DATE_FORMAT(date_purchased,\"%Y-%m-%d %T\") between '".$this->MakeSqlSafe($this->GetServerTimeLocal(true,$datefrom))."' and '".$this->MakeSqlSafe($this->GetServerTimeLocal(true,$dateto))."') )";

		$orders_query_raw = "select orders_id from ".TABLE_ORDERS." where ".$search ." order by orders_id DESC";
		
		  
		$zencart_orders_res = $db->execute($orders_query_raw);
		$counter=0;
		while (!$zencart_orders_res->EOF) 
		{
			$zencart_orders_temp=new order($this->GetFieldNumber($zencart_orders_res->fields,"orders_id"));
			
			$flag=0;
			//Extract shipping charges	
			
			//initialize shipping cost	
			$shipping_charges=0;
			//get shipping method from order info
			$shipping_method=$this->GetFieldString($zencart_orders_temp->info,"shipping_method");
			
			//Check if $zencart_orders_temp->totals exists & it is an array
			if(isset($zencart_orders_temp->totals) && is_array($zencart_orders_temp->totals))
			{
				foreach($zencart_orders_temp->totals as $key=>$value)
				{
					//if "ot_shipping" module exists then get shipping details using this module
					if(in_array("ot_shipping",$value))
					{
					
						$shipping_method=$value['title'];
						$shipping_method=str_replace(":","",$shipping_method);
						$shipping_charges=$value['text'];
						$flag++;
						break;
					}
				
				}
				if(isset($shipping_charges))
				$shipping_charges=substr($shipping_charges,1);
			}
			
			
			//prepare order array
			$this->zencart_orders[$counter]->orderid=$this->GetFieldNumber($zencart_orders_res->fields,"orders_id");
			$this->zencart_orders[$counter]->num_of_products=count($this->GetClassProperty($zencart_orders_temp,"products"));
			
			//shipping details
			$this->zencart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($zencart_orders_temp->delivery,"name");
			$this->zencart_orders[$counter]->order_shipping["LastName"]="";
			$this->zencart_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($zencart_orders_temp->delivery,"company");
			$this->zencart_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($zencart_orders_temp->delivery,"street_address");
			$this->zencart_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($zencart_orders_temp->delivery,"suburb");
			$this->zencart_orders[$counter]->order_shipping["City"]=$this->GetFieldString($zencart_orders_temp->delivery,"city");
			$this->zencart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($zencart_orders_temp->delivery,"state");
			$this->zencart_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($zencart_orders_temp->delivery,"postcode");
			$this->zencart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($zencart_orders_temp->delivery,"country");
			$this->zencart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($zencart_orders_temp->customer,"telephone");
			$this->zencart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($zencart_orders_temp->customer,"email_address");
			
			//billing details
			$this->zencart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($zencart_orders_temp->billing,"name");
			$this->zencart_orders[$counter]->order_billing["LastName"]="";
			$this->zencart_orders[$counter]->order_billing["Company"]=$this->GetFieldString($zencart_orders_temp->billing,"company");
			$this->zencart_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($zencart_orders_temp->billing,"street_address");
			$this->zencart_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($zencart_orders_temp->billing,"suburb");
			$this->zencart_orders[$counter]->order_billing["City"]=$this->GetFieldString($zencart_orders_temp->billing,"city");
			$this->zencart_orders[$counter]->order_billing["State"]=$this->GetFieldString($zencart_orders_temp->billing,"state");
			$this->zencart_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($zencart_orders_temp->billing,"postcode");
			$this->zencart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($zencart_orders_temp->billing,"country");
			$this->zencart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($zencart_orders_temp->customer,"telephone");
			
			//order info
			$this->zencart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,strtotime($this->GetFieldString($zencart_orders_temp->info,"date_purchased")));
			
			
			
			$this->zencart_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber(($this->GetFieldNumber($zencart_orders_temp->info,"total")-$this->GetFieldNumber($zencart_orders_temp->info,"tax")-$shipping_charges));
			
			$this->zencart_orders[$counter]->order_info["Total"]=$this->GetFieldMoney($zencart_orders_temp->info,"total");
			
			$this->zencart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->FormatNumber($shipping_charges);
			
			$this->zencart_orders[$counter]->order_info["ShipMethod"]=$shipping_method;
			
			$this->zencart_orders[$counter]->order_info["ItemsTax"]=$this->GetFieldMoney($zencart_orders_temp->info,"tax");;
			
			$this->zencart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($zencart_orders_res->fields,"orders_id");
			
			$this->zencart_orders[$counter]->order_info["PaymentType"]="";
			
			if($this->GetFieldString($zencart_orders_temp->info,"payment_method")!="")
			$this->zencart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($zencart_orders_temp->info,"payment_method"));
			
			if($this->GetFieldNumber($zencart_orders_temp->info,"orders_status")!="1")
				$this->zencart_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->zencart_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Order status	
			if($this->GetFieldNumber($zencart_orders_temp->info,"orders_status")=="3")
				$this->zencart_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->zencart_orders[$counter]->order_info["IsShipped"]=0;
			
			//Cancelled Order status
			if(ZENCART_CANCELLED_ORDER_STATUS_ID!=0)
			{
				if($this->GetFieldNumber($zencart_orders_temp->info,"orders_status")==ZENCART_CANCELLED_ORDER_STATUS_ID)
				$this->zencart_orders[$counter]->order_info["IsCancelled"]=1;
			}
			
			
			//Get Customer Comments
			$res_order_details = $db->Execute("SELECT * FROM ".DB_PREFIX."orders_status_history WHERE orders_id=".$this->zencart_orders[$counter]->orderid." order by orders_status_history_id");
			$this->zencart_orders[$counter]->order_info["Comments"]=$this->MakeXMLSafe($this->GetFieldString($res_order_details->fields,"comments"));
			$product_id_arr="";		
			$limit_str="";
					
			//Get order products
			for($i=0;$i<count($zencart_orders_temp->products);$i++)
			{	
				$this->zencart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($zencart_orders_temp->products,"name",$i);
				$this->zencart_orders[$counter]->order_product[$i]["Price"]=$this->FormatNumber($this->GetFieldNumber($zencart_orders_temp->products,"final_price",$i));
				$this->zencart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($zencart_orders_temp->products,"model",$i);
				$this->zencart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($zencart_orders_temp->products,"qty",$i);
				$this->zencart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->GetFieldNumber($zencart_orders_temp->products,"final_price",$i)*$this->GetFieldNumber($zencart_orders_temp->products,"qty",$i));
				
				//Get product weight  
			
				if($this->GetFieldNumber($zencart_orders_temp->products,"id",$i)!="")
				{
					$product_id=$this->GetFieldNumber($zencart_orders_temp->products,"id",$i);
				}
				else
				{
					$res_product = $db->Execute("select p.products_id from " . TABLE_PRODUCTS . " p  where p.products_model = '" . $this->GetFieldNumber($zencart_orders_temp->products,"model",$i) . "'");
					$product_id=$this->GetClassPropertyNumber($res_product,"fields","products_id");
				}
				
				
				$res_product = $db->Execute("select p.products_weight from " . TABLE_PRODUCTS . " p  where p.products_id = '" . $product_id . "'");
				
				
				
				$product_weight=$this->GetClassPropertyNumber($res_product,"fields","products_weight");
				
				//check if same product exits with different attributes
				if(is_array($product_id_arr))
				{
					if(in_array($product_id,$product_id_arr))
					{
						$array_counter=array_count_values($product_id_arr);
						$limit_str=" limit ".$array_counter[$product_id]." ,1";
						
					}
				
				}
				
				//Get product weight related to attributes and adjust product weight accordingly
				$res_order_products = $db->Execute("select op.orders_products_id from " . TABLE_ORDERS_PRODUCTS . " op  where op.products_id = '" . $product_id . "' and op.orders_id = '" . $this->zencart_orders[$counter]->order_info["OrderNumber"]. "' order by orders_products_id asc $limit_str");
				
				$product_id_arr[$i]=$product_id;
				
				$attributes_arr="";
				$prod_attributes="";
				$attributes_arr=$this->GetFieldString($zencart_orders_temp->products,"attributes",$i);
				if(is_array($attributes_arr))
				{
					foreach($attributes_arr as $key=>$val)
					{
						if($prod_attributes!="")
						$prod_attributes=$prod_attributes.",";
						
						$prod_attributes=$prod_attributes.$val['option']."-".$val['value'];
						
							$res_product_attributes = $db->Execute("select opa.products_attributes_weight_prefix,opa.products_attributes_weight,opa.products_options,opa.	products_options_values from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa  where opa.orders_products_id = '" . $this->GetClassPropertyNumber($res_order_products,"fields","orders_products_id") . "' and opa.orders_id = '" . $this->zencart_orders[$counter]->order_info["OrderNumber"]. "' ");
				
							
				
							while(!$res_product_attributes->EOF)
							{
								if($this->GetClassPropertyNumber($res_product_attributes,"fields","products_attributes_weight")!=0)
								{
									
									//Add or subtract attribute weight based on value of "products_attributes_weight_prefix"
									if($this->GetClassPropertyNumber($res_product_attributes,"fields","products_attributes_weight_prefix")=="-")
									{
										$product_weight=$product_weight-$this->GetClassPropertyNumber($res_product_attributes,"fields","products_attributes_weight");
									}
									else
									{
										$product_weight=$product_weight+$this->GetClassPropertyNumber($res_product_attributes,"fields","products_attributes_weight");
									}
								}
								
								
													
								$res_product_attributes->MoveNext();
							}
					}
				
				}
								
				
				//Calculate total product weight
				$this->zencart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$product_weight*$this->GetFieldNumber($zencart_orders_temp->products,"qty",$i);
				
				//Add selected attributes to product name
				if($prod_attributes!="")
				$this->zencart_orders[$counter]->order_product[$i]["Name"]=$this->zencart_orders[$counter]->order_product[$i]["Name"]."(".$prod_attributes.")";
			}
			
			
			
			
			$zencart_orders_res->MoveNext();
			$counter++;
		}	
		
		
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			
			if (isset($this->zencart_orders))
				return $this->zencart_orders;
			else
                       		return array();  

		
	}
	
	################################################ Function PrepareOrderStatusString #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareZencartOrderStatusFilter()
	{
			
			$order_status_filter="";
			
			if(ZENCART_RETRIEVE_ORDER_STATUS_1_PENDING==1)
			{
				$order_status_filter=" orders_status=1 ";
			
			}
			if(ZENCART_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" orders_status=2 ";
				}
				else
				{
					$order_status_filter.=" OR orders_status=2 ";
				}
			
			}
			if(ZENCART_RETRIEVE_ORDER_STATUS_3_DELIVERED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" orders_status=3 ";
				}
				else
				{
					$order_status_filter.=" OR orders_status=3 ";
				}
			
			}
			if(ZENCART_RETRIEVE_ORDER_STATUS_4_UPDATE==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" orders_status=4 ";
				}
				else
				{
					$order_status_filter.=" OR orders_status=4 ";
				}
			
			}
			if(ZENCART_RETRIEVE_ORDER_STATUS_5_CANCELLED==1 && ZENCART_CANCELLED_ORDER_STATUS_ID!=0)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" orders_status=".ZENCART_CANCELLED_ORDER_STATUS_ID;
				}
				else
				{
					$order_status_filter.=" OR orders_status=".ZENCART_CANCELLED_ORDER_STATUS_ID;
				}
			}
			
			if($order_status_filter!="")
			$order_status_filter="( ".$order_status_filter." ) and";
			return $order_status_filter;
			
	}
	
	
}
######################################### End of class ShippingZZencart ###################################################

	//create object & perform tasks based on command
	$obj_shipping_zencart=new ShippingZZencart;
	$obj_shipping_zencart->ExecuteCommand();	

?>