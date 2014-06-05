<?php
require_once("SwitchvoxRequest.php");
require_once("config.php");

$request = new SwitchvoxRequest("$CFG->host", "$CFG->user", "$CFG->password");

$MonthAgo = `date +"%F 00:00:00" --date="a year ago"`;
$Today = `date +"%F 00:00:00"`;
$MonthAgo = trim($MonthAgo);
$Today = trim($Today);

$requestParams = array( 'start_date' => $MonthAgo,'end_date' => $Today,
			'ignore_weekends' =>true,
		        'queue_account_ids'=>array('queue_account_id'=>1235),
			'report_fields'=>array('report_field'=>array('abandoned_calls',
								     'completed_calls',
								     'redirected_calls')),
			'format'=>"xml",
//			'sort_field'=>'start_time',
			'breakdown' => "by_day",
			'items_per_page' => 9999,
			'sort_field' => 'date',
			'sort_order'=>'ASC' //or DESC
	
);
$response = $request->send("switchvox.callQueueReports.search", $requestParams);

//echo $response->getRawXMLResponse();

$result = $response->getResult();
$daydata = $result['days']['day'];
$count = count($daydata);
$chartjs = "
<html>
  <head>
    <script type='text/javascript' src='http://www.google.com/jsapi'></script>
    <script type='text/javascript'>
      google.load('visualization', '1.1', {'packages':['annotationchart']});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
  var data = new google.visualization.DataTable();
  data.addColumn('date', 'Date');
  data.addColumn('number', 'completed');
  data.addColumn('number', 'abandoned');
  data.addColumn('number', 'redirected');
  data.addRows([";

$counter = 0;
foreach($daydata as $daycalls) {
    $date = fixDate($daycalls['date']);
    $chartjs .= "[new Date(".$date['year'].",".$date['month'].",".$date['day']."), ".$daycalls['completed_calls'].",".$daycalls['abandoned_calls'].",".$daycalls['redirected_calls']."]";
    $counter++;
//    echo "\n$count : $counter\n";
    if($counter < $count) {$chartjs.= ",\n";} else {$chartjs.="\n";}
}
$chartjs .= "
]);

  var chart_calls = new google.visualization.AnnotationChart(document.getElementById('chart_div'));
  chart_calls.draw(data, {'displayAnnotations': true,'fill':20});
}

</script>
  </head>

  <body>
 <h2> HelpDesk Call Stats from $MonthAgo to $Today</h2>
    <div id='chart_div' style='width: 900px; height: 500px;'></div>
  </body>
</html>

";
echo $chartjs;



function fixDate($str) {
   if($str){
   list ($date['year'],$date['month'],$date['day']) = split("-",$str);
   $date['month'] = $date['month'] -1; //stupid javascript starting on 0 for jan
   return $date;
   }
   else return false;

}

//print_r($response);
?>
