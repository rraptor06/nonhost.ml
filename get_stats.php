<?php

class MyDB extends SQLite3
{
		function __construct() {
			$this->open('stats_db', SQLITE3_OPEN_READONLY);
		}
}

function doesEntryExist($database, $table, $column_num, $data) {
	$db_data = $database->query('SELECT * FROM '.$table);
	while($x = $db_data->fetchArray(SQLITE3_NUM)) {
		if(strcmp($x[$column_num], $data) == 0) {
			return true;
		}
	}
	return false;
}

/* get type from URL */
$typeOfStats = htmlspecialchars($_GET["type"]);
$typeOfTime = htmlspecialchars($_GET["from"]);

date_default_timezone_set('Europe/Paris');

$db = new MyDB();
$currentDay = date("d-m-Y");

$period = new DatePeriod(
	new DateTime($typeOfTime),
	new DateInterval('P1D'),
	new DateTime(date("d-m-Y", time() + (60 * 60)))
);

$dArray = [];
foreach ($period as $key => $value) {
	for ($i=0; $i < 24; $i++) { 
		array_push($dArray, $value->format('d-m-Y')." ".sprintf("%02d", $i));
	}
}
list($aH) = sscanf(date("H"), "%02d");
for ($j=0; $j <= $aH; $j++) {
	array_push($dArray, date("d-m-Y")." ".sprintf("%02d", $j));
}
$dataPt = [];

foreach($dArray as $d) {
	$s = $db->prepare('SELECT value_of FROM '.$typeOfStats.' WHERE date_time = :dat');
	$s->bindValue(":dat", $d);
	$q = $s->execute();
	while($x = $q->fetchArray(SQLITE3_NUM)) {
		$td = DateTime::createFromFormat('d-m-Y H', $d);
		array_push($dataPt, array("x" => $td->getTimestamp()*1000, "y" => $x[0]));
	}
	
}

?>

<!DOCTYPE HTML>
<html>
<head>
<script>
window.onload = function () {
 
var chart = new CanvasJS.Chart("chartContainer", {
	title:{
		text: "<?php echo "Users for ".$typeOfStats ?>"
	},
	axisY: {
		title: "User Count",
	},
	data: [{
		type: "splineArea",
		color: "#6599FF",
		markerSize: 5,
		xValueFormatString: "DD-MM-YYYY HH:mm",
		xValueType: "dateTime",
		dataPoints: <?php echo json_encode($dataPt, JSON_NUMERIC_CHECK); ?>
	}]
});
 
chart.render();
 
}
</script>
</head>
<body>
<div id="chartContainer" style="height: 50%; width: 100%;"></div>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
</body>
</html>                              
