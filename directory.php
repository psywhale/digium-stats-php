<?php
//require_once("SwitchvoxRequest.php");
//
require_once("mm_switchvox_integration.php");
require_once("config.php");

//$request = new SwitchvoxRequest("$CFG->host", "$CFG->user", "$CFG->password");
$request = mm_switchvox::getInstance("$CFG->host", "$CFG->user", "$CFG->password");

$Ago = `date +"%F 00:00:01" --date="yesterday"`;
$Today = `date +"%F 23:59:59" --date="yesterday"`;
$Ago = trim($Ago);
$Today = trim($Today);

$requestParams = array( 
			'sort_field'=>'number',
            'sort_order'=>'ASC',
            'items_per_page'=>300
);
$response = $request->request("switchvox.directories.getExtensionList", $requestParams);
var_dump($response);

foreach($response['directory']['extensions']["extension"] as $ext) {
    if(array_key_exists("email_address",$ext)) {
        if($ext["email_address"] == "PBX@wosc.edu") {
            echo $ext["number"]."\n";
        }
    }


}


die;
$result = $response->getResult();
$daydata = $result['hours_of_day']['hour_of_day'];

//print_r($daydata);

?>
