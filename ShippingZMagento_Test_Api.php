<?php
##############################################################################################################################
//SHIPPINGZ MAGENTO API TEST SCRIPT
############################################### Check & adjust "default_socket_timeout"#######################################
$timeout_value="";
$timeout_value=@ini_get("default_socket_timeout");
if($timeout_value!="" && $timeout_value<120)
@ini_set("default_socket_timeout",120);
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
############################################### SETTINGS ########################################
$domain="localhost.com";
$check_domain=0; //set 0 if domain check not required like for localhost
$dir="newmagento_v1_4"; //leave blank if not applicable
$host= 'http://www.'.$domain.'/'.$dir;
$apiuser= 'SHIPPINGZ'; //Magento API User
$apikey = 'myshiprushapikey2010';//Magento API Key
############################################# Domain check #######################################
function checkDomain($domain,$server,$findText)
{
        // Open a socket connection to the whois server
        $con = fsockopen($server, 43);
        if (!$con) return false;
        
        // Send the requested doman name
        fputs($con, $domain."\r\n");
        
        // Read and store the server response
        $response = ' :';
        while(!feof($con)) {
            $response .= fgets($con,128); 
        }
        
        // Close the connection
        fclose($con);
        
        // Check the response stream whether the domain is available
        if (strpos($response, $findText)){
            return true;
        }
        else {
            return false;   
        }
 }
 
 if($check_domain)
 {
 	echo "Checking Domain Name....<br>";
	if (checkDomain($domain,'whois.crsnic.net','No match for'))
	{
		  echo "Domain accessible<br><br>";
	}
	else echo "Domain not accessible<br><br>";
}
############################################# DNS lookup ########################################
function win_checkdnsrr($host, $type='MX') 
{
    if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') { return; }
    if (empty($host)) { return; }
    $types=array('A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY');
    if (!in_array($type,$types)) {
        user_error("checkdnsrr() Type '$type' not supported", E_USER_WARNING);
        return;
    }
    @exec('nslookup -type='.$type.' '.escapeshellcmd($host), $output);
    foreach($output as $line){
        if (preg_match('/^'.$host.'/',$line)) { return true; }
    }
}

// Define
if (!function_exists('checkdnsrr')) {
    function checkdnsrr($host, $type='MX') {
        return win_checkdnsrr($host, $type);
    }
}

echo "Checking DNS....<br>";

$result=checkdnsrr($domain);
if ($result)
{
	  echo "DNS records found<br><br>";
}
else 
echo "DNS records not found<br><br>";
############################################# Check if url is accessible ###################
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
			
	$http_code =curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $http_code."~=~".$fp;

}
  echo "Checking URL....<br>";
//check if the url is proper & we can access wsdl
$curl_result=EXECUTE_CURL($host.'/index.php/api/soap/?wsdl');
$curl_result_temp=explode("~=~",$curl_result);
$fp = $curl_result_temp[1]; 
$http_code =$curl_result_temp[0];
if($http_code==200)
{
	$proxy= new SoapClient($host.'/index.php/api/soap/?wsdl',array('exceptions' => 1,'trace' => 1,"connection_timeout" => 120));
	 
	
	try {
	  $sessionId= $proxy->login($apiuser, $apikey);
	  echo  "Magento Api accessed Successfully.";
	  echo "<br>Session Id is:". $sessionId;
	} catch (Exception $e) {
	  echo "==> Error: ".$e->getMessage();
	  exit();
	} 	
}
else
{
	echo "Can not aceess url";

}

?>