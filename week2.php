<?php
require_once("SwitchvoxRequest.php");
require_once("config.php");

$request = new SwitchvoxRequest("$CFG->host", "$CFG->user", "$CFG->password");

$HTML = "
	<html>
	<head>
	<title> Helpdesk by week</title>
	</head>
	<body>
	<div>
	<h2> Last 7 days </h2>
	<table border=1>
	<tr><th>Day</th><th>Completed</th><th>Abandoned</th><th>Total</th></tr>
	";
$totals = array('complete'=>0,'abandon'=>0,'total'=>0);
for($i=7;$i>=1;$i--) {


	$Start = `date +"%F 00:00:01" --date="$i days ago"`;
//	echo $Ago;

//die;
	$End = `date +"%F 23:59:00" --date="$i days ago"`;
	$Start = trim($Start);
	$End = trim($End);

	$requestParams = array( 'start_date' => $Start,'end_date' => $End,
			'ignore_weekends' =>false,
		        'queue_account_ids'=>array('queue_account_id'=>1235),
			'report_fields'=>array('report_field'=>array('abandoned_calls',
								     'completed_calls',
								     'total_calls')),
			'format'=>"xml",
//			'sort_field'=>'start_time',
			'breakdown' => "by_day"
//			'sort_field' => "day",
//			'sort_order'=>'ASC' //or DESC
);
	$response = $request->send("switchvox.callQueueReports.search", $requestParams);
//var_dump($response);

	$result = $response->getResult();
	$daydata = $result['days']['day'];

//print_r($daydata);

$HTML .= "<tr><td>".$daydata['date']."</td><td>".$daydata['completed_calls']."</td><td>".$daydata['abandoned_calls']."</td><td>".$daydata['total_calls']."</td></tr>";
  $totals['complete'] += $daydata['completed_calls'];
  $totals['abandon'] += $daydata['abandoned_calls'];
  $totals['total'] += $daydata['total_calls'];
}
$HTML .= "<tr><td></td>
<td>".$totals['complete']."</td>
<td>".$totals['abandon']."</td>
<td>".$totals['total']."</td>
</tr></table></div></body></html>";

	echo $HTML;
?>
