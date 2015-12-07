<?php

define("SHIPPINGZXCART_VERSION","3.0.0.55139");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZXCART_VERSION && SHIPPINGZXCART_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZXcart.php [".SHIPPINGZXCART_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


#########################################################################################################################
  
//Check for xcart include files
if(Check_Include_File("./top.inc.php"))
require "./top.inc.php";

if(Check_Include_File("./init.php"))
require "./init.php";
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZXcart ######################################
class ShippingZXcart extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		global $sql_tbl;
		//check if xcart database can be acessed or not
		$sql = "SHOW COLUMNS FROM $sql_tbl[orders]";
		
		$result = db_query($sql);
		
        if ($result) 
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
		
		global $sql_tbl;
		
		//Get order count based on data range
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$order_status_filter=$this->PrepareXcartOrderStatusFilter();
		
		$sql = "SELECT COUNT(*) as total_pending_orders FROM $sql_tbl[orders] WHERE ".$order_status_filter." date between '".$this->MakeSqlSafe($datefrom_timestamp)."' and '".$this->MakeSqlSafe($dateto_timestamp)."'";
		
		$result = db_query($sql);
		$row = db_fetch_array($result);
		
		return $row['total_pending_orders'];
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		global $sql_tbl;
		
		$sql = "SELECT COUNT(*) as total_order FROM $sql_tbl[orders] WHERE orderid='".$this->MakeSqlSafe($OrderNumber,1)."'";
		
		$result = db_query($sql);
		$row = db_fetch_array($result);
		//check if order number is valid
		if($row ['total_order']>0)
		{
		
			if($ShipDate!="")
				$shipped_on=$ShipDate;
			else
				$shipped_on=date("m/d/Y");
				
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
			$tracking_sql="";
			if($TrackingNumber!="")
			{
				$TrackingNumberString=", Tracking number $TrackingNumber";
				$tracking_sql=" ,tracking='". $this->MakeSqlSafe($TrackingNumber). "'";
			}
			
			if($shipping_str!="")
			$shipping_sql=" ,shipping='".$shipping_str."'";
			
			//get shipments
			$sql = "SELECT * FROM $sql_tbl[orders] WHERE  orderid='".$this->MakeSqlSafe($OrderNumber,1)."'";
			$result = db_query($sql);
			$row = db_fetch_array($result);
			$current_order_status=$row['status'];
			
						
			//prepare $comments (appending existing notes)
			$comments=$row['notes']."---Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
			
			//update order table
			if(XCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE==1)
			{
			 	db_query("update $sql_tbl[orders] set status='C', notes='". $this->MakeSqlSafe($comments). "'".$tracking_sql.$shipping_sql."  where orderid=".$this->MakeSqlSafe($OrderNumber,1));
			}
			else
			{
				if($current_order_status=='Q' || $current_order_status=='A')
					$change_order_status='P';
				else if($current_order_status=='P')
					$change_order_status='C';
				else
					$change_order_status=$current_order_status;
					
				db_query("update $sql_tbl[orders] set status='$change_order_status', notes='". $this->MakeSqlSafe($comments). "'".$tracking_sql.$shipping_sql." where orderid=".$this->MakeSqlSafe($OrderNumber,1));
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
		global $sql_tbl;
		require_once('./include/func/func.order.php');
		
		$order_status_filter=$this->PrepareXcartOrderStatusFilter();
		
		$search=$order_status_filter." date between '".$this->MakeSqlSafe($this->GetServerTimeLocal(false,$datefrom))."' and '".$this->MakeSqlSafe($this->GetServerTimeLocal(false,$dateto))."'";
		
		$orders_query_raw = "select orderid from $sql_tbl[orders] where ".$search ." order by orderid DESC";
				  
		$xcart_orders_res = db_query($orders_query_raw);
		
		$counter=0;
		while ($row = db_fetch_array($xcart_orders_res)) 
		{
			
			//Get order details & customer details
			$xcart_orders_temp=func_select_order($this->GetFieldNumber($row,"orderid"));
			
			//prepare order array
			$this->xcart_orders[$counter]->orderid=$this->GetFieldNumber($row,"orderid");
			
			//Get order products
			$order_data_arr=func_order_data($this->GetFieldNumber($row,"orderid"));
			$product_data_arr=$this->GetFieldString($order_data_arr,"products");
			
			$this->xcart_orders[$counter]->num_of_products=count($product_data_arr);
			
			//shipping details
			if($this->GetFieldString($xcart_orders_temp,"s_firstname")!="" && $this->GetFieldString($xcart_orders_temp,"s_lastname")!="")
			{
				$this->xcart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($xcart_orders_temp,"s_firstname");
				$this->xcart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($xcart_orders_temp,"s_lastname");
			}
			else
			{
				$this->xcart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($xcart_orders_temp,"firstname");
				$this->xcart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($xcart_orders_temp,"lastname");
			
			}
			
			$this->xcart_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($xcart_orders_temp,"company");
			$this->xcart_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($xcart_orders_temp,"s_address");
			$this->xcart_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($xcart_orders_temp,"s_address_2");
			$this->xcart_orders[$counter]->order_shipping["City"]=$this->GetFieldString($xcart_orders_temp,"s_city");
			$this->xcart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($xcart_orders_temp,"s_state");
			$this->xcart_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($xcart_orders_temp,"s_zipcode");
			$this->xcart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($xcart_orders_temp,"s_country");
			
			$this->xcart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($xcart_orders_temp,"phone");
			if($this->xcart_orders[$counter]->order_shipping["Phone"]=="")
			$this->xcart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($xcart_orders_temp,"s_phone");
			
			$this->xcart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($xcart_orders_temp,"email");
			
			//billing details
			if($this->GetFieldString($xcart_orders_temp,"b_firstname")!="" && $this->GetFieldString($xcart_orders_temp,"b_lastname")!="")
			{
			
				$this->xcart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($xcart_orders_temp,"b_firstname");
				$this->xcart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($xcart_orders_temp,"b_lastname");
			}
			else
			{
				$this->xcart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($xcart_orders_temp,"firstname");
				$this->xcart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($xcart_orders_temp,"lastname");
			
			}
			
			$this->xcart_orders[$counter]->order_billing["Company"]=$this->GetFieldString($xcart_orders_temp,"company");
			$this->xcart_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($xcart_orders_temp,"b_address");
			$this->xcart_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($xcart_orders_temp,"b_address_2");
			$this->xcart_orders[$counter]->order_billing["City"]=$this->GetFieldString($xcart_orders_temp,"b_city");
			$this->xcart_orders[$counter]->order_billing["State"]=$this->GetFieldString($xcart_orders_temp,"b_state");
			$this->xcart_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($xcart_orders_temp,"b_zipcode");
			$this->xcart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($xcart_orders_temp,"b_country");
			
			$this->xcart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($xcart_orders_temp,"phone");
			if($this->xcart_orders[$counter]->order_billing["Phone"]=="")
			$this->xcart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($xcart_orders_temp,"b_phone");
			
			//order info
			//$this->xcart_orders[$counter]->order_info["OrderDate"]=date("Y-m-d",$xcart_orders_temp['date']);
			$this->xcart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,$this->GetFieldString($xcart_orders_temp,"date"));
			
			$this->xcart_orders[$counter]->order_info["ItemsTotal"]=$this->GetFieldMoney($xcart_orders_temp,"display_subtotal");
			$this->xcart_orders[$counter]->order_info["Total"]=$this->GetFieldMoney($xcart_orders_temp,"total");
			$this->xcart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->GetFieldMoney($xcart_orders_temp,"display_shipping_cost");
			$this->xcart_orders[$counter]->order_info["ItemsTax"]=$this->GetFieldMoney($xcart_orders_temp,"tax");
			$this->xcart_orders[$counter]->order_info["Comments"]=$this->MakeXMLSafe($this->GetFieldString($xcart_orders_temp,"customer_notes")); 
			
			//Get shipping method
			$shippingid=$xcart_orders_temp['shippingid'];
			$shipping_query_raw = "select shipping from $sql_tbl[shipping] where shippingid=$shippingid";
			$xcart_shipping_res = db_query($shipping_query_raw);
			$shipping_row = db_fetch_array($xcart_shipping_res);
			
			if($shipping_row['shipping']=="")
			{
				$this->xcart_orders[$counter]->order_info["ShipMethod"]="Not Available";
			}
			else
			{
				$this->xcart_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($shipping_row,"shipping");
			}
			
			$this->xcart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($xcart_orders_temp,"orderid");
			
			//get payment type
			$sql_get_payment_method= "select * from $sql_tbl[payment_methods] where paymentid=".$this->GetFieldNumber($xcart_orders_temp,"paymentid");
			$res_payment_method = db_query($sql_get_payment_method);
			$row_payment_method = db_fetch_array($res_payment_method); 
			
			$this->xcart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($row_payment_method,"payment_method"));
			
			if($this->GetFieldString($xcart_orders_temp,"status")!="Q" && $this->GetFieldString($xcart_orders_temp,"status")!="A")
				$this->xcart_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->xcart_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status	
			if($this->GetFieldString($xcart_orders_temp,"status")=="C")
				$this->xcart_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->xcart_orders[$counter]->order_info["IsShipped"]=0;
			
			for($i=0;$i<$this->xcart_orders[$counter]->num_of_products;$i++)
			{
				
				$this->xcart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_data_arr,"product",$i);
				$this->xcart_orders[$counter]->order_product[$i]["Price"]=$this->GetFieldMoney($product_data_arr,"price");
				$this->xcart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_data_arr,"amount",$i);
				$this->xcart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->GetFieldNumber($product_data_arr,"price",$i)*$this->GetFieldNumber($product_data_arr,"amount",$i));	
				 $this->xcart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($product_data_arr,"weight",$i)*$this->GetFieldNumber($product_data_arr,"amount",$i);
				 
				 $this->xcart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_data_arr,"productcode",$i);
				 
				
				 
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
			

			if (isset($this->xcart_orders))
				return $this->xcart_orders;
			else
                       		return array();  
			
	}
	################################################ Function PrepareOrderStatusString #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareXcartOrderStatusFilter()
	{
			
			$order_status_filter="";
			
			if(XCART_RETRIEVE_ORDER_STATUS_1_QUEUED==1)//considers queued/pre-authorized orders
			{
				$order_status_filter="  status='Q' OR  status='A' ";
			
			}
			if(XCART_RETRIEVE_ORDER_STATUS_2_PROCESSED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" status='P' ";
				}
				else
				{
					$order_status_filter.=" OR status='P' ";
				}
			
			}
			if(XCART_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" status='C' ";
				}
				else
				{
					$order_status_filter.=" OR status='C'";
				}
			
			}
			
			if($order_status_filter!="")
			$order_status_filter="( ".$order_status_filter." ) and";
			return $order_status_filter;
			
	}
		
	
}
######################################### End of class ShippingZXcart ###################################################

	// create object & perform tasks based on command

	$obj_shipping_xcart=new ShippingZXcart;
	$obj_shipping_xcart->ExecuteCommand();

?>