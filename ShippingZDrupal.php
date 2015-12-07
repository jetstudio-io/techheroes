<?php

define("SHIPPINGZDRUPAL_VERSION","3.0.0.55139");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZDRUPAL_VERSION && SHIPPINGZDRUPAL_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZDrupal.php [".SHIPPINGZDRUPAL_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


// Check for Drupal bootstrap file and bootstrap the DB 
define("DRUPAL_ROOT",getcwd());
if(Check_Include_File('includes/bootstrap.inc'))
require('includes/bootstrap.inc');
drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
// Can now access DB with Drupal functions
 
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZDrupal ######################################
class ShippingZDrupal extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		
       	//check if drupal database can be acessed or not
		$result = db_query('SHOW COLUMNS FROM {commerce_order}');
		
 		 if ($result->rowCount()>0) 
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
		global $active_db;
		
		$order_status_filter=$this->PrepareDrupalOrderStatusFilter();
		
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		//Get pending order count based on data range			
		$sql = "SELECT * FROM {commerce_order}  WHERE ".$order_status_filter."  ((changed between '".$datefrom_timestamp."' and '".$dateto_timestamp."') ||(created between '".$datefrom_timestamp."' and '".$dateto_timestamp."'))";
		
		$result = db_query($sql);
		
		return $result->rowCount();
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		$sql = "SELECT COUNT(*) as total_order FROM {commerce_order} WHERE order_id='".$OrderNumber."' and status in('pending', 'processing', 'completed')";
		
		$result = db_query($sql);
		$row = $result->fetchAssoc();
		//check if order number is valid
		if($row ['total_order']>0)
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
			
			//get existing order status
			$sql = "SELECT * FROM {commerce_order} WHERE  order_id='".$OrderNumber."'";
			$result = db_query($sql);
			$row = $result->fetchAssoc();
			$current_order_status=$row['status'];
			
			//prepare $comments 
			$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
			
			$orders_query_raw = "select mail from {commerce_order}  where  commerce_order.order_id=".$OrderNumber;
				  
			$drupal_orders_res = db_query($orders_query_raw);
		    $drupal_orders_row=$drupal_orders_res->fetchAssoc();
			$customer_email=$drupal_orders_row['mail'];
					
			//update order & order revision table
			if(DRUPAL_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE==1)
			{
			 	db_query("Insert into  {commerce_order_revision} set status='completed',revision_uid='1', revision_timestamp='".time()."',order_id=".$OrderNumber." ,order_number=".$OrderNumber." , log='".$comments."', mail='".$customer_email."'");
				
				$revision_result=db_query("select revision_id from {commerce_order_revision} where order_id=".$OrderNumber." and order_number=".$OrderNumber." order by revision_id desc");
				$revision_row = $revision_result->fetchAssoc();
				$revision_id=$revision_row['revision_id'];
				
				db_query("update {commerce_order} set revision_id='".$revision_id."', status='completed',changed='".time()."'  where order_id=".$OrderNumber);
							
				
			}
			else
			{
				if($current_order_status=='pending'  )
					$change_order_status='processing';
				else if($current_order_status=='processing')
					$change_order_status='completed';
				else
					$change_order_status=$current_order_status;
					
				db_query("Insert into  {commerce_order_revision} set status='".$change_order_status."',revision_uid='1', revision_timestamp='".time()."',order_id=".$OrderNumber." ,order_number=".$OrderNumber." , log='".$comments."', mail='".$customer_email."'");
				
				$revision_result=db_query("select revision_id from {commerce_order_revision} where order_id=".$OrderNumber." and order_number=".$OrderNumber." order by revision_id desc");
				$revision_row = $revision_result->fetchAssoc();
				$revision_id=$revision_row['revision_id'];
				
				db_query("update {commerce_order} set revision_id='".$revision_id."', status='".$change_order_status."', changed='".time()."'   where order_id=".$OrderNumber);
				
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
		global $active_db;
		
		$order_status_filter=$this->PrepareDrupalOrderStatusFilter();
		
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$search=$order_status_filter." ((changed between '".$datefrom_timestamp."' and '".$dateto_timestamp."') ||(created between '".$datefrom_timestamp."' and '".$dateto_timestamp."'))";

		$orders_query_raw = "select * from {commerce_order},{field_data_commerce_order_total} where ".$search ." and  {commerce_order}.order_id={field_data_commerce_order_total}.entity_id order by order_id DESC";
				  
		$drupal_orders_res = db_query($orders_query_raw);
		$counter=0;
		
		while ($drupal_orders_row=$drupal_orders_res->fetchAssoc()) 
		{
			
			//prepare order array
			$this->drupal_orders[$counter]->orderid=$this->GetFieldNumber($drupal_orders_row,"order_id");
				
			$profile_query_raw = "select * from {field_data_commerce_customer_billing} where entity_type='commerce_order' and entity_id =".$this->GetFieldNumber($drupal_orders_row,"order_id");
			$drupal_profile_res = db_query($profile_query_raw);
			$drupal_profile_row=$drupal_profile_res->fetchAssoc();
			$commerce_customer_billing_profile_id=$this->GetFieldNumber($drupal_profile_row,"commerce_customer_billing_profile_id");
			$profile_query_raw_ship = "select * from {field_data_commerce_customer_shipping} where entity_type='commerce_order' and entity_id =".$this->GetFieldNumber($drupal_orders_row,"order_id");
			$drupal_profile_res_ship = db_query($profile_query_raw_ship);
			$drupal_profile_row_ship=$drupal_profile_res_ship->fetchAssoc();
			$commerce_customer_shipping_profile_id=$this->GetFieldNumber($drupal_profile_row_ship,"commerce_customer_shipping_profile_id");
			
			$customer_shipping_query_raw = "select * from {field_data_commerce_customer_address} where bundle='shipping' and entity_id =".$commerce_customer_shipping_profile_id;
			$drupal_customer_shipping_res = db_query($customer_shipping_query_raw);
			$drupal_customer_shipping_row=$drupal_customer_shipping_res->fetchAssoc();
			
			
			//shipping details
			if($this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_first_name")!="")
			{
				$this->drupal_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_first_name");
				$this->drupal_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_last_name");
			}
			else
			{
				$temp_name=explode(" ",$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_name_line"));//split full name
				if(count($temp_name)>1)
				{
					$this->drupal_orders[$counter]->order_shipping["LastName"]=$temp_name[count($temp_name)-1];
					$this->drupal_orders[$counter]->order_shipping["FirstName"]="";
					for($k=0;$k<count($temp_name)-1;$k++)
					$this->drupal_orders[$counter]->order_shipping["FirstName"].=$temp_name[$k];
				}
				else
				{
					$this->drupal_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_name_line");
					$this->drupal_orders[$counter]->order_shipping["LastName"]="";
				}
				
			}
			$this->drupal_orders[$counter]->order_shipping["Company"]="";
			$this->drupal_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_thoroughfare");			
			$this->drupal_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_premise");
			$this->drupal_orders[$counter]->order_shipping["City"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_locality");		
			$this->drupal_orders[$counter]->order_shipping["State"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_administrative_area");
			$this->drupal_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_postal_code");			
			$this->drupal_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($drupal_customer_shipping_row,"commerce_customer_address_country");
			$this->drupal_orders[$counter]->order_shipping["Phone"]="";
			$this->drupal_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($drupal_orders_row,"mail");
			
			
			$customer_billing_query_raw = "select * from {field_data_commerce_customer_address} where bundle='billing' and entity_id =".$commerce_customer_billing_profile_id;
			$drupal_customer_billing_res = db_query($customer_billing_query_raw);
			$drupal_customer_billing_row=$drupal_customer_billing_res->fetchAssoc();
			
			//billing details
			if($this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_first_name")!="")
			{
				$this->drupal_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_first_name");
				$this->drupal_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_last_name");
				
			}
			else
			{
				$this->drupal_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_name_line");
				$this->drupal_orders[$counter]->order_billing["LastName"]="";
				$temp_name=explode(" ",$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_name_line"));//split full name
				
				if(count($temp_name)>1)
				{
					$this->drupal_orders[$counter]->order_billing["LastName"]=$temp_name[count($temp_name)-1];
					$this->drupal_orders[$counter]->order_billing["FirstName"]="";
					for($k=0;$k<count($temp_name)-1;$k++)
					$this->drupal_orders[$counter]->order_billing["FirstName"].=$temp_name[$k];
				}
				else
				{
					$this->drupal_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_name_line");
					$this->drupal_orders[$counter]->order_billing["LastName"]="";
				}
			}
			
					
			$this->drupal_orders[$counter]->order_billing["Company"]="";
			$this->drupal_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_thoroughfare");			
			$this->drupal_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_premise");
			$this->drupal_orders[$counter]->order_billing["City"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_locality");
			
						
			$this->drupal_orders[$counter]->order_billing["State"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_administrative_area");
			$this->drupal_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_postal_code");
			
			
			$this->drupal_orders[$counter]->order_billing["Country"]=$this->GetFieldString($drupal_customer_billing_row,"commerce_customer_address_country");
			$this->drupal_orders[$counter]->order_billing["Phone"]="";
			$this->drupal_orders[$counter]->order_billing["EMail"]=$this->GetFieldString($drupal_customer_billing_row,"mail");
			
			//order info
			$this->drupal_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,$this->GetFieldString($drupal_orders_row,"created"));
			
			$this->drupal_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($drupal_orders_row,"order_id");
			
			//get payment method
			$commerce_order_data=unserialize($this->GetFieldString($drupal_orders_row,"data"));
			$this->drupal_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($commerce_order_data,"payment_method"));
			
			//get shipping charges/taxes
			$this->drupal_orders[$counter]->order_info["ShippingChargesPaid"]=0;
			$this->drupal_orders[$counter]->order_info["ShipMethod"]="";
			
			
			//Extract tax amount
			$commerce_order_total_data=unserialize($this->GetFieldString($drupal_orders_row,"commerce_order_total_data"));
			$tax_assign=0;
			$ship_assign=0;
			
			$this->drupal_orders[$counter]->order_info["ItemsTax"]=0;
			
			foreach($commerce_order_total_data['components'] as $key=>$val)
			{			
				foreach($val as $key2=>$val2)
				{
					if(!is_array($val2))
					{
						if(strstr($val2,"tax") )
						$tax_assign=1;
					}
					else if($tax_assign)
					{
						$this->drupal_orders[$counter]->order_info["ItemsTax"]=$this->FormatNumber($val2['amount']/100,2);
						$tax_assign=0;
					}
					if(!is_array($val2))
					{
						if(strstr($val2,"usps")|| strstr($val2,"ups") )
						{
							$ship_assign=1;
							$this->drupal_orders[$counter]->order_info["ShipMethod"]=$val2;
							
							//Make the text user friendly
							$this->drupal_orders[$counter]->order_info["ShipMethod"]=str_replace("flat_rate","",$this->drupal_orders[$counter]->order_info["ShipMethod"]);
							$this->drupal_orders[$counter]->order_info["ShipMethod"]=str_replace("_"," ",$this->drupal_orders[$counter]->order_info["ShipMethod"]);
							$this->drupal_orders[$counter]->order_info["ShipMethod"]=strtoupper($this->drupal_orders[$counter]->order_info["ShipMethod"]);
						}
					}
					else if($ship_assign)
					{
						$this->drupal_orders[$counter]->order_info["ShippingChargesPaid"]=$this->FormatNumber($val2['amount']/100,2);
						$ship_assign=0;
						
					}
				}
			}
				
			
			if($this->GetFieldString($drupal_orders_row,"status")!="pending")
				$this->drupal_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->drupal_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status	
			if($this->GetFieldString($drupal_orders_row,"status")=="completed")
				$this->drupal_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->drupal_orders[$counter]->order_info["IsShipped"]=0;
			
			//Get Customer Comments
		
			$this->drupal_orders[$counter]->order_info["Comments"]="";
			//Get order products
			$items_cost=0;
					
			
			$product_sql = "select * from {commerce_line_item},{field_data_commerce_unit_price},{commerce_product} where {commerce_line_item}.order_id=".$this->drupal_orders[$counter]->order_info["OrderNumber"]." and {commerce_line_item}.line_item_id={field_data_commerce_unit_price}.entity_id and {commerce_line_item}.line_item_label={commerce_product}.sku";
			$product_result = db_query($product_sql);
			$i=0;
			while ($product_row=$product_result->fetchAssoc()) 
			{
				$this->drupal_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_row,"title");
				$unit_price=$this->GetFieldNumber($product_row,"commerce_unit_price_amount")/100;
				$this->drupal_orders[$counter]->order_product[$i]["Price"]=$unit_price;
				$this->drupal_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_row,"line_item_label");
				$this->drupal_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_row,"quantity");
				
				$this->drupal_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($unit_price*$this->GetFieldNumber($product_row,"quantity"));
				
				$items_cost=$items_cost+$this->drupal_orders[$counter]->order_product[$i]["Total"];
						
				
				$this->drupal_orders[$counter]->order_product[$i]["Total_Product_Weight"]="";
				
				$i++;
			}
			
			$this->drupal_orders[$counter]->num_of_products=$i;
			
			$this->drupal_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($items_cost-$this->drupal_orders[$counter]->order_info["ItemsTax"],2);
			
			$this->drupal_orders[$counter]->order_info["Total"]=$this->FormatNumber($this->GetFieldNumber($drupal_orders_row,"commerce_order_total_amount")/100);
			
			
			
			
			$counter++;
		}	
		
		
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			
			if (isset($this->drupal_orders))
				return $this->drupal_orders;
			else
                       		return array();  


			
	}
	
	
	################################################ Function PrepareDrupalOrderStatusFilter #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareDrupalOrderStatusFilter()
	{
			
			$order_status_filter="";
			
			if(DRUPAL_RETRIEVE_ORDER_STATUS_1_PENDING==1)
			{
				$order_status_filter=" status='pending' ";
			
			}
			if(DRUPAL_RETRIEVE_ORDER_STATUS_2_PAYMENT_RECEIVED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" status='payment_received' ";
				}
				else
				{
					$order_status_filter.=" OR status='payment_received' ";
				}
			
			}
			if(DRUPAL_RETRIEVE_ORDER_STATUS_3_PROCESSING==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" status='processing' ";
				}
				else
				{
					$order_status_filter.=" OR status='processing' ";
				}
			
			}
			if(DRUPAL_RETRIEVE_ORDER_STATUS_4_DELIVERED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" status='completed' ";
				}
				else
				{
					$order_status_filter.=" OR status='completed' ";
				}
			
			}
			
			if($order_status_filter!="")
			$order_status_filter="( ".$order_status_filter." ) and";
			return $order_status_filter;
			
	}
	
	
}
######################################### End of class ShippingZDrupal ###################################################

	//create object & perform tasks based on command
	$obj_shipping_drupal=new ShippingZDrupal;
	$obj_shipping_drupal->ExecuteCommand();	

?>