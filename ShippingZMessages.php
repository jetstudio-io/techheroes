<?php

define("SHIPPINGZMESSAGES_VERSION","3.0.0.55139");

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



##################################### Define output messages##############################################################
//Do not change the following section,leave it as it is 
###########################################################################################################################

######################################################## Generic Section #############################################
//Required for all shopping carts
###########################################################################################################################

define("TOKEN_ERROR_MSG","Authentication failed. Please open file \"ShippingZSettings.php\" and set the SHIPPING_ACCESS_TOKEN to a random string of letters and numbers,at least 12 characters long.");
define("URL_TOKEN_ERROR_MSG","Authentication failed. Invalid shipping_access_token specified.");
define("DB_ERROR_MSG","Shipping module cannot access database.");
define("DB_SUCCESS_MSG","Success. Access token database access verified.");
define("INVALID_CMD","Invalid command. Allowed commands are: ping, getserverinfo, getordersbydate, getordercountbydate, updateshippinginfo.");
define("INVAID_DATE_ERROR_MSG","Invalid date format. \"DateFrom\" and \"DateTo\" parameters should be in ISO 8601 format. YYYY-MM-DDThh:mm:ssZ.");
define("INVAID_TRACKING_NUMBER_MSG","URL parameter TrackingNumber not found.");
define("NO_ORDER_ERROR_MSG","There is no pending orders in this period.");
define("INVAID_ORDER_NUMBER_ERROR_MSG","Order not found.");
define("MISSING_ORDER_NUMBER_ERROR_MSG","URL parameter OrderNumber not found.");
######################################################## Magento Cart Section #############################################
//Required for only for Magento Cart
###########################################################################################################################
define("MAGENTO_TEMPORARY_ERROR_MSG", "Can not access Magento API. Please check the URL and access token. The URL must match the configuration of the Magento system. If this problem persists, please review the Magento setup steps.");
define("MAGENTO_API_NOT_SET_ERROR_MSG","Please open the file \"ShippingZSettings.php\" and set WebsiteUrl, Magento_Username and Magento_Password. Notes for creating API user and required Role: 1. Go to admin section of Magento

2. Go to System>Web Services>Users

   -Click on \"Add new user\"

   -Fill up required fields like User name (say ShippingUser), First name, Api key (say ShippingKey123) etc

   -Then click on \"Save user\" button

3. Go to System>Web Services>Roles

   -Click on \"Add new role\"

   -Enter Role name (say ShippingRole)

   -Then click on \"Save Role\" button

  - After Role successfully is saved, click on \"Role Resources\" (present at the left panel).

  - Select \"All\" option from the \"Resource Access\" drop down.

  -Click on \"Save Role\" button

4. Now again go to System>Web Services>Users

   -User list will appear

  -Click on the username (which you have just created, say ShippingUser)

  -Then from the left panel, click on User Role

  -Role list will appear

  -Select the Role (which you have just created, say ShippingRole)

  -Then click on \"Save user\" button.

This api user name and key should be used for shipping module.");//for magento cart only
define("MAGENTO_WRONG_STORE_URL_ERROR_MSG","Please, make sure that you use right URL. Url is case sensitive");//for magento cart only
define("MAGENTO_WRONG_API_DETAILS_ERROR_MSG","Wrong Api credentials.Please open the file \"ShippingZSettings.php\" and set Magento_Username and Magento_Password properly. Notes for creating API user and required Role: 1. Go to admin section of Magento

2. Go to System>Web Services>Users

   -Click on \"Add new user\"

   -Fill up required fields like User name (say ShippingUser), First name, Api key (say ShippingKey123) etc

   -Then click on \"Save user\" button

3. Go to System>Web Services>Roles

   -Click on \"Add new role\"

   -Enter Role name (say ShippingRole)

   -Then click on \"Save Role\" button

  - After Role successfully is saved, click on \"Role Resources\" (present at the left panel).

  - Select \"All\" option from the \"Resource Access\" drop down.

  -Click on \"Save Role\" button

4. Now again go to System>Web Services>Users

   -User list will appear

  -Click on the username (which you have just created, say ShippingUser)

  -Then from the left panel, click on User Role

  -Role list will appear

  -Select the Role (which you have just created, say ShippingRole)

  -Then click on \"Save user\" button.

This api user name and key should be used for shipping module.");//for magento cart only

define("cMagento141Problem","It appears you are running Magento 1.4.1.0. This version has a broken soap.php file. Please refer to http://www.magentocommerce.com/boards/viewthread/195876/");

define("cMagentoSOAPPermissionError","It appears that Magento Api user does not have proper permissions");
define("cMagentoCurlSSLError","Curl Error: SSL Certificate Problem.Verify that the CA cert is OK.");
###########################################################################################################################
?>