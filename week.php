<?php
require_once("SwitchvoxRequest.php");
require_once("config.php");

$request = new SwitchvoxRequest("$CFG->host", "$CFG->user", "$CFG->password");

$Ago = `date +"%F 00:00:00" --date="week ago"`;
$Today = `date +"%F 00:00:00"`;
$Ago = trim($Ago);
$Today = trim($Today);

$requestParams = array( 'start_date' => $Ago,'end_date' => $Today,
			'ignore_weekends' =>false,
		        'queue_account_ids'=>array('queue_account_id'=>1235),
			'report_fields'=>array('report_field'=>array('abandoned_calls',
								     'completed_calls',
								     'redirected_calls')),
			'format'=>"xml",
//			'sort_field'=>'start_time',
			'breakdown' => "by_day_of_week",
			'sort_field' => "day",
//			'sort_order'=>'ASC' //or DESC
);
$response = $request->send("switchvox.callQueueReports.search", $requestParams);
//var_dump($response);

$result = $response->getResult();
$daydata = $result['days_of_week']['day_of_week'];

//print_r($daydata);

$HTML = "
<html>
<head>
<title> Helpdesk by week</title>
</head>
<body>
<div>
<h2> Last 7 days </h2>
<table>
<tr><th>Day</th><th>Completed</th><th>Abandoned</th><th>Redirected</th></tr>
";
foreach($daydata as $day) {
   $HTML .= "<tr><td>".$day['day']."</td><td>".$day['completed_calls']."</td><td>".$day['abandoned_calls']."</td><td>".$day['redirected_calls']."</td></tr>";
}
$HTML .= "</table></div></body></html>";

echo $HTML;
?>
