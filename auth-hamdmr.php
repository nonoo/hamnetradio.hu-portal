<?php
	// Returns 1 if callsign and given MD5 hash of password matches the ham-dmr password's
	// MD5 hash.

	ini_set('display_errors', 'On');
    error_reporting(E_ALL);

	define('ABSPATH', dirname(__FILE__) . '/../');
	include('config.inc.php');
	include('functions-hp-db.inc.php');
	include('functions-hp.inc.php');

	$callsign = @$_GET['callsign'];
	$passmd5 = @$_GET['password'];

	hp_db_open();
	if (!isset($callsign) ||
		!hp_is_valid_callsign($callsign) ||
		!hp_db_is_valid_user($callsign) ||
		!hp_db_is_user_active($callsign)) {
			echo '0';
			hp_db_close();
			return;
	}

	if ($passmd5 != md5(hp_db_get_hamdmr_password($callsign))) {
		echo '0';
		hp_db_close();
		return;
	}
	hp_db_close();
	echo '1';
?>
