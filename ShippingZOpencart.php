<?php

define("SHIPPINGZOPENCART_VERSION","3.0.0.55139");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZOPENCART_VERSION && SHIPPINGZOPENCART_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZOpencart.php [".SHIPPINGZOPENCART_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


// Check for bootstrap file and bootstrap the DB 
if(Check_Include_File('ShippingZOpencartBootstrap.php'))
require('ShippingZOpencartBootstrap.php');

// Can now access DB with Opencart functions
 
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZOpencart ######################################
class ShippingZOpencart extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		
     	global $db_pdo;
		
		//check if opencart database can be acessed or not
		$shipping = $db_pdo->prepare('SHOW COLUMNS FROM `'.DB_PREFIX.'order`');
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
		
		$order_status_filter=$this->PrepareOpencartOrderStatusFilter();
		
		
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$sql = "SELECT o.order_id FROM `" . DB_PREFIX . "order` o";

		$sql .= " WHERE " . $order_status_filter . "";
			
		$sql .= " ( ( DATE_FORMAT(date_modified,\"%Y-%m-%d %T\") between :dateform and :dateto) OR  ( DATE_FORMAT(date_added,\"%Y-%m-%d %T\") between :dateform and :dateto) )";
		
		$orders = $db_pdo->prepare($sql);
		
		$data=array(':dateform' => $this->GetServerTimeLocal(true,$datefrom), ':dateto' => $this->GetServerTimeLocal(true,$dateto));
		
		$orders->execute($data);
		
		return $orders->rowCount();
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		global $db_pdo;
		
		
		$result = $db_pdo->prepare("SELECT order_status_id FROM `".DB_PREFIX."order` WHERE order_id=:order_id");
		$result->execute(array(':order_id' => $OrderNumber));
		
		//check if order number is valid
		if($result->rowCount()>0)
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
			
			foreach ($result as $order_row)
			{
				$current_order_status=$order_row['order_status_id'];
			}
			
			
								
			//prepare $comments & save it
			$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
			
			if(OPENCART_SHIPPED_STATUS_SET_TO_STATUS_3_SHIPPED==1)
			{
				
				 $upate_order_history=$db_pdo->prepare("insert into " .DB_PREFIX."order_history
						  (order_id, order_status_id,date_added, notify, comment)
						  values (:order_id, :order_status_id,:date_added,:notify, :comments)");
				 $upate_order_history->execute(array(':order_id' => $OrderNumber,':order_status_id'=>3,':date_added' => date('Y-m-d H:i:s'),':notify' =>0,':comments' => $comments));	
				 
				
						  
				//update order status
				$upate_order=$db_pdo->prepare(" update `".DB_PREFIX."order` set order_status_id=:order_status_id where order_id=:order_id");
				$upate_order->execute(array(':order_id' => $OrderNumber,':order_status_id'=>3));	  
			 } 
			 else
			 {
			 	
				if($current_order_status==1)
					$change_order_status=2;
				else if($current_order_status==2)
					$change_order_status=3;
				else
					$change_order_status=$current_order_status;
				
				 $upate_order_history=$db_pdo->prepare("insert into ".DB_PREFIX."order_history
						  (order_id, order_status_id, date_added, notify, comment)
						  values (:order_id, :change_order_status, :date_added, :notify, :comments)");
				 $upate_order_history->execute(array(':order_id' => $OrderNumber,':change_order_status' => $change_order_status,':date_added' => date('Y-m-d H:i:s'),':notify' => 0,':comments' => $comments));	 
						  
				if($change_order_status!=$current_order_status)
				{
					$upate_order=$db_pdo->prepare(" update `".DB_PREFIX."order` set order_status_id=:change_order_status where order_id=:order_id");
				 	$upate_order->execute(array(':order_id' => $OrderNumber,':change_order_status' => $change_order_status));	
				 }
			 
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
		global $db_pdo;
		
		$order_status_filter=$this->PrepareOpencartOrderStatusFilter();
		
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		
		$sql = "SELECT o.order_id,o.shipping_firstname,o.shipping_lastname,o.shipping_company,o.shipping_address_1,o.shipping_address_2,
		o.shipping_city,o.shipping_zone,o.shipping_postcode,o.shipping_country,o.telephone,o.email,o.payment_firstname,o.payment_lastname,o.payment_company,
		o.payment_address_1,o.payment_address_2,o.payment_city,o.payment_zone,o.payment_postcode,o.payment_country,o.telephone,o.email,
		o.date_added,o.payment_method,o.shipping_method,o.comment,o.total FROM `" . DB_PREFIX . "order` o";

		$sql .= " WHERE " . $order_status_filter . "";
			
		$sql .= " ( ( DATE_FORMAT(date_modified,\"%Y-%m-%d %T\") between :dateform and :dateto) OR  ( DATE_FORMAT(date_added,\"%Y-%m-%d %T\") between :dateform and :dateto) )";
		
		$orders = $db_pdo->prepare($sql);
		
		$data=array(':dateform' => $this->GetServerTimeLocal(true,$datefrom), ':dateto' => $this->GetServerTimeLocal(true,$dateto));
		
		$orders->execute($data);
			
		$counter=0;
		
		foreach ($orders as $opencart_orders_row)
		{
		
				
			//prepare order array
			
			$this->opencart_orders[$counter]->orderid=$this->GetFieldNumber($opencart_orders_row,"order_id");
					
			//shipping details
			$this->opencart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($opencart_orders_row,"shipping_firstname");
			$this->opencart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($opencart_orders_row,"shipping_lastname");
			$this->opencart_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($opencart_orders_row,"shipping_company");
			$this->opencart_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($opencart_orders_row,"shipping_address_1");			
			$this->opencart_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($opencart_orders_row,"shipping_address_2");
			$this->opencart_orders[$counter]->order_shipping["City"]=$this->GetFieldString($opencart_orders_row,"shipping_city");		
			$this->opencart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($opencart_orders_row,"shipping_zone");
			$this->opencart_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($opencart_orders_row,"shipping_postcode");			
			$this->opencart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($opencart_orders_row,"shipping_country");
			$this->opencart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($opencart_orders_row,"telephone");
			$this->opencart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($opencart_orders_row,"email");
			
			//billing details
			$this->opencart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($opencart_orders_row,"payment_firstname");
			$this->opencart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($opencart_orders_row,"payment_lastname");
			$this->opencart_orders[$counter]->order_billing["Company"]=$this->GetFieldString($opencart_orders_row,"payment_company");
			$this->opencart_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($opencart_orders_row,"payment_address_1");			
			$this->opencart_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($opencart_orders_row,"payment_address_2");
			$this->opencart_orders[$counter]->order_billing["City"]=$this->GetFieldString($opencart_orders_row,"payment_city");
			$this->opencart_orders[$counter]->order_billing["State"]=$this->GetFieldString($opencart_orders_row,"payment_zone");
			$this->opencart_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($opencart_orders_row,"payment_postcode");
			$this->opencart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($opencart_orders_row,"payment_country");
			$this->opencart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($opencart_orders_row,"telephone");
			$this->opencart_orders[$counter]->order_billing["EMail"]=$this->GetFieldString($opencart_orders_row,"email");
			
			//order info
			$this->opencart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,strtotime($this->GetFieldString($opencart_orders_row,"date_added")));
			$this->opencart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($opencart_orders_row,"order_id");
			$this->opencart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($opencart_orders_row,"payment_method"));
			
			
			//for older version of opencart, 'weight' tablle does not have 'unit' column, hence check for "unit" column	to identify the version
			$my_unit_column = $db_pdo->prepare("SHOW columns from ".DB_PREFIX."weight_class where field=:field");
			$my_unit_column->execute(array(':field' => "unit"));
			
			$check_col_flag=0;
			if($my_unit_column->rowCount()>0)
			$check_col_flag=1;
			
			$shipping_charge="";
			$tax="";
			
			if(!$check_col_flag)
			{
				$order_total_result =$db_pdo->prepare('SELECT code,value from '.DB_PREFIX.'order_total where order_id=:order_id');
				$order_total_result->execute(array(':order_id' => $this->GetFieldNumber($opencart_orders_row,"order_id")));
				
				foreach ($order_total_result as $opencart_order_total)
				{
				
						if($this->GetFieldString($opencart_order_total,"code")=="shipping")
						$shipping_charge=$this->GetFieldNumber($opencart_order_total,"value");
						
						if($this->GetFieldString($opencart_order_total,"code")=="tax")
						$tax=$this->GetFieldNumber($opencart_order_total,"value");
				}
			}
			else
			{
				
				$order_total_result =$db_pdo->prepare('SELECT title,value from '.DB_PREFIX.'order_total where order_id=:order_id');
				$order_total_result->execute(array(':order_id' => $this->GetFieldNumber($opencart_orders_row,"order_id")));
				
				foreach ($order_total_result as $opencart_order_total)
				{
				
						if(strstr(strtolower($this->GetFieldString($opencart_order_total,"title")),"shipping"))
						$shipping_charge=$this->GetFieldNumber($opencart_order_total,"value");
						
						if(strstr(strtolower($this->GetFieldString($opencart_order_total,"title")),"tax"))
						$tax=$this->GetFieldNumber($opencart_order_total,"value");
				}
			}
			
			//get shipping charges
			$this->opencart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->FormatNumber($shipping_charge);
			$this->opencart_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($opencart_orders_row,"shipping_method");
			
			
			//Extract tax amount
			$this->opencart_orders[$counter]->order_info["ItemsTax"]=$this->FormatNumber($tax);
			
			if($this->GetFieldNumber($opencart_orders_row,"order_status_id")!="1")
				$this->opencart_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->opencart_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Order status	
			if($this->GetFieldNumber($opencart_orders_row,"order_status_id")=="3")
				$this->opencart_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->opencart_orders[$counter]->order_info["IsShipped"]=0;
			
			//Get Customer Comments
		
			$this->opencart_orders[$counter]->order_info["Comments"]=$this->GetFieldString($opencart_orders_row,"comment");
			//Get order products
			$items_cost=0;
			
			
			
			if(!$check_col_flag)
			{
				//for older versions, unit values are stored in 'weight_class_description' table
				$product_result_arr =$db_pdo->prepare('SELECT op.order_product_id,op.name,op.model,op.price,op.quantity,p.weight,p.weight_class_id,w.unit from '.DB_PREFIX.'order_product op, '.DB_PREFIX.'product p,'.DB_PREFIX.'weight_class_description w where op.order_id=:order_id and op.product_id=p.product_id and p.weight_class_id=w.weight_class_id   and w.language_id=1');
			}
			else
			{
			
			$product_result_arr =$db_pdo->prepare('SELECT op.order_product_id,op.name,op.model,op.price,op.quantity,p.weight,p.weight_class_id,w.unit from '.DB_PREFIX.'order_product op, '.DB_PREFIX.'product p,'.DB_PREFIX.'weight_class w where op.order_id=:order_id and op.product_id=p.product_id and p.weight_class_id=w.weight_class_id and w.language_id=1');
			}
			
			$i=0;
			
			$product_result_arr->execute(array(':order_id' => $this->GetFieldNumber($opencart_orders_row,"order_id")));
			
			foreach ($product_result_arr as $product_row) 
			{
				
				
				$unit_price=$this->GetFieldNumber($product_row,"price");
				$this->opencart_orders[$counter]->order_product[$i]["Price"]=$this->FormatNumber($unit_price);
				$this->opencart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_row,"model");
				$this->opencart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_row,"quantity");
				
				$this->opencart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($unit_price*$this->GetFieldNumber($product_row,"quantity"));
				
				$items_cost=$items_cost+$this->opencart_orders[$counter]->order_product[$i]["Total"];
				
				$product_weight=$this->ConvertToPound($this->GetFieldNumber($product_row,"weight"),$this->GetFieldNumber($product_row,"unit"));
				
				$uom_weight=$this->GetFieldNumber($product_row,"unit");
				
				$this->opencart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$product_weight*$this->GetFieldNumber($product_row,"quantity");
				
				//product options
				
				$product_option_arr =$db_pdo->prepare('SELECT name,value from '.DB_PREFIX.'order_option  where order_product_id=:order_product_id and order_id=:order_id');
				$product_option_arr->execute(array(':order_product_id' => $this->GetFieldNumber($product_row,"order_product_id"),':order_id' => $this->opencart_orders[$counter]->orderid));
				
				
				$attributes="";
				if($product_option_arr->rowCount())
				{
					foreach ($product_option_arr as $option_arr_row) 
					{
						if($attributes!="")
						$attributes.=",";
						
						$attributes.=$this->GetFieldString($option_arr_row,"name").":".$this->GetFieldString($option_arr_row,"value");
					}
					
					$this->opencart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_row,"name")." (".$attributes.")";
					
				}
				else
				{
					$this->opencart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_row,"name");
				}
				$i++;
			}
			
			$this->opencart_orders[$counter]->num_of_products=$i;
			
			$this->opencart_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($items_cost,2);
			
			$this->opencart_orders[$counter]->order_info["Total"]=$this->FormatNumber($this->GetFieldNumber($opencart_orders_row,"total"));
			
			$this->opencart_orders[$counter]->order_info["UOMWeight"]=strtoupper($uom_weight);
						
			$counter++;
			
			
		}//end foreach
		
		
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			
			if (isset($this->opencart_orders))
				return $this->opencart_orders;
			else
                       		return array();  


			
	}
	
	
		################################################ Function ExtractOrderStatusIdsByType #######################
		//Extract order status ids based on settings
		#######################################################################################################
		function ExtractOrderStatusIdsByType($order_status_filter,$StatusType)
		{
				$order_id_temps=explode(",",$StatusType);
				
				foreach($order_id_temps as $val)
				{
					if($val!="")
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" o.order_status_id=".$val;
						}
						else
						{
							$order_status_filter.=" OR o.order_status_id=".$val;
						}
					}
				}
				return $order_status_filter;
		}
		
		################################################ Function PrepareOpencartOrderStatusFilter #######################
		//Prepare order status string based on settings
		#######################################################################################################
		function PrepareOpencartOrderStatusFilter()
		{
				
				$order_status_filter="";
				
				//Use order statuses based on settings
				if(OPENCART_RETRIEVE_ORDER_STATUS_1_UNPAID==1)
				{
					$order_status_filter=$this->ExtractOrderStatusIdsByType($order_status_filter,OPENCART_UNPAID_ORDER_STATUSES);
				
				}
				if(OPENCART_RETRIEVE_ORDER_STATUS_2_PAID==1)
				{
					$order_status_filter=$this->ExtractOrderStatusIdsByType($order_status_filter, OPENCART_PAID_ORDER_STATUSES);
				}
				if(OPENCART_SHIPPED_STATUS_SET_TO_STATUS_3_SHIPPED==1)
				{
					$order_status_filter=$this->ExtractOrderStatusIdsByType($order_status_filter,OPENCART_SHIPPED_ORDER_STATUSES);
				
				}
				
				if($order_status_filter!="")
				$order_status_filter="( ".$order_status_filter." ) and";
				
				return $order_status_filter;
				
		}
		function ConverCarrier($carrier)
		{
					$formatted_carrier="";
					$carrier=strtolower($carrier);
					
					switch($carrier)
					{
						case 'usps': 
						$formatted_carrier="USP";
						break;
						
						case 'ups':
						$formatted_carrier="UPS";
						break;
						
						case 'fedex':
						$formatted_carrier="FDX";
						break;
						
						case 'dhl':
						$formatted_carrier="DHL";
						break;
						
						case 'aup':
						$formatted_carrier="AUP";
						break;
						
						case 'chp':
						$formatted_carrier="CHP";
						break;
					
					}	
					return $formatted_carrier;
							
		
		}
	
		################################################ Function ConvertToPound() #######################
		//Converts weight values to pound
		#######################################################################################################
		function ConvertToPound($weight,$from_unit)
		{
			
			$from_unit=trim($from_unit);
			
			if($from_unit=='oz' || $from_unit=='ozs')
			{
				
				$converted_weight=$weight*0.0625;
			}
			else if($from_unit=='kg' || $from_unit=='kgs')
			{
			
				$converted_weight=$weight*2.20462;
			}
			else if($from_unit=='g' || $from_unit=='gm' || $from_unit=='gms')
			{
			
				$converted_weight=$weight*0.0022;
			}
			else 
			{
			
				$converted_weight=$weight;
			}
			return $converted_weight;
		}
	
}
######################################### End of class ShippingZOpencart ###################################################

	//create object & perform tasks based on command
	$obj_shipping_opencart=new ShippingZOpencart;
	$obj_shipping_opencart->ExecuteCommand();	

?>