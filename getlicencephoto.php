<?php
	ini_set('display_errors', 'On');
    error_reporting(E_ALL);

	define('ABSPATH', dirname(__FILE__) . '/../');
	include('config.inc.php');
	include('functions-hp.inc.php');
	include('functions-hp-db.inc.php');

	session_name('hp');
	session_start();

	hp_db_open();

	if (!isset($_GET['callsign']) ||
		!hp_is_valid_callsign($_GET['callsign']) ||
		!hp_db_is_valid_user(@$_SESSION['hp-loggedin-callsign']) ||
		!hp_db_is_user_active(@$_SESSION['hp-loggedin-callsign']) ||
		!hp_db_is_admin(@$_SESSION['hp-loggedin-callsign'])) {
			die();
	}

	hp_db_close();

	$file = hp_get_licencephotofn($_GET['callsign']);
	switch (pathinfo($file, PATHINFO_EXTENSION)) {
		case 'jpg':
		case 'jpeg':
			header('Content-Type: image/jpeg');
			break;
		case 'png':
			header('Content-Type: image/png');
			break;
		default:
			die();
	}
	readfile("licences/" . $file);
?>
