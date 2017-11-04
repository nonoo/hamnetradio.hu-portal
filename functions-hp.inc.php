<?php
	function hp_sanitize($s) {
		return strip_tags(stripslashes(trim($s)));
	}

	function hp_is_valid_callsign($callsign) {
		return (!preg_match('/[^a-z0-9\-]/', $callsign) && strlen($callsign) >= 3 && strlen($callsign) <= 15);
	}

	function hp_is_valid_password($password) {
		return (!preg_match('/[^a-z0-9]/i', $password) && strlen($password) >= 5 && strlen($password) <= 253);
	}

	function hp_is_valid_email($email) {
		return (!preg_match('/[^a-z\+_@\.\-0-9]/i', $email) && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 50);
	}

	function hp_is_valid_ip($ip) {
		return (!preg_match('/[^0-9\.]$/i', $ip) && filter_var($ip, FILTER_VALIDATE_IP));
	}

	function hp_genpass($length = 8) {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array();
		$alphabet_length = strlen($alphabet) - 1;

		for ($i = 0; $i < $length; $i++) {
		    $n = rand(0, $alphabet_length);
		    $pass[] = $alphabet[$n];
		}

		return implode($pass);
	}

    function hp_sendmail($to, $subject, $msg, $headers = '') {
		$header = "From: hamnetradio.hu <info@hamnetradio.hu>\nReply-To: info@hamnetradio.hu\nMIME-Version: 1.0";
		if (strstr($headers, 'Content-type:') == false)
			$header .= "\nContent-type: text/plain; charset=UTF-8";
		if ($headers)
			$header .= "\n$headers";

		mail($to, '=?UTF-8?B?' . base64_encode("[hamnetradio.hu portál] $subject") .'?=', $msg, $header);
	}

	function hp_get_licencephotofn($callsign) {
		$files = scandir(HP_LICENCEPHOTOS_DIR);
		foreach ($files as $file) {
			if (pathinfo($file, PATHINFO_FILENAME) == $callsign)
				return $file;
		}
		return FALSE;
	}
?>
