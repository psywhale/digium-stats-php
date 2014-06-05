<?php
require_once("SwitchvoxRequest.php");
require_once("config.php");

$request = new SwitchvoxRequest("$CFG->host", "$CFG->user", "$CFG->password");

$Ago = `date +"%F 00:00:01" --date="yesterday"`;
$Today = `date +"%F 23:59:59" --date="yesterday"`;
$Ago = trim($Ago);
$Today = trim($Today);

$requestParams = array( 'start_date' => $Ago,'end_date' => $Today,
			'ignore_weekends' =>false,
		        'queue_account_ids'=>array('queue_account_id'=>1235),
			'report_fields'=>array('report_field'=>array('abandoned_calls',
								     'completed_calls',
								     'total_calls')),
			'format'=>"xml",
//			'sort_field'=>'start_time',
			'breakdown' => "by_hour_of_day",
			'sort_field' => "hour",
			'sort_order'=>'ASC' //or DESC
);
$response = $request->send("switchvox.callQueueReports.search", $requestParams);
//var_dump($response);

$result = $response->getResult();
$daydata = $result['hours_of_day']['hour_of_day'];

//print_r($daydata);

$HTML = "
<html>
<head>
<title> Helpdesk last 24</title>
</head>
<body>
<div>
<h2>Yesterday 24 hours</h2>
<table border=1>
<tr><th>hour</th><th>Completed</th><th>Abandoned</th><th>total</th></tr>
";
$totals = array('complete'=>0,'abandon'=>0,'total'=>0);
foreach($daydata as $day) {
   $HTML .= "<tr><td>".$day['hour']."</td><td>".$day['completed_calls']."</td><td>".$day['abandoned_calls']."</td><td>".$day['total_calls']."</td></tr>";
  $totals['complete'] += $day['completed_calls'];
  $totals['abandoned'] += $day['abandoned_calls'];
  $totals['total'] += $day['total_calls'];


}
$HTML .= "<tr><td></td>
<td>".$totals['complete']."</td>
<td>".$totals['abandoned']."</td>
<td>".$totals['total']."</td>
</tr></table></div></body></html>";

echo $HTML;
?>
