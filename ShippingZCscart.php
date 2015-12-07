<?php

define("SHIPPINGZCSCART_VERSION","3.0.0.55139");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZCSCART_VERSION && SHIPPINGZCSCART_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZCscart.php [".SHIPPINGZCSCART_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


// Check for bootstrap file and bootstrap the DB 
if(Check_Include_File('ShippingZCscartBootstrap.php'))
require('ShippingZCscartBootstrap.php');

// Can now access DB with Cscart functions
 
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZCscart ######################################
class ShippingZCscart extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		
     	global $db_conn;
		
		//check if cscart database can be acessed or not
		$shipping = db_get_array('SHOW COLUMNS FROM ?:orders', 'O');
    	if (db_get_found_rows()>0) 
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
		global $db_conn;
		
		$order_status_filter=$this->PrepareCscartOrderStatusFilter();
		
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$conditions = db_quote(" AND (?:".$order_status_filter.")");
		$conditions.= db_quote(" AND (?:orders.timestamp between ?i AND ?i)",$datefrom_timestamp, $dateto_timestamp);
		
		//Get pending order count based on data range			
		$result = db_get_field(  "SELECT COUNT(*) as total_order FROM ?:orders WHERE  1 ?p", $conditions);
		
		return $result['total_order'];
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		$conditions=db_quote(" AND (?:orders.order_id='$OrderNumber')");
		$sql = "SELECT COUNT(*) as total_order FROM ?:orders WHERE status in('O','P','C') ?p";
		
		$result = db_get_field($sql, $conditions);
		
		$formatted_carrier="";
		//check if order number is valid
		if($result ['total_order']>0)
		{
		
			if($ShipDate!="")
				$shipped_on=$ShipDate;
			else
				$shipped_on=date("m/d/Y");
			
			$carriersql="";
			if($Carrier!="")
			{
				$formatted_carrier=$this->ConverCarrier($Carrier);
				$Carrier=" via ".$Carrier;
				
				$carriersql=",carrier='".$formatted_carrier."'";
			}
			
			if($Service!="")
			$Service=" [".$Service."]";
			
			$TrackingNumberString="";
			$trackingsql="";
			if($TrackingNumber!="")
			{
				$TrackingNumberString=", Tracking number $TrackingNumber";
				$trackingsql=", tracking_number='".$TrackingNumber."'";
			}
			//get existing order status
			$sql = "SELECT * FROM ?:orders WHERE  order_id='".$OrderNumber."'";
			$result = db_get_array($sql);
			foreach ($result as $rows)
			{
				$current_order_status=$rows['status'];
				$existing_comments=$rows['details'];
			}
			
			//prepare $comments 
			$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString."<br>".$existing_comments;
			
			$order_data="";
			
			//Update tracking number & Carrier details
			if($TrackingNumber!="" || $Carrier!="")
			{
				
				$sql_order_data = "SELECT * FROM ?:order_data WHERE  order_id='".$OrderNumber."' and type='L'";
				$result_data= db_get_array($sql_order_data);
				foreach ($result_data as $rows_data)
				{
					$order_data=unserialize($rows_data['data']);
					
					$order_data[1]['tracking_number']=$TrackingNumber;
					$order_data[1]['carrier']=$formatted_carrier;
					
				}
			
				$new_order_data=serialize($order_data); 
				db_query("update ?:order_data set data='".$new_order_data."' where order_id=".$OrderNumber." and type='L'");
				
						
			}
			if(CSCART_PROFESSIONAL)//only for professional version
			{
				//create new shipment
				$shipment_id=db_query("insert into ?:shipments set shipping_id='1'".$trackingsql.$carriersql.",timestamp='".time()."'");
				
				
				//insert shipment items
				$sql_order_items = "SELECT * FROM ?:order_details WHERE  order_id='".$OrderNumber."'";
				$result_items= db_get_array($sql_order_items);
				foreach ($result_items as $rows_item_data)
				{
					db_query("insert into ?:shipment_items set 	item_id='".$rows_item_data['item_id']."', shipment_id ='$shipment_id', order_id='".$OrderNumber."',product_id='".$rows_item_data['product_id']."',amount='".$rows_item_data['amount']."'");
					
				
				}
				//Enable RMA
				db_query("Insert into ?:order_data set  order_id=".$OrderNumber." , type='V', data='".time()."'");
			
			}
				
			//update order table
			if(CSCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE==1)
			{
			 	db_query("update ?:orders set status='C',details='".$comments."'  where order_id=".$OrderNumber);
				
							
				
			}
			else
			{
				if($current_order_status=='O'  )
					$change_order_status='P';
				else if($current_order_status=='P')
					$change_order_status='C';
				else
					$change_order_status=$current_order_status;
					
				db_query("update ?:orders set status='".$change_order_status."', details='".$comments."' where order_id=".$OrderNumber);
				
				
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
		
		$order_status_filter=$this->PrepareCscartOrderStatusFilter();
		
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$conditions = db_quote(" AND (?:".$order_status_filter.")");
		$conditions.= db_quote(" AND (?:orders.timestamp between ?i AND ?i)",$datefrom_timestamp, $dateto_timestamp);
		
		$result = db_get_array("SELECT * FROM ?:orders WHERE  1 ?p", $conditions);
		
		
		$counter=0;
		
		foreach ($result as $cscart_orders)
		{
			
			//prepare order array
			$cscart_orders_row=fn_get_order_info($this->GetFieldNumber($cscart_orders,"order_id"));
			$this->cscart_orders[$counter]->orderid=$this->GetFieldNumber($cscart_orders,"order_id");
					
			//shipping details
			$this->cscart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($cscart_orders_row,"s_firstname");
			$this->cscart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($cscart_orders_row,"s_lastname");
			$this->cscart_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($cscart_orders_row,"company");
			$this->cscart_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($cscart_orders_row,"s_address");			
			$this->cscart_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($cscart_orders_row,"s_address_2");
			$this->cscart_orders[$counter]->order_shipping["City"]=$this->GetFieldString($cscart_orders_row,"s_city");		
			$this->cscart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($cscart_orders_row,"s_state");
			$this->cscart_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($cscart_orders_row,"s_zipcode");			
			$this->cscart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($cscart_orders_row,"s_country");
			$this->cscart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($cscart_orders_row,"s_phone");
			$this->cscart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($cscart_orders_row,"email");
			
			//billing details
			$this->cscart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($cscart_orders_row,"b_firstname");
			$this->cscart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($cscart_orders_row,"b_lastname");
			$this->cscart_orders[$counter]->order_billing["Company"]=$this->GetFieldString($cscart_orders_row,"company");
			$this->cscart_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($cscart_orders_row,"b_address");			
			$this->cscart_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($cscart_orders_row,"b_address_2");
			$this->cscart_orders[$counter]->order_billing["City"]=$this->GetFieldString($cscart_orders_row,"b_city");
			$this->cscart_orders[$counter]->order_billing["State"]=$this->GetFieldString($cscart_orders_row,"b_state");
			$this->cscart_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($cscart_orders_row,"b_zipcode");
			$this->cscart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($cscart_orders_row,"b_country");
			$this->cscart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($cscart_orders_row,"b_phone");
			$this->cscart_orders[$counter]->order_billing["EMail"]=$this->GetFieldString($cscart_orders_row,"email");
			
			//order info
			$this->cscart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,$this->GetFieldString($cscart_orders_row,"timestamp"));
			$this->cscart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($cscart_orders_row,"order_id");
			
			//get payment method
			$pay_result_arr = db_get_array('SELECT * FROM ?:payment_descriptions WHERE  payment_id=?i', $this->GetFieldNumber($cscart_orders_row,"payment_id"));
			$payment_method="";
			foreach ($pay_result_arr as $pay_result)
			{
				$payment_method=$this->GetFieldString($pay_result,'payment');
			}
				
			$this->cscart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($payment_method);
			
			//get shipping charges
			$ship_method="";
			$ship_result_arr=$this->GetFieldNumber($cscart_orders_row,"shipping");
			if(is_array($ship_result_arr))
			{
				foreach ($ship_result_arr as $ship_result)
				{
					$ship_method=$this->GetFieldString($ship_result,'shipping');
				}
			}
			$this->cscart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->GetFieldNumber($cscart_orders_row,"display_shipping_cost");
			$this->cscart_orders[$counter]->order_info["ShipMethod"]=$ship_method;
			
			
			//Extract tax amount
			$tax="";
			$tax_arr=$this->GetFieldNumber($cscart_orders_row,"taxes");
			foreach ($tax_arr as $taxes)
			{
				$tax=$taxes['tax_subtotal'];
			}
			
			$this->cscart_orders[$counter]->order_info["ItemsTax"]=$tax;
			
			if($this->GetFieldString($cscart_orders_row,"status")!="O")
				$this->cscart_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->cscart_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status	
			if($this->GetFieldString($cscart_orders_row,"status")=="C")
				$this->cscart_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->cscart_orders[$counter]->order_info["IsShipped"]=0;
			
			//Get Customer Comments
		
			$this->cscart_orders[$counter]->order_info["Comments"]=$this->GetFieldString($cscart_orders_row,"notes");
			//Get order products
			$items_cost=0;
				
			
			$product_result_arr =db_get_array('SELECT ?:order_details.*,?:product_descriptions.product,?:products.weight FROM ?:order_details INNER JOIN ?:product_descriptions ON ?:order_details.product_id = ?:product_descriptions.product_id INNER JOIN ?:products ON ?:order_details.product_id = ?:products.product_id WHERE ?:order_details.order_id =?i', $this->GetFieldNumber($cscart_orders_row,"order_id"));
			
			$i=0;
			
			foreach ($product_result_arr as $product_row) 
			{
				
				$this->cscart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_row,"product");
				$unit_price=$this->GetFieldNumber($product_row,"price");
				$this->cscart_orders[$counter]->order_product[$i]["Price"]=$unit_price;
				$this->cscart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_row,"product_code");
				$this->cscart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_row,"amount");
				
				$this->cscart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($unit_price*$this->GetFieldNumber($product_row,"amount"));
				
				$items_cost=$items_cost+$this->cscart_orders[$counter]->order_product[$i]["Total"];
						
				$this->cscart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($product_row,"weight");
				
				$option_arr_temp=unserialize($this->GetFieldString($product_row,"extra"));
				$attributes="";

				$option_arr=$this->GetFieldString($option_arr_temp,"product_options_value");
				if(count($option_arr)>0 && is_array($option_arr))
				{
					foreach ($option_arr as $option_arr_row) 
					{
						if($attributes!="")
						$attributes.=",";
						
						$attributes.=$this->GetFieldString($option_arr_row,"option_name").":".$this->GetFieldString($option_arr_row,"variant_name");
					}
				}
				
				$this->cscart_orders[$counter]->order_product[$i]["Notes"]=$attributes;
				
				if($attributes!="")
				$this->cscart_orders[$counter]->order_product[$i]["Name"]=$this->cscart_orders[$counter]->order_product[$i]["Name"]." (".$attributes.")";
				
				$i++;
			}
			
			$this->cscart_orders[$counter]->num_of_products=$i;
			
			$this->cscart_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($items_cost-$this->cscart_orders[$counter]->order_info["ItemsTax"]-$this->cscart_orders[$counter]->order_info["ShippingChargesPaid"],2);
			
			$this->cscart_orders[$counter]->order_info["Total"]=$this->FormatNumber($this->GetFieldNumber($cscart_orders_row,"total"));
			
			
			
			
			$counter++;
		}	
		
		
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			
			if (isset($this->cscart_orders))
				return $this->cscart_orders;
			else
                       		return array();  


			
	}
	
	
	################################################ Function PrepareCscartOrderStatusFilter #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareCscartOrderStatusFilter()
	{
			
			$order_status_filter="";
			
			//Order statuses in this case:Complete ,Processed ,Open
			if(CSCART_RETRIEVE_ORDER_STATUS_1_OPEN==1)
			{
				$order_status_filter="orders.status= 'O' ";
				
			
			}
			if(CSCART_RETRIEVE_ORDER_STATUS_2_PROCESSED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" ?:orders.status= 'P' ";
				}
				else
				{
					$order_status_filter.=" OR  ?:orders.status='P' ";
				}
				
			
			}
			if(CSCART_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" ?:orders.status='C' ";
				}
				else
				{
					$order_status_filter.=" OR  ?:orders.status='C' ";
				}
							
			}
			
			
			
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
	
	
	
}
######################################### End of class ShippingZCscart ###################################################

	//create object & perform tasks based on command
	$obj_shipping_cscart=new ShippingZCscart;
	$obj_shipping_cscart->ExecuteCommand();	

?>