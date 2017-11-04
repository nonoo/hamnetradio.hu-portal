<?php
	function hp_db_open() {
		global $hp_db, $hp_error;

		// Telling mysqli to throw exceptions.
		mysqli_report(MYSQLI_REPORT_STRICT);

		try {
			$hp_db = new mysqli(HP_DB_HOST, HP_DB_USER, HP_DB_PASS, HP_DB_DBNAME);
		} catch(Exception $e) {
			$hp_error = "Hiba: nem lehet kapcsolódni a Hamnet RADIUS adatbázishoz!\n";
		}
	}

	function hp_db_close() {
		global $hp_db;

		if (isset($hp_db))
	  		$hp_db->close();
	}

	function hp_callsigndb_open() {
		global $hp_callsigndb, $hp_error;

		// Telling mysqli to throw exceptions.
		mysqli_report(MYSQLI_REPORT_STRICT);

		try {
			$hp_callsigndb = new mysqli(HP_CALLSIGNDB_HOST, HP_CALLSIGNDB_USER, HP_CALLSIGNDB_PASS, HP_CALLSIGNDB_DBNAME);
		} catch(Exception $e) {
			$hp_error = "Hiba: nem lehet kapcsolódni a hívójelkönyv adatbázishoz!\n";
		}
	}

	function hp_callsigndb_close() {
		global $hp_callsigndb;

		if (isset($hp_callsigndb))
	  		$hp_callsigndb->close();
	}

	function hp_db_get_hamdmr_password($callsign) {
		global $hp_db;

		if ($callsign == '')
			return '';

		$query_result = $hp_db->query('select `value` from `' . HP_DB_RADCHECK_TABLE . '` where `username`="' . $hp_db->escape_string($callsign) .
			'" and `attribute`="ClearText-Password"');
		$row = $query_result->fetch_array(MYSQLI_NUM);
		$passmd5 = md5($row[0]);
		$passmd5length = strlen($passmd5);
		$pass_sum = 0;
		for ($i = 0; $i < $passmd5length; $i++)
			$pass_sum += ord($passmd5[$i]);
		return $pass_sum;
	}

	function hp_db_is_valid_user($callsign) {
		global $hp_db;

		if ($callsign == '')
			return 0;

		$query_result = $hp_db->query('select `callsign` from `' . HP_DB_USERS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"');
		return ($query_result && $query_result->num_rows > 0);
	}

	function hp_db_is_valid_register_request_user($callsign) {
		global $hp_db;

		$query_result = $hp_db->query('select `callsign` from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"');
		return ($query_result && $query_result->num_rows > 0);
	}

	function hp_db_is_valid_email($email) {
		global $hp_db;

		$query_result = $hp_db->query('select `email` from `' . HP_DB_USERS_TABLE . '` where `email`="' . $hp_db->escape_string($email) . '"');
		return ($query_result && $query_result->num_rows > 0);
	}

	function hp_db_is_valid_auth($callsign, $passwordsum, $nonce) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return 0;

		$query_result = $hp_db->query('select `value` from `' . HP_DB_RADCHECK_TABLE . '` where `username`="' . $hp_db->escape_string($callsign)
			. '" and `attribute`="ClearText-Password"');
		if ($query_result && $query_result->num_rows > 0) {
			$row = $query_result->fetch_array(MYSQLI_NUM);
			if ($passwordsum == md5($row[0] . $nonce))
				return 1;
		} else
			return 0;
	}

	function hp_db_is_user_active($callsign) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return 0;

		$query_result = $hp_db->query('select `value` from `' . HP_DB_RADCHECK_TABLE . '` where `username`="' . $hp_db->escape_string($callsign)
			. '" and `attribute`="Auth-Type"');
		if ($query_result && $query_result->num_rows > 0) {
			$row = $query_result->fetch_array(MYSQLI_NUM);
			if ($row[0] != 'Reject')
				return 1;
			else
				return 0;
		} else
			return 1;
	}

	function hp_db_user_activate($callsign) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return 0;

		if ($hp_db->query('delete from `' . HP_DB_RADCHECK_TABLE . '` where `username`="' . $hp_db->escape_string($callsign)
			. '" and `attribute`="Auth-Type"') != 1)
				return 0;

		return 1;
	}

	function hp_db_user_deactivate($callsign) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return 0;

		if ($hp_db->query('replace into `' . HP_DB_RADCHECK_TABLE . '` (`username`, `attribute`, `op`, `value`) values ("' . $hp_db->escape_string($callsign)
			. '", "Auth-Type", ":=", "Reject")') != 1)
				return 0;

		return 1;
	}

	function hp_db_is_admin($callsign) {
		global $hp_db;

		$query_result = $hp_db->query('select `callsign` from `' . HP_DB_USERS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '" and `isadmin` = 1');
		return ($query_result && $query_result->num_rows > 0);
	}

	function hp_db_user_makeadmin($callsign) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return 0;

		if ($hp_db->query('update `' . HP_DB_USERS_TABLE . '` set `isadmin` = 1 where `callsign` = "' . $hp_db->escape_string($callsign) . '"') != 1)
			return 0;

		return 1;
	}

	function hp_db_user_unmakeadmin($callsign) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return 0;

		if ($hp_db->query('update `' . HP_DB_USERS_TABLE . '` set `isadmin` = 0 where `callsign` = "' . $hp_db->escape_string($callsign) . '"') != 1)
			return 0;

		return 1;
	}

	function hp_db_user_delete($callsign) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return 0;

		try {
			$hp_db->begin_transaction();

			$hp_db->query('delete from `' . HP_DB_USERS_TABLE . '` where `callsign` = "' . $hp_db->escape_string($callsign) . '"');
			$hp_db->query('delete from `' . HP_DB_RADCHECK_TABLE . '` where `username` = "' . $hp_db->escape_string($callsign) . '"');
			$hp_db->query('delete from `' . HP_DB_RADUSERGROUP_TABLE . '` where `username` = "' . $hp_db->escape_string($callsign) . '"');
			$hp_db->query('delete from `' . HP_DB_RADREPLY_TABLE . '` where `username` = "' . $hp_db->escape_string($callsign) . '"');

			$hp_db->commit();
		} catch (Exception $e) {
			$hp_db->rollback();
			return FALSE;
		}

		hp_db_licencephotos_cleanup();

		return 1;
	}

	function hp_db_get_email_for_callsign($callsign) {
		global $hp_db;

		$query_result = $hp_db->query('select `email` from `' . HP_DB_USERS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"');
		if ($query_result && $query_result->num_rows > 0) {
			$row = $query_result->fetch_array(MYSQLI_NUM);
			return $row[0];
		} else
			return FALSE;
	}

	function hp_db_get_email_for_register_request_user($callsign) {
		global $hp_db;

		$query_result = $hp_db->query('select `email`, `emailconfirmed` from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"');
		if ($query_result && $query_result->num_rows > 0) {
			$row = $query_result->fetch_array(MYSQLI_NUM);
			if ($row[1] > 0) // Only returning the email if it's confirmed.
				return $row[0];
		}
		return FALSE;
	}

	function hp_db_get_fixip_for_callsign($callsign) {
		global $hp_db;

		$query_result = $hp_db->query('select `value` from `' . HP_DB_RADREPLY_TABLE . '` where `username`="' . $hp_db->escape_string($callsign) . '" and `attribute`="Framed-IP-Address"');
		if ($query_result && $query_result->num_rows > 0) {
			$row = $query_result->fetch_array(MYSQLI_NUM);
			return $row[0];
		} else
			return FALSE;
	}

	function hp_db_get_internet_allowed_for_callsign($callsign) {
		global $hp_db;

		$query_result = $hp_db->query('select `value` from `' . HP_DB_RADREPLY_TABLE . '` where `username`="' . $hp_db->escape_string($callsign) . '" and `attribute`="Filter-Id" and `value`="deny-internet"');
		if ($query_result && $query_result->num_rows > 0)
			return FALSE;
		return TRUE;
	}

	function hp_db_email_change_requests_cleanup($callsign = '') {
		global $hp_db;

		if ($callsign != '')
			$hp_db->query('delete from `' . HP_DB_EMAIL_CHANGE_REQUESTS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"');

		$hp_db->query('delete from `' . HP_DB_EMAIL_CHANGE_REQUESTS_TABLE . '` where unix_timestamp()-unix_timestamp(`createdat`) > 3600');
	}

	function hp_db_add_new_change_email_request($callsign, $newemail) {
		global $hp_db;

		hp_db_email_change_requests_cleanup();

		// Getting a unique id.
		do {
			$id = hp_genpass(10);
			$query_result = $hp_db->query('select `id` from `' . HP_DB_EMAIL_CHANGE_REQUESTS_TABLE . '` where `id`="' . $hp_db->escape_string($id) . '"');
		} while ($query_result && $query_result->num_rows > 0);

		if ($hp_db->query('replace into `' . HP_DB_EMAIL_CHANGE_REQUESTS_TABLE . '` (`id`, `callsign`, `email`, `createdat`) values ("' . $hp_db->escape_string($id) .
			'", "' . $hp_db->escape_string($callsign) . '", "' . $hp_db->escape_string($newemail) . '", now())') != 1);

		return 1;
	}

	function hp_db_process_change_email_request($id) {
		global $hp_db;

		try {
			$hp_db->begin_transaction();

			hp_db_email_change_requests_cleanup();

			$query_result = $hp_db->query('select `email`,`callsign` from `' . HP_DB_EMAIL_CHANGE_REQUESTS_TABLE . '` where `id`="' . $hp_db->escape_string($id) . '"');
			if (!$query_result || $query_result->num_rows <= 0)
				return 0;

			$row = $query_result->fetch_array(MYSQLI_NUM);
			if (!$row || !isset($row[0]) || !hp_is_valid_email($row[0]))
				return 0;

			hp_db_email_change_requests_cleanup($row[1]);

			$hp_db->query('update `' . HP_DB_USERS_TABLE . '` set `email`="' . $hp_db->escape_string($row[0]) . '" where `callsign`="' . $hp_db->escape_string($row[1]) . '"');

			$hp_db->commit();
		} catch (Exception $e) {
			$hp_db->rollback();
			return FALSE;
		}
		return 1;
	}

	function hp_db_resetpass($callsign) {
		global $hp_db;

		$query_result = $hp_db->query('select `email` from `' . HP_DB_USERS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"');
		if (!$query_result || $query_result->num_rows <= 0)
			return FALSE;

		$row = $query_result->fetch_array(MYSQLI_NUM);
		if (!$row || !isset($row[0]) || !hp_is_valid_email($row[0]))
			return FALSE;

		$newpass = hp_genpass();
		if (!$hp_db->query('update `' . HP_DB_RADCHECK_TABLE . '` set `value`="' . $newpass . '" where `username`="' . $hp_db->escape_string($callsign) . '" and ' .
			'`attribute`="Cleartext-Password"'))
				return FALSE;

		$result = array();
		$result['email'] = $row[0];
		$result['newpass'] = $newpass;

		return $result;
	}

	function hp_db_register_requests_cleanup() {
		global $hp_db;

		$hp_db->query('delete from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where unix_timestamp()-unix_timestamp(`createdat`) > 3600 ' .
			'and `emailconfirmed` = 0');

		hp_db_licencephotos_cleanup();
	}

	function hp_db_licencephotos_cleanup() {
		global $hp_db;

		$files = scandir(HP_LICENCEPHOTOS_DIR);
		foreach ($files as $file) {
			if (!is_file(HP_LICENCEPHOTOS_DIR . $file))
				continue;

			$found = 0;
			$callsign = pathinfo($file, PATHINFO_FILENAME);

			$query_result = $hp_db->query('select `callsign` from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where `callsign` = "' . $hp_db->escape_string($callsign) . '"');
			if ($query_result && $query_result->num_rows > 0)
				$found = 1;
			$query_result = $hp_db->query('select `callsign` from `' . HP_DB_USERS_TABLE . '` where `callsign` = "' . $hp_db->escape_string($callsign) . '"');
			if ($query_result && $query_result->num_rows > 0)
				$found = 1;

			if (!$found)
				unlink(HP_LICENCEPHOTOS_DIR . $file);
		}
	}

	function hp_db_add_new_register_request($callsign, $email) {
		global $hp_db;

		// Getting a unique id.
		do {
			$id = hp_genpass(10);
			$query_result = $hp_db->query('select `id` from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where `id`="' . $hp_db->escape_string($id) . '"');
		} while ($query_result && $query_result->num_rows > 0);

		if ($hp_db->query('replace into `' . HP_DB_REGISTER_REQUESTS_TABLE . '` (`id`, `callsign`, `email`, `createdat`, `emailconfirmed`) values ("' . $hp_db->escape_string($id) .
			'", "' . $hp_db->escape_string($callsign) . '", "' . $hp_db->escape_string($email) . '", now(), 0)') != 1)
				return FALSE;

		hp_db_register_requests_cleanup();

		return $id;
	}

	function hp_db_process_register_confirm_email($id) {
		global $hp_db;

		hp_db_register_requests_cleanup();

		$query_result = $hp_db->query('select `callsign`, `email` from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where `id`="' . $id . '"');
		if (!$query_result || $query_result->num_rows <= 0)
			return FALSE;

		$row = $query_result->fetch_array(MYSQLI_ASSOC);

		if ($hp_db->query('update `' . HP_DB_REGISTER_REQUESTS_TABLE . '` set `emailconfirmed` = 1 where `id`="' . $id . '"'))
			return $row;

		return FALSE;
	}

	function hp_db_remove_register_request($callsign) {
		global $hp_db;

		hp_db_register_requests_cleanup();

		if ($hp_db->query('delete from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"') == 1) {
			@unlink(HP_LICENCEPHOTOS_DIR . hp_get_licencephotofn($callsign));
			return 1;
		}
		return 0;
	}

	function hp_db_approve_register_request($callsign) {
		global $hp_db;

		hp_db_register_requests_cleanup();

		$query_result = $hp_db->query('select `callsign`, `email` from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"');
		if (!$query_result || $query_result->num_rows <= 0)
			return 0;

		$row = $query_result->fetch_array(MYSQLI_NUM);

		$password = hp_genpass();

		try {
			$hp_db->begin_transaction();

			$hp_db->query('insert into `' . HP_DB_USERS_TABLE . '` (`callsign`, `email`, `isadmin`, `registeredat`) values ("' . $hp_db->escape_string($row[0]) .
				'", "' . $hp_db->escape_string($row[1]) . '", 0, now())');
			$hp_db->query('insert into `' . HP_DB_RADCHECK_TABLE . '` (`username`, `attribute`, `op`, `value`) values ("' . $hp_db->escape_string($row[0]) .
				'", "ClearText-Password", ":=", "' . $hp_db->escape_string($password) . '")');
			$hp_db->query('insert into `' . HP_DB_RADUSERGROUP_TABLE . '` (`username`, `groupname`) values ("' . $hp_db->escape_string($row[0]) .
				'", "hamnet-users")');
			$hp_db->query('delete from `' . HP_DB_REGISTER_REQUESTS_TABLE . '` where `callsign`="' . $hp_db->escape_string($callsign) . '"');
			$hp_db->query('insert into `' . HP_DB_RADREPLY_TABLE . '` (`username`, `attribute`, `op`, `value`) values ("' . $hp_db->escape_string($callsign) . '", "Filter-Id", "=", "deny-internet")');

			$hp_db->commit();
		} catch (Exception $e) {
			$hp_db->rollback();
			return FALSE;
		}
		return $password;
	}

	function hp_db_is_callsign_in_callsigndb($callsign) {
		global $hp_callsigndb;

		if (!hp_db_is_valid_user($callsign) && !hp_db_is_valid_register_request_user($callsign))
			return 0;

		$query_result = $hp_callsigndb->query('select `callsign` from `' . HP_CALLSIGNDB_TABLE . '` where `callsign` = "' . $hp_callsigndb->escape_string($callsign) . '"');
		if (!$query_result || $query_result->num_rows <= 0)
			return 0;
		return 1;
	}

	function hp_db_user_newpass($callsign) {
		global $hp_db;

		$email = hp_db_get_email_for_callsign($callsign);
		if (!hp_is_valid_email($email))
			return FALSE;

		$newpass = hp_genpass();
		if (!$hp_db->query('update `' . HP_DB_RADCHECK_TABLE . '` set `value`="' . $newpass . '" where `username`="' . $hp_db->escape_string($callsign) . '" and ' .
			'`attribute`="Cleartext-Password"'))
				return FALSE;

		return $newpass;
	}

	function hp_db_user_setfixip($callsign, $ip) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return FALSE;

		if ($ip != '') {
			if (!$hp_db->query('replace into `' . HP_DB_RADREPLY_TABLE . '` (`username`, `attribute`, `op`, `value`) values ("' . $hp_db->escape_string($callsign) . '", "Framed-IP-Address", "=", "' .
				$hp_db->escape_string($ip) . '")'))
					return FALSE;
		} else {
			if (!$hp_db->query('delete from `' . HP_DB_RADREPLY_TABLE . '` where `username`="' . $hp_db->escape_string($callsign) . '" and `attribute`="Framed-Ip-Address"'))
				return FALSE;
		}

		return TRUE;
	}

	function hp_db_user_set_internet_allowed($callsign, $value) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return FALSE;

		if ($value) {
			if (!$hp_db->query('delete from `' . HP_DB_RADREPLY_TABLE . '` where `username`="' . $hp_db->escape_string($callsign) . '" and `attribute`="Filter-Id"'))
				return FALSE;
		} else {
			if (!$hp_db->query('replace into `' . HP_DB_RADREPLY_TABLE . '` (`username`, `attribute`, `op`, `value`) values ("' . $hp_db->escape_string($callsign) . '", "Filter-Id", "=", "deny-internet")'))
					return FALSE;
		}

		return TRUE;
	}

	function hp_db_user_setcomment($callsign, $comment) {
		global $hp_db;

		if (!hp_db_is_valid_user($callsign))
			return FALSE;

		if (!$hp_db->query('update `' . HP_DB_USERS_TABLE . '` set `comment`="' . $hp_db->escape_string($comment) . '" where `callsign`="' . $hp_db->escape_string($callsign) . '"'))
			return FALSE;
		return TRUE;
	}
?>
