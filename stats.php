<?php

class MyDB extends SQLite3
{
		function __construct() {
				$this->open('stats_db');
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

if(array_key_exists("HTTP_SID", $_SERVER) == false) {
	exit("YA BITCH");
}

date_default_timezone_set('Europe/Paris');

/* get type from URL */
$typeOfStats = htmlspecialchars($_GET["type"]);

$db = new MyDB();

/* Create tables if they do not exists */
$db->exec('CREATE TABLE IF NOT EXISTS launcher (date_time TEXT NOT NULL, value_of INTEGER)');
$db->exec('CREATE TABLE IF NOT EXISTS website (date_time TEXT NOT NULL, value_of INTEGER)');
$db->exec('CREATE TABLE IF NOT EXISTS vulcain (date_time TEXT NOT NULL, value_of INTEGER)');
$db->exec('CREATE TABLE IF NOT EXISTS phantom (date_time TEXT NOT NULL, value_of INTEGER)');
$db->exec('CREATE TABLE IF NOT EXISTS trinity (date_time TEXT NOT NULL, value_of INTEGER)');

/* Verify header, so randoms cant spam thorugh an internet browser */

/* Current day (TODO: add per hour stats later) */
$currentDay = date("d-m-Y H");

/* check stats per type */
switch($typeOfStats) {

	case "launcher":
		if(doesEntryExist($db, $typeOfStats, 0, $currentDay) == false) {
			$db->exec('INSERT INTO '.$typeOfStats.' VALUES("'.$currentDay.'", "1")');
		} else {
			$s = $db->prepare('UPDATE '.$typeOfStats.' SET value_of = value_of + 1 WHERE date_time = :dat');
			$s->bindValue(":dat", $currentDay);
			$s->execute();
		}
		break;

	case "website":
		if(doesEntryExist($db, $typeOfStats, 0, $currentDay) == false) {
			$db->exec('INSERT INTO '.$typeOfStats.' VALUES("'.$currentDay.'", "1")');
		} else {
			$s = $db->prepare('UPDATE '.$typeOfStats.' SET value_of = value_of + 1 WHERE date_time = :dat');
			$s->bindValue(":dat", $currentDay);
			$s->execute();
		}
		break;

	case "vulcain":
		if(doesEntryExist($db, $typeOfStats, 0, $currentDay) == false) {
			$db->exec('INSERT INTO '.$typeOfStats.' VALUES("'.$currentDay.'", "1")');
		} else {
			$s = $db->prepare('UPDATE '.$typeOfStats.' SET value_of = value_of + 1 WHERE date_time = :dat');
			$s->bindValue(":dat", $currentDay);
			$s->execute();
		}
		break;

	case "phantom":
		if(doesEntryExist($db, $typeOfStats, 0, $currentDay) == false) {
			$db->exec('INSERT INTO '.$typeOfStats.' VALUES("'.$currentDay.'", "1")');
		} else {
			$s = $db->prepare('UPDATE '.$typeOfStats.' SET value_of = value_of + 1 WHERE date_time = :dat');
			$s->bindValue(":dat", $currentDay);
			$s->execute();
		}
		break;

	case "trinity":
		if(doesEntryExist($db, $typeOfStats, 0, $currentDay) == false) {
			$db->exec('INSERT INTO '.$typeOfStats.' VALUES("'.$currentDay.'", "1")');
		} else {
			$s = $db->prepare('UPDATE '.$typeOfStats.' SET value_of = value_of + 1 WHERE date_time = :dat');
			$s->bindValue(":dat", $currentDay);
			$s->execute();
		}
		break;

	default:
		exit("Unsupported type..");
}

echo "<br/>".$typeOfStats;

?>
