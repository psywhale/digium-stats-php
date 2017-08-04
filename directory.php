<?php
require __DIR__.'/vendor/autoload.php';
require_once("config.php");

//$request = new SwitchvoxRequest("$CFG->host", "$CFG->user", "$CFG->password");
//$request = mm_switchvox::getInstance("$CFG->host", "$CFG->user", "$CFG->password");
$request = new Switchvox\SwitchvoxClient();
$request->uri=$CFG->host;
$request->user=$CFG->user;
$request->password=$CFG->password;

//$request->data_type='xml';

$Ago = `date +"%F 00:00:01" --date="yesterday"`;
$Today = `date +"%F 23:59:59" --date="yesterday"`;
$Ago = trim($Ago);
$Today = trim($Today);

$requestParams =[
  'sort_field'=>'number',
  'sort_order'=>'ASC',
  'items_per_page'=>'9999'
];

//$response = $request->send('switchvox.status.phones.getList', $requestParams);
$response = $request->send('switchvox.directories.getExtensionList', $requestParams);

print_r($response->body);
$result = $response->body->response->result;
foreach ($result->directory->extensions->extension as $sip_phone) {
    if($sip_phone->type == "sip") {
        echo "$sip_phone->number,$sip_phone->display,$sip_phone->first_name,$sip_phone->last_name,$sip_phone->location\n";
    }
//    echo "$sip_phone->caller_id $sip_phone->extension\n";
}

///$response->



die;
$result = $response->getResult();
$daydata = $result['hours_of_day']['hour_of_day'];

//print_r($daydata);

?>
