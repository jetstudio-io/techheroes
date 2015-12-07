<?php

define("SHIPPINGZCLASSES_VERSION","3.0.0.55139");

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


	####################################################### Begin shipping main #################################
	class ShippingZGenericShoppingCart {
			
			var $display_msg="";
			var $complete_shipment_order_xml="";
			
			#####################################Treat this as an abstract class #################
			//This is done in this way to make the code compatible to both PHP4 & PHP5
			######################################################################################
			function ShippingZGenericShoppingCart()
			{
				if(get_class($this)=='ShippingZGenericShoppingCart'||!is_subclass_of ($this,'ShippingZGenericShoppingCart'))
				{
					  trigger_error('This class is abstract. It cannot be instantiated!',E_USER_ERROR);
				}
			}
			#####################################Get command & perform required actions #################
			/* This will handle all URL parameters & validates them.Then invoke required methods*/
			##################################################################################################
			function ExecuteCommand()
			{
			
				$cmd=strtolower($this->GetValues('cmd'));
				####################### Act according to selected command ########################################
				//getordersbydate - returns list of orders by date range, no paging
				//getordercountbydate - returns count of orders by date range (in XML format, of course)
				//updateshippinginfo - updates orders with tracking number and shipping details. 
				//ping - checks that API configured properly (has DB access, valid token, etc.)
				//Display error message for invalid commands
				##########################################################################################
				switch($cmd)
				{
						case 'ping':
						//Invokes Ping() function checks for valid token
						$this->Ping();
						if($this->display_msg=="")
						{	
							$this->Check_DB_Access();//checks for DB access
							if($this->display_msg==DB_SUCCESS_MSG)
							{
								$this->SetXmlMessageResponse($this->wrap_to_xml('Message',$this->display_msg) . $this->wrap_to_xml('Version',SHIPPINGZCLASSES_VERSION));
							}
							else
							{
								$this->SetXmlError(1,$this->display_msg);
							}
						}
						else
						{
							 $this->SetXmlError(1,$this->display_msg);
						}
						break;
						
						//checks server info
						case 'getserverinfo':
						$this->Ping();
						if($this->display_msg=="")
						{	
							//display php version & other server details
							echo 'PHP version: ' . phpversion()."<br>";
							echo "Other Debugging Information:<br>";
							echo "DOCUMENT ROOT: ".$_SERVER['DOCUMENT_ROOT']."<br>";
							echo "SERVER SOFTWARE: ".$_SERVER['SERVER_SOFTWARE']."<br>";
							echo "SCRIPT FILENAME: ".$_SERVER['SCRIPT_FILENAME']."<br>";
							echo "REQUEST URI: ".$_SERVER['REQUEST_URI']."<br>";
							echo "HOST: ".$_SERVER['HTTP_HOST']."<br>";
							echo "PHP INFO of Server:<br>";
							phpinfo();
							exit;
						}
						else
						{
							 $this->SetXmlError(1,$this->display_msg);
						}
						
						
						break;
						case 'getordersbydate':
						//Invokes GetOrdersByDate( DateFrom, DateTo ) which returns list of orders by date range, no paging
						
						$this->DateFrom=$this->GetValues('DateFrom');
						$this->DateTo=$this->GetValues('DateTo');
						
						//check for valid dates
						if($this->check_valid_date($this->DateFrom)!=1 || $this->check_valid_date($this->DateTo)!=1)
						{
							$this->SetXmlError(1,$this->display_msg);
							break;
						}
						
						//For all commands -At first check valid token & db access
						$respose_code=$this->Check_Settings();
						
						
						/*Response code set indicates has DB access, valid token, etc, so perform the required action.Otherwise display error mesage in XML format*/
						if($respose_code=="set")
						{	
							 //Get orders for specific cart
							 $cart_orders=$this->GetOrdersByDate($this->DateFrom,$this->DateTo);
							 
							 //if orders present in specified data range
							if(count($cart_orders)>0)
							{
								 //Convert cart orders to shipping order 
								 for($counter=0;$counter<count($cart_orders);$counter++)
								 {
									$shipping_orders[$counter]=$this->ConvertOrder($cart_orders[$counter]);
								 }
								  
								  //Prepare XML order
								  $this->OrdersToXML($shipping_orders);
							}
							else
							{
								$output='<?xml version="1.0"?><ShipmentOrders></ShipmentOrders>';
								
								$this->Display_XML_Output($output);
								
							}
						}
						else
						{
							 $this->SetXmlError(1,$this->display_msg);
						}
						break;
						
						
						
						case 'getordercountbydate':
						//Invokes GetOrderCountByDate( DateFrom, DateTo ) which returns order count
						
						$this->DateFrom=$this->GetValues('DateFrom');
						$this->DateTo=$this->GetValues('DateTo');
						
						//check for valid dates
						if($this->check_valid_date($this->DateFrom)!=1 || $this->check_valid_date($this->DateTo)!=1)
						{
							$this->SetXmlError(1,$this->display_msg);
							break;
						}
						
						//For all commands -At first check valid token & db access
						$respose_code=$this->Check_Settings();
						
						/*Response code set indicates has DB access, valid token, etc, so perform the required action.Otherwise display error mesage in XML format*/
						
						if($respose_code=="set")
						{	
							
							$this->SetXmlMessageResponse($this->wrap_to_xml('Ordercount',$this->GetOrderCountByDate($this->DateFrom,$this->DateTo)));
						}
						else
						{
							 $this->SetXmlError(1,$this->display_msg);
						}
						break;
						
						
						case 'updateshippinginfo':
						//Invokes UpdateShippingInfo(OrderNumber) which has following parameters:
						//order number (reqd)
						//tracking number (reqd)
						//shipment date (optional)
						//shipment type (optional)
						//shipment carrier (optional)
						//shipment service (optional)
						//shipping cost (optional)
						//notes block (which would be built by the calling app, and have tracking #, date, and other details in a friendly, ready-to-read block
						
						
						$OrderNumber=$this->GetValues('OrderNumber');
						
												
						//check for ordernumber & tracking number
						if($OrderNumber=="")
						{
							$this->display_msg=MISSING_ORDER_NUMBER_ERROR_MSG;
							$this->SetXmlError(1,$this->display_msg);
						}
						else
						{
							$TrackingNumber=$this->GetValues('TrackingNumber');
							$ShipDate=$this->GetValues('ShipDate');
							$ShipmentType=$this->GetValues('ShipmentType');
							$Notes=$this->GetValues('Notes');
							$Carrier=$this->GetValues('Carrier');
							$Service=$this->GetValues('Service');
							$ShippingCost=$this->GetValues('ShippingCost');
		
							
							//For all commands -At first check valid token & db access
							$respose_code=$this->Check_Settings();
							
							/*Response code set indicates has DB access, valid token, etc, so perform the required action.Otherwise display error mesage in XML format*/
							if($respose_code=="set")
							{	
								
								$this->UpdateShippingInfo($OrderNumber,$TrackingNumber,$ShipDate,$ShipmentType,$Notes,$Carrier,$Service,$ShippingCost);
								
							}
							else
							{
								 $this->SetXmlError(1,$this->display_msg);
							}
						}
						break;
							
						default:
						$respose_code=$this->Check_Settings();
						
						if($respose_code=="set")
						{
							$this->display_msg=INVALID_CMD;
							$this->SetXmlError(1,$this->display_msg);
						}
						else
						{
							 $this->SetXmlError(1,$respose_code);
						}
						break;
					
					
					}	
			
			
			}	
			###################################### Get offset of server time from UTC #######################
			/*Calculate offset along with direction i.e. + or - from GMT/UTC*/
			##################################################################################################
			function GetServerTimeOffsetFromUTC()
			{
				$difference_from_gmt_including_sign=date("O");
				$sign_from_gmt=substr($difference_from_gmt_including_sign,0,1);
				
				$len=strlen($difference_from_gmt_including_sign);
				
				$difference_from_gmt=substr($difference_from_gmt_including_sign,1,($len-1));
				
				return $sign_from_gmt.($difference_from_gmt); // hours from GMT
			}	
			###################################### Function CheckIfSet #######################
			/*Checks whether a variable is set or not*/
			##################################################################################################
			function CheckIfSet($array,$field)
			{
				if(isset($array[$field]))
					return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $array[$field]);
				else
					return '';
			}	
			###################################### Function GetClassProperty #######################
			/*Checks whether property is set or not & return values accordingly */
			##################################################################################################
			function GetClassProperty($classname,$propertyname,$field="",$defaultValue=0)
			{
				
				if(isset($classname->{$propertyname}))
				{
				
					if($field!="")
					{
						if(isset($classname->{$propertyname}[$field]))
							return $classname->{$propertyname}[$field];
						else
							return $defaultValue;
					}
					else
					{
						return $classname->{$propertyname};
					}
					
				}
			
			}
			############################################## Function GetClassPropertyNumber ##########################
			//calls GetClassProperty function with $defaultValue=1
			##################################################################################################
			function GetClassPropertyNumber($classname,$propertyname,$field="")
			{
				return $this->GetClassProperty($classname,$propertyname,$field,1);
			}	
			############################################## Function GetField #################################
			//Check if variables are set and return data accordingly
			#######################################################################################################
			function GetField($cart_order_temp,$field,$item_counter=-1,$defaultValueIsNumber=0)
			{
					
					 if($item_counter<-1)
					{	//for order items
					
					
						if(isset($cart_order_temp->$field))
						{
							return $cart_order_temp->$field;
						}
						else
						{
							if($defaultValueIsNumber)
								return 0;
							else
								return '';
						}
					
					}
					else if($item_counter>-1)
					{	//for order items
						if(isset($cart_order_temp[$item_counter][$field]))
						{
							return $cart_order_temp[$item_counter][$field];
						}
						else
						{
							if($defaultValueIsNumber)
								return 0;
							else
								return '';
						}
					
					}
					else
					{
						//shipping or billing array fields
						if(isset($cart_order_temp[$field]))
						{
							return $cart_order_temp[$field];
						}
						else
						{
							if($defaultValueIsNumber)
								return 0;
							else
								return '';
						}
					}
				
					
			}
			############################################## Function FormatNumber  ##########################
			//Formats number to money format
			##################################################################################################
			function FormatNumber($number)
			{
				if($number!="")
				{
					$matches="";
					$floats="";
					
					preg_match_all('!\d+(?:\.\d+)?!', $number, $matches);
					$floats = array_map('floatval', $matches[0]);
					$number=$floats[0];		
					
					return number_format($number,2,'.','');
				}
				else
					return "0.00";
			}
			############################################## Function GetFieldString ##########################
			//calls GetField function with $defaultValueIsNumber=""
			##################################################################################################
			function GetFieldString($cart_order_temp,$field,$item_counter=-1)
			{
				return $this->GetField($cart_order_temp,$field,$item_counter,"");
			}			
			############################################## Function GetFieldNumber  ##########################
			//calls GetField function with $defaultValueIsNumber=0
			##################################################################################################
			function GetFieldNumber($cart_order_temp,$field,$item_counter=-1)
			{
				return $this->GetField($cart_order_temp,$field,$item_counter,0);
			}	
			############################################## Function GetFieldMoney  ##########################
			//calls GetField function with $defaultValueIsNumber=0 and also formats number to money format
			##################################################################################################
			function GetFieldMoney($cart_order_temp,$field,$item_counter=-1)
			{
				$result=$this->GetField($cart_order_temp,$field,$item_counter,0);
				if($result!="")
				{
					return number_format($result,2,'.','');
				}
				else
				{
					return "0.00";
				}
			}
			
			################################################ Convert time ####################################
            /*Convert UTC time to server time*/
            ##################################################################################################
            function GetServerTimeLocal($formatted=true,$server_date_utc)
            {
               
                if(strpos($server_date_utc,"Z"))
                {
                    $utc_fotmat_temp=str_replace("Z","",$server_date_utc);
                    $server_date_utc=str_replace("T","",$utc_fotmat_temp);;//"T" & "Z" removed from UTC format(in ISO 8601)
                   
                }   
               
                //get offset
                $offset=$this->GetServerTimeOffsetFromUTC();
               
                $sign=substr($offset,0,1);
				
				$len=strlen($offset);
               
                $time_difference=substr($offset,1,($len-1));
					
											
				$hours=substr($time_difference,0,2);
				$mins=substr($time_difference,2,2);
				
				
				$server_date_utc_day=substr($server_date_utc,0,10);
                $server_date_utc_time=substr($server_date_utc,10,8);
               
                $server_date_utc_formmated=$server_date_utc_day." ".$server_date_utc_time;
               
                $server_date_utc_timestamp = strtotime($server_date_utc_formmated);
               
			   	$secs=($hours*3600)+($mins*60);
			   
                              
                if ($sign == "-")
                {
                   
                    $timestamp = $server_date_utc_timestamp-($secs);
                }
                else
                {
                    $timestamp = $server_date_utc_timestamp+($secs);
                }
               
               
                //Adjustment for daylight saving
				$daylight_saving = date('I',$timestamp); 
                if($daylight_saving)
				{
					 if ($sign == "-")
					{
					   
						$timestamp = $timestamp-3600;
					}
					else
					{
						$timestamp = $timestamp+3600;
					}
				}
			    
				 $server_date = date("Y-m-d H:i:s", $timestamp); //get Server Date
			   
                if($formatted==true)
                {
                    return $server_date;
                }
                else
                {
                    return $timestamp;
                }
           
            }
			##############################################################################################
			/*Convert Server time to UTC*/
			##############################################################################################
			function ConvertServerTimeToUTC($formatted=true,$server_time) 
			{
				
				//get offset
				$offset=$this->GetServerTimeOffsetFromUTC();
				$sign=substr($offset,0,1);
				
				$len=strlen($offset);
				$time_difference=substr($offset,1,($len-1));
				
				$hours=substr($time_difference,0,2);
				$mins=substr($time_difference,2,2);
				
				//Adjustment for daylight saving
				$daylight_saving = date('I',$server_time); 
                if($daylight_saving)
				{
					  if ($sign == "-")
					{
					   
						$hours = $hours-1;
					}
					else
					{
						$hours = $hours+1;
					}
				}
			
				$secs=($hours*3600)+($mins*60);
				
				if ($sign == "-")
				{ 
					$timestamp = $server_time+($secs); 
				}
				else 
				{ 
					$timestamp = $server_time-($secs); 
				}
				
				$gmdate = date("Y-m-d~H:i:s^", $timestamp); //get UTC date
				$gmdate=str_replace("~","T",$gmdate);
				$gmdate=str_replace("^","Z",$gmdate);
				if($formatted==true) 
				{
					return $gmdate;
				}
				else 
				{
					return $timestamp;
				}
			
			}
			############## Check if GET method is set or not and return parameters accordingly #################
			/*The script will support both POST & GET method depending upon settings*/
			##################################################################################################
			function GetValues($field_name)
			{
				if(HTTP_GET_ENABLED==1)
				{
					
					//make it case insensitive
					if(preg_match("/$field_name=/i",$_SERVER['QUERY_STRING'],$matches))
					{
						$case_insensitive_field_name=str_replace("=","",$matches[0]);
						return $_GET[$case_insensitive_field_name];
					}
					
				}
				else
				{
					$posted_string="";
					foreach($_POST as $key=>$val)
					{
						$posted_string.=$key."=".$val."&";
						
					}
					
					if(preg_match("/$field_name=/i",$posted_string,$matches))
					{
						$case_insensitive_field_name=str_replace("=","",$matches[0]);
						return $_POST[$case_insensitive_field_name];
					}
					
				
				}
			}
			
			############################### It will be used to output related messages to the user ###################
			//if there is an error, it will clearly state what is the issue & how it may be fixed etc
			##########################################################################################################
			function SetMessage($msg)
			{
				$this->display_msg=$msg;
								
			}
			
			############################## It will be used to generate XML error/informative messages ###################
			
			function SetXmlMessageResponse($msg)
			{
				$output='<?xml version="1.0"?>' . $this->wrap_to_xml('Response',$msg);
				
				$this->Display_XML_Output($output);
								
			}	
			
			################ It will be used to generate XML error messages(with error code & description) ###################
			
			function SetXmlError($code,$desc,$message_details="")
			{
				
				if($message_details=="")
				{
					$output='<?xml version="1.0"?>' . $this->wrap_to_xml('Error',$this->wrap_to_xml('Code',$code). $this->wrap_to_xml('Description',$desc).$this->wrap_to_xml('Version',SHIPPINGZCLASSES_VERSION));
				}
				else
				{
				
				$output='<?xml version="1.0"?>' . $this->wrap_to_xml('Error',$this->wrap_to_xml('Code',$code). $this->wrap_to_xml('Description',$desc).$this->wrap_to_xml('MessageDetails',$message_details).$this->wrap_to_xml('Version',SHIPPINGZCLASSES_VERSION));
				}
				
				$this->Display_XML_Output($output);
				
					
			}	
			############################### Check for valid date range ###########################################
		
			function check_valid_date($date)
			{
				
				if((strpos($date,"T")===false) || (strpos($date,"Z")===false))
				{
				
					$this->display_msg=INVAID_DATE_ERROR_MSG;
				}
				else 
				{	
					
					
					$date=str_replace("Z","",$date);	
					$date=str_replace("T"," ",$date);			
					
					$date_temp=explode(" ",$date);
					$date=$date_temp[0];
					
					$arr=explode("-",$date); // splitting the array
					if($date=="" || count($arr)!="3")
					{
						$this->display_msg=INVAID_DATE_ERROR_MSG;
					}
					else
					{
						
						$month=$arr[1]; // first element of the array is month
						$day=$arr[2]; // second element is date
						$year=$arr[0]; // third element is year
					
						if($month=="" || $day=="" || $year=="")
						{
							$this->display_msg=INVAID_DATE_ERROR_MSG;
						}
						else if(!is_numeric($month) || !is_numeric($day) || !is_numeric($year))
						{
							$this->display_msg=INVAID_DATE_ERROR_MSG;
						}
						else if(!checkdate($month,$day,$year))
						{
							$this->display_msg=INVAID_DATE_ERROR_MSG;
						}
						else 
						{
							return 1;
						} 
					}
				}//end UTC check		
			}
			
			################################################ Ping function #####################################	
			function Ping()
			{
				
				################################################# check for valid token#############################
				//It should be more than twelve characters long, less than 36, and must contain letters and numbers. 
				#####################################################################################################
				$token_lenght=strlen(SHIPPING_ACCESS_TOKEN);
				
				if($token_lenght<12 || $token_lenght>36)
				{
					$this->SetMessage(TOKEN_ERROR_MSG);
					
					
				}
				else if(!preg_match('/^[a-z0-9]+$/i', SHIPPING_ACCESS_TOKEN))//check does not contain special chars
				{
					$this->SetMessage(TOKEN_ERROR_MSG);
					
				}
				else if(!preg_match('#[0-9]#', SHIPPING_ACCESS_TOKEN))//check that contains atleast one digit
				{
					$this->SetMessage(TOKEN_ERROR_MSG);
					
				}
				else if(!preg_match('#[A-Z]#', SHIPPING_ACCESS_TOKEN)&&!preg_match('#[a-z]#', SHIPPING_ACCESS_TOKEN))//check that contains atleast one albhabet
				{
					$this->SetMessage(TOKEN_ERROR_MSG);
					
				}
				
					
				if($this->GetValues('shipping_access_token')!=SHIPPING_ACCESS_TOKEN&&$this->GetValues('SHIPPING_ACCESS_TOKEN')!=SHIPPING_ACCESS_TOKEN)
				{
					if($this->display_msg!=TOKEN_ERROR_MSG)
					$this->SetMessage(URL_TOKEN_ERROR_MSG);
				}
				
				
				
			}
			
       ############### This will be involked for all commands except "ping" to check proper settings ##################
			function Check_Settings()
			{
				//For all commands -At first check valid token & db access
				$this->Ping();
				
				if($this->display_msg=="")
				{	
					
					##################################################### Used for debugging ##########################
					if(isset($_GET['show_settings']))
					{
						if($_GET['show_settings']==1)
						{
							$handle = fopen("ShippingZSettings.php", "r");
							$contents="";
							while (!feof($handle)) 
							{
								$contents .= fread($handle, 8192);
							}
							fclose($handle); 
							print(htmlspecialchars($contents));
							exit;
						}
					}
					###########################################################################################
					
					$this->Check_DB_Access();//checks for DB access
					if($this->display_msg==DB_SUCCESS_MSG )
					{
						return "set";
					}
				}
				else
				{
					return $this->display_msg;
				}
			
			}
			
			
			############################# Definition of GetOrdersByDate function #####################################
			 function GetOrdersByDate($datefrom,$dateto) { }
			
			############################ Definition of GetOrderCountByDate function #####################################
			function GetOrderCountByDate($datefrom,$dateto) { }
			
			############################# Definition of UpdateShippingInfo function ##########################
			function UpdateShippingInfo($OrderNumber,$TrackingNumber,$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='') {}
			
			################################################ XML Serialization #################################### 
			//Creates XML node string
			// <fieldname>value</fieldname>
			#######################################################################################################	
			
			function wrap_to_xml( $fieldname, $fieldvalue )
			{
				return "<" . $fieldname . ">" . $fieldvalue . "</" . $fieldname . ">";
				
			}
			
			################################################ XML Serialization #################################### 
			//Creates CDATA XML node string
			// <fieldname><![CDATA[value]]></fieldname>
			#######################################################################################################
			    
			function wrap_to_xml_cdata( $fieldname, $fieldvalue )
			{
				return "<" . $fieldname . "><![CDATA[" . $fieldvalue . "]]></" . $fieldname . ">";
			}
			
			################################################ XML Serialization ####################################
			// Creates XML node from PHP array field
			#######################################################################################################
			function array_field_to_xml( $fieldname, $array )
			{
				if(isset($array[ $fieldname ]))
				return $this->wrap_to_xml_cdata( $fieldname, $array[ $fieldname ] );
			}
			
			################################################ XML Serialization #################################### 
			// Creates XML representation of the all order
			//ShipmentOrders element is added
			#######################################################################################################  
			function shipment_order_xml( $complete_shipment_order_xml )
			{
				
				return  '<?xml version="1.0"?>' . $this->wrap_to_xml( 
						'ShipmentOrders',$complete_shipment_order_xml);
				
				
			}
			
			################################################ XML Serialization #################################### 
			// Creates XML representation of the individual order
			#######################################################################################################  
			function shipment_individual_order_xml( $order )
			{
				return 
						$this->wrap_to_xml( 
						'ShipmentOrder',
						$this->order_info_xml( $order ) .
						$this->order_items_xml( $order ).
						$this->order_shipping_xml( $order ) .
						$this->order_billing_xml( $order ) );
			}
			
			
			################################################ XML Serialization #################################### 
			// Order items data as XML
			#######################################################################################################    
			function order_items_xml( $order )
			{
				$this->product_xml="";
				$this->all_product_xml="";
				for($prod_count=0; $prod_count < $order->num_of_products; $prod_count++)
				{
					   $this->product_xml= $this->array_field_to_xml( 'Name', $order->order_product[$prod_count] ) .
					   $this->array_field_to_xml( 'Price', $order->order_product[$prod_count] ) .
					   $this->array_field_to_xml( 'ExternalID', $order->order_product[$prod_count] ) .
					   $this->array_field_to_xml( 'Quantity' ,$order->order_product[$prod_count] ) . 
					    $this->array_field_to_xml( 'Notes' ,$order->order_product[$prod_count] ) . 
					   $this->array_field_to_xml( 'Total' , $order->order_product[$prod_count] );
					   $this->all_product_xml.=$this->wrap_to_xml( 'ShipmentOrderItem' ,$this->product_xml);
				}
				
				return $this->all_product_xml;
			}    
			
			
			################################################ XML Serialization ####################################
			// Delivery-To (shipping) address data as XML
			#######################################################################################################    
			function order_shipping_xml( $order )
			{
				return $this->wrap_to_xml( 'ShippingAddress' ,
					   $this->array_field_to_xml( 'FirstName',$order->order_shipping ) .
					   $this->array_field_to_xml( 'LastName', $order->order_shipping ) .
					   $this->array_field_to_xml( 'Company' , $order->order_shipping ) . 
					   $this->array_field_to_xml( 'Address1' , $order->order_shipping ) .
					   $this->array_field_to_xml( 'Address2' , $order->order_shipping ) .
					   $this->array_field_to_xml( 'City' , $order->order_shipping ) .
					   $this->array_field_to_xml( 'State'  , $order->order_shipping ) .
					   $this->array_field_to_xml( 'PostalCode' , $order->order_shipping ) .
					   $this->array_field_to_xml( 'Country'  , $order->order_shipping ) .
					   $this->array_field_to_xml( 'Phone'  ,$order->order_shipping ).
					   $this->array_field_to_xml( 'EMail'  , $order->order_shipping ) );
			}    
			
			################################################ XML Serialization ####################################
			// Billing address data as XML
			#######################################################################################################   
			function order_billing_xml( $order )
			{
				return $this->wrap_to_xml( 'BillingAddress' ,
					   $this->array_field_to_xml( 'FirstName', $order->order_billing ) .
					   $this->array_field_to_xml( 'LastName', $order->order_billing ) .
					   $this->array_field_to_xml( 'Company' , $order->order_billing ) . 
					   $this->array_field_to_xml( 'Address1' , $order->order_billing ) .
					    $this->array_field_to_xml( 'Address2' , $order->order_billing ) .
					   $this->array_field_to_xml( 'City' , $order->order_billing ) .
					   $this->array_field_to_xml( 'State'  , $order->order_billing ) .
					   $this->array_field_to_xml( 'PostalCode' , $order->order_billing ) .
					   $this->array_field_to_xml( 'Country'  , $order->order_billing ) .
					   $this->array_field_to_xml( 'Phone'  , $order->order_billing ) );
			}    
			
			################################################ XML Serialization ####################################
			// Order Info as XML
			#######################################################################################################  
			function order_info_xml( $order )
			{
				if(isset($order->order_info['PackageActualWeight']))
					 $package_xml=$this->array_field_to_xml( 'PackageActualWeight',$order->order_info );//added shipping weight
				else
					 $package_xml="";
					 
				if(isset($order->order_info['UOMWeight']))
					 $uom_xml=$this->array_field_to_xml( 'UOMWeight',$order->order_info );//added unit of shipping weight
				else
					 $uom_xml="";
					 
				
				if(isset($order->order_info['ExternalID']))
					 $externalID_xml=$this->array_field_to_xml( 'ExternalID',$order->order_info );//added unit of shipping weight
				else
					 $externalID_xml=$this->wrap_to_xml('ExternalID',$order->order_info["OrderNumber"] . '-' . $order->order_info["OrderDate"]);	 
				
				
				return 
					   $this->array_field_to_xml( 'OrderDate', $order->order_info) . 
					   $this->array_field_to_xml( 'ItemsTotal', $order->order_info ) . 
					   $this->array_field_to_xml( 'Total', $order->order_info ) .
					   $this->array_field_to_xml( 'ShippingChargesPaid' , $order->order_info ) . 
					    $this->array_field_to_xml( 'ShipMethod' , $order->order_info ) . 
					   $this->array_field_to_xml( 'ItemsTax' , $order->order_info ) .
					   $this->array_field_to_xml( 'OrderNumber' , $order->order_info) .
					   $externalID_xml .
					   $this->array_field_to_xml( 'ShippingSameAsBilling'  , $order->order_info ) .
					   $this->array_field_to_xml( 'Comments'  , $order->order_info ) .
					   $this->array_field_to_xml( 'PaymentType' , $order->order_info ) .
					   $this->array_field_to_xml( 'PaymentStatus' , $order->order_info ).
					   $this->array_field_to_xml( 'IsShipped' , $order->order_info ).
					   $this->array_field_to_xml( 'IsCancelled' , $order->order_info ).$uom_xml.$package_xml;
					   
			} 
			
				############################################## Function ConvertOrder #################################
				//Conver cart order to shipping_order
				#######################################################################################################
				  function ConvertOrder($cart_order_array)
				  {
					
						//prepare order array
						$shipping_order->orderid=$cart_order_array->orderid;
						$shipping_order->num_of_products=$cart_order_array->num_of_products;
						
						//shipping details
						$shipping_order->order_shipping["FirstName"]=$this->MakeXMLSafe($cart_order_array->order_shipping["FirstName"]);
						$shipping_order->order_shipping["LastName"]=$this->MakeXMLSafe($cart_order_array->order_shipping["LastName"]);
						$shipping_order->order_shipping["Company"]=$this->MakeXMLSafe($cart_order_array->order_shipping["Company"]);
						$shipping_order->order_shipping["Address1"]=$this->MakeXMLSafe($cart_order_array->order_shipping["Address1"]);
						
						if(isset($cart_order_array->order_shipping["Address2"]))
						$shipping_order->order_shipping["Address2"]=$this->MakeXMLSafe($cart_order_array->order_shipping["Address2"]);
						
						$shipping_order->order_shipping["City"]=$this->MakeXMLSafe($cart_order_array->order_shipping["City"]);
						$shipping_order->order_shipping["State"]=$this->MakeXMLSafe($cart_order_array->order_shipping["State"]);
						$shipping_order->order_shipping["PostalCode"]=$this->MakeXMLSafe($cart_order_array->order_shipping["PostalCode"]);
						$shipping_order->order_shipping["Country"]=$this->MakeXMLSafe($cart_order_array->order_shipping["Country"]);
						$shipping_order->order_shipping["Phone"]=$this->MakeXMLSafe($cart_order_array->order_shipping["Phone"]);
						$shipping_order->order_shipping["EMail"]=$this->MakeXMLSafe($cart_order_array->order_shipping["EMail"]);
						
						//billing details
						$shipping_order->order_billing["FirstName"]=$this->MakeXMLSafe($cart_order_array->order_billing["FirstName"]);
						$shipping_order->order_billing["LastName"]=$this->MakeXMLSafe($cart_order_array->order_billing["LastName"]);
						$shipping_order->order_billing["Company"]=$this->MakeXMLSafe($cart_order_array->order_billing["Company"]);
						$shipping_order->order_billing["Address1"]=$this->MakeXMLSafe($cart_order_array->order_billing["Address1"]);
						
						if(isset($cart_order_array->order_billing["Address2"]))
						$shipping_order->order_billing["Address2"]=$this->MakeXMLSafe($cart_order_array->order_billing["Address2"]);
						
						$shipping_order->order_billing["City"]=$this->MakeXMLSafe($cart_order_array->order_billing["City"]);
						$shipping_order->order_billing["State"]=$this->MakeXMLSafe($cart_order_array->order_billing["State"]);
						$shipping_order->order_billing["PostalCode"]=$this->MakeXMLSafe($cart_order_array->order_billing["PostalCode"]);
						$shipping_order->order_billing["Country"]=$this->MakeXMLSafe($cart_order_array->order_billing["Country"]);
						$shipping_order->order_billing["Phone"]=$this->MakeXMLSafe($cart_order_array->order_billing["Phone"]);
						
						//order info
						$shipping_order->order_info["OrderDate"]=$this->MakeXMLSafe($cart_order_array->order_info["OrderDate"]);
						
						if(isset($cart_order_array->order_info["ExternalID"]))
						$shipping_order->order_info["ExternalID"]=$this->MakeXMLSafe($cart_order_array->order_info["ExternalID"]);
						
						$shipping_order->order_info["ItemsTotal"]=$this->MakeXMLSafe($this->GetFieldMoney($cart_order_array->order_info,"ItemsTotal"));
						$shipping_order->order_info["Total"]=$this->MakeXMLSafe($this->GetFieldMoney($cart_order_array->order_info,"Total"));
						$shipping_order->order_info["ShippingChargesPaid"]=$this->MakeXMLSafe($this->GetFieldMoney($cart_order_array->order_info,"ShippingChargesPaid"));
						$shipping_order->order_info["ShipMethod"]=$this->MakeXMLSafe($cart_order_array->order_info["ShipMethod"]);
						$shipping_order->order_info["ItemsTax"]=$this->MakeXMLSafe($this->GetFieldMoney($cart_order_array->order_info,"ItemsTax"));
						$shipping_order->order_info["OrderNumber"]=$this->MakeXMLSafe($cart_order_array->order_info["OrderNumber"]);
						$shipping_order->order_info["PaymentType"]=$this->MakeXMLSafe($cart_order_array->order_info["PaymentType"]);
						$shipping_order->order_info["Comments"]=$this->MakeXMLSafe($cart_order_array->order_info["Comments"]);
						$shipping_order->order_info["PaymentStatus"]=$this->MakeXMLSafe($cart_order_array->order_info["PaymentStatus"]);
						$shipping_order->order_info["IsShipped"]=$this->MakeXMLSafe($cart_order_array->order_info["IsShipped"]);
						$shipping_order->order_info["IsCancelled"]= $this->MakeXMLSafe($this->CheckIfSet($cart_order_array->order_info,"IsCancelled"));
						
						if(isset($cart_order_array->order_info["UOMWeight"]))
						$shipping_order->order_info["UOMWeight"]= $this->MakeXMLSafe($this->CheckIfSet($cart_order_array->order_info,"UOMWeight"));

						$shipping_order->order_info["PackageActualWeight"]="";	
						
						//get order products 
                        if (isset($cart_order_array->order_product))
						{    
							for($j=0;$j<count($cart_order_array->order_product);$j++)
						    {
								
								$shipping_order->order_product[$j]["Name"]=$this->MakeXMLSafe($this->CheckIfSet($cart_order_array->order_product[$j],"Name"));	
								$shipping_order->order_product[$j]["Price"]=$this->MakeXMLSafe($this->GetFieldMoney($cart_order_array->order_product[$j],"Price"));
								$shipping_order->order_product[$j]["Quantity"]=$this->MakeXMLSafe($this->CheckIfSet($cart_order_array->order_product[$j],"Quantity"));
								$shipping_order->order_product[$j]["Total"]=$this->MakeXMLSafe($this->GetFieldMoney($cart_order_array->order_product[$j],"Total"));
								$shipping_order->order_product[$j]["ExternalID"]= $this->MakeXMLSafe($this->CheckIfSet($cart_order_array->order_product[$j],"ExternalID"));
								$shipping_order->order_product[$j]["Notes"]=$this->MakeXMLSafe($this->CheckIfSet($cart_order_array->order_product[$j],"Notes"));
								
								if(isset($cart_order_array->order_product[$j]["Total_Product_Weight"]))
								{
									if($cart_order_array->order_product[$j]["Total_Product_Weight"]!="")
									{
										$shipping_order->order_product[$j]["Total_Product_Weight"]=$this->MakeXMLSafe($cart_order_array->order_product[$j]["Total_Product_Weight"]);//add product weight
										$shipping_order->order_info["PackageActualWeight"]+=$this->MakeXMLSafe($shipping_order->order_product[$j]["Total_Product_Weight"]);//total shipping weight
									}
									
								}
								
								
						   }
						}
						if($shipping_order->order_info["PackageActualWeight"]!="")
					  	$shipping_order->order_info["PackageActualWeight"]=$this->MakeXMLSafe(number_format($shipping_order->order_info["PackageActualWeight"],2,'.',''));
						   
					  return $shipping_order;
				  }
			######################################## function MakeXMLSafe ############################################	
			//Make a string completely safe for XML-required for user comments
			##########################################################################################################
			function MakeXMLSafe ($strin) 
			{
				//removed encoding logic(will be dealt in My.SR), keep this function for future use if required
				$strout=$strin;
				
				return $strout;
		    }
			######################################## function OrdersToXML ############################################	
			//Generate order XML
			##########################################################################################################
		    function OrdersToXML($shipping_orders)
			{
								
				for($i=0;$i<count ($shipping_orders);$i++)
				{
				
					$this->complete_shipment_order_xml.=$this->shipment_individual_order_xml($shipping_orders[$i]);
				}
				$output=$this->shipment_order_xml( $this->complete_shipment_order_xml );
				
				$this->Display_XML_Output($output);
			
			
			}
			######################################## function ConvertPaymentType ####################################	
			//Convert from string to PaymentType
			##########################################################################################################			
			function ConvertPaymentType($string)
			{
					//- If matches one of our types -> return it
					$PaymentType=-1;
					$string=strtolower($string);
					
					switch($string)
					{
						case 'creditcard': 
						$PaymentType=0;
						break;
						
						case 'personalcheck':
						$PaymentType=1;
						break;
						
						case 'moneyorder':
						$PaymentType=2;
						break;
						
						case 'paypal':
						$PaymentType=3;
						break;
						
						case 'other':
						$PaymentType=4;
						break;
					
					}	
					
					if($PaymentType!=-1)
					{
						return $PaymentType;
					}
					else
					{
						if( strstr($string,"check"))
						{		
							$PaymentType=1;
						}
						if( strstr($string,"paypal"))
						{		
							$PaymentType=3;
						}
						
						if(strstr($string,"cc" )|| strstr($string,"visa") || strstr($string,"mc")|| strstr($string,"mastercard")|| strstr($string,"amex")|| strstr($string,"discover")|| strstr($string,"credit"))
						{
							$PaymentType=0;
						}
						
						if($PaymentType==-1)
						{
							$PaymentType=4;
						
						}
						
						return $PaymentType;
					}				
				
					
			}
			
			############################### It will be used to calculate Response length ###################
			function GetResponseLength($response)
			{
				return strlen($response);
								
			}
			
			############################### It will be used to stop SQL Injectiononse ###################
			function MakeSqlSafe($value,$is_number=0)
			{
				
				$value=str_replace("%","",$value);
				
				if(ini_get("magic_quotes_gpc") )
				{
				 	$value=stripslashes($value);
				}
				
				if(!$is_number )
				{
					$value = mysql_real_escape_string($value) ;
				}
				else
				{
					$value=(int)$value;
				}
				
		  		return $value;
		}  
			############################### It will be used to display XML with header ###################
			function Display_XML_Output($output)
			{
        $output = $output."\r\n\r\n\r\n\r\n\r\n\r\n\r\n"; 
				header("Pragma: public");
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");   // Date in the past
				header("Content-type: text/xml");
				header("Content-Disposition: inline; filename=xml_order.xml");
				header("Content-Length: ".$this->GetResponseLength($output));
				echo $output;
				
				exit;
								
			}
			############################### Check for predefined custom errors #########################################
			//Detect low level known errors and raise human friendly version of error as specified in Settings.php file.
			##############################################################################################################
			function CheckAndOverrideErrorMessage($error_string)
			{
				$custom_error_details="";
				if(strstr(strtolower($error_string),"parse error") && strstr(strtolower($error_string),"soap.php"))
				{
					$custom_error_details=cMagento141Problem;
				}
				else if(strstr(strtolower($error_string),"access denied"))
				{
					$custom_error_details=cMagentoSOAPPermissionError;
				}
				else if(strstr(strtolower($error_string),"curl error: ssl certificate problem"))
				{
					$custom_error_details=cMagentoCurlSSLError;
				}
				
				if($custom_error_details!="")
				{
					$this->SetXmlError(1,$custom_error_details);
					exit;
				
				}
								
			}
	}

############################################## Custom Error Handling ######################################
//Function to display back Trace Messages
function ShowDebugBacktrace() 
{
    $DebugTraceMsg = '';
    $MAXLEN = 64;
    $traceArr = debug_backtrace();
    array_shift($traceArr);
    $tabs = sizeof($traceArr)-1;
   
	foreach($traceArr as $arr)
	{
        for ($i=0; $i < $tabs; $i++) $DebugTraceMsg .= ' &nbsp; ';
        $tabs -= 1;
        
        if (isset($arr['class'])) $DebugTraceMsg .= $arr['class'].'.';
		
        $args = array();
		
        if(!empty($arr['args'])) 
		{
			foreach($arr['args'] as $val)
			{
				if (is_null($val)) $args[] = 'null';
				else if (is_array($val)) $args[] = 'Array['.sizeof($val).']';
				else if (is_object($val)) $args[] = 'Object:'.get_class($val);
				else if (is_bool($val)) $args[] = $val ? 'true' : 'false';
				else
				{
					$val = (string) @$val;
					$str = htmlspecialchars(substr($val,0,$MAXLEN));
					if (strlen($val) > $MAXLEN) $str .= '...';
					$args[] = "\"".$str."\"";
				}
			}
		}
		
        $DebugTraceMsg .= $arr['function'].'('.implode(', ',$args).')';
       
        $DebugTraceMsg .= "<br>";
    }   
    
    return $DebugTraceMsg;
}

//Function to display error messages along with backtrace
function ShippingZ_Exception_Error_Handler($errno, $errstr, $errfile, $errline ) 
{
   
   if(!defined('E_STRICT')) define('E_STRICT', 2048);
   //Display all types of errors including notices
   //Check if error is related to ShippingZ Integration Files 
   if( $errno!=E_STRICT && (strstr(strtolower($errfile),basename(strtolower($_SERVER['PHP_SELF'])))||strstr(strtolower($errfile),"shippingzsettings.php") || strstr(strtolower($errfile),"shippingzclasses.php") || strstr(strtolower($errfile),"shippingzmessages.php"))) 
   {
  	   //Display error message
	   $message="";
	   $message .= "<br><b>SHIPPINGZCLASSES Version:</b>".SHIPPINGZCLASSES_VERSION."<br>";
	   $message .= "<strong>Error Type:</strong> ".print_r($errno, true)."<br>";
	   $message .= "<strong>File:</strong> ".print_r( $errfile, true)."<br>";
	   $message .= "<strong>Line:</strong> ".print_r( $errline, true)."<br><br>";
	   $message .= "<strong>Message:</strong> ".print_r( $errstr, true)."<br><br>";
	   $message .= "<strong>Trace:</strong> ".ShowDebugBacktrace();
	   echo $message;
	   exit; 
	}
   
 
}

#########################################################################################################################
?>