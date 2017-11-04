<?php
	function action_handle_login() {
		global $_POST, $_SESSION, $hp_error;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		$passwordsum = hp_sanitize($_POST['password']);
		$nonce = hp_sanitize($_POST['nonce']);
		unset($_SESSION['hp-loggedin-callsign']);

		if (hp_db_is_valid_auth($callsign, $passwordsum, $nonce)) {
			// Logging in the user.
			$_SESSION['hp-loggedin-callsign'] = $callsign;
		} else
			$hp_error = 'Hibás hívójel vagy jelszó!';
	}

	function action_handle_logout() {
		global $_SESSION;

		unset($_SESSION['hp-loggedin-callsign']);
	}

	function action_handle_resetpass() {
		global $_POST, $hp_result, $hp_error;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (hp_is_valid_callsign($callsign) && ($result = hp_db_resetpass($callsign)) !== FALSE) {
			hp_sendmail($result['email'], 'új jelszavad', "Szia!\n\nItt az új Hamnet portál jelszavad: ${result['newpass']}\n\n" . HP_EMAILFOOTER);
			$hp_result = 'Új jelszó elküldve a korábban regisztrált email-címre.';
		} else
			$hp_error = 'Hiba: nincs regisztrálva "' . $callsign . '" hívójelű felhasználó!';
	}

	function action_handle_changeemail() {
		global $_GET, $_POST, $_SESSION, $hp_error, $hp_result;

		if (isset($_GET['id'])) {
			$id = hp_sanitize($_GET['id']);
			if (hp_db_process_change_email_request($id))
				$hp_result = 'Email-cím változtatás sikeres!';
			else
				$hp_error = 'Hiba: érvénytelen email-cím változtatás azonosító!';
		} else {
			if (!hp_db_is_valid_user(@$_SESSION['hp-loggedin-callsign']))
				$hp_error = 'Nem vagy bejelentkezve!';
			else {
				$newemail = strtolower(hp_sanitize($_POST['newemail']));
				if (!hp_is_valid_email($newemail))
					$hp_error = 'Hibás email-cím!';
				else {
					if (!hp_db_add_new_change_email_request(@$_SESSION['hp-loggedin-callsign'], $newemail))
						$hp_error = 'Hiba: nem sikerült az email-cím változtatás!';
					else {
						hp_sendmail($newemail, 'email-cím változtatás', "Szia!\n\nNyisd meg ezt a linket az új email címed aktiválásához: " . HP_URL . "?action=changeemail&id=$id\n\n" . HP_EMAILFOOTER);
						$hp_result = 'A jelszó változtatás kérelem emailt elküldtük az újonnan megadott címre, amit a benne lévő linkre kattintva aktiválhatsz!';
					}
				}
			}
		}
	}

	function action_handle_register() {
		global $_POST, $_FILES, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel hibás, csak betűket és számokat tartalmazhat, valamint max. 10 karakter hosszú lehet!';
			return;
		}
		if (hp_db_is_valid_user($callsign)) {
			$hp_error = 'A megadott hívójel már létezik az adatbázisban!';
			return;
		}

		$email = strtolower(hp_sanitize($_POST['email']));
		if (!hp_is_valid_email($email)) {
			$hp_error = 'A megadott email-cím hibás!';
			return;
		}
		if (!isset($_FILES['licencephoto']['size']) || $_FILES['licencephoto']['size'] == 0) {
			$hp_error = 'Nem töltöttél fel képet a rádióamatőr igazolványodról!';
			return;
		}
		if ($_FILES['licencephoto']['size'] > 1000000) {
			$hp_error = 'Az igazolványról készült fotó mérete nem haladhatja meg az 1 megabyteot!';
			return;
		}
		$licencephoto_extension = strtolower(pathinfo(basename($_FILES['licencephoto']['name']), PATHINFO_EXTENSION));
		switch ($licencephoto_extension) {
			case 'jpg':
			case 'png':
			case 'jpeg':
				break;
			default:
				$hp_error = 'A fájl kiterjesztése nem engedélyezett!';
				return;
		}
		if (!getimagesize($_FILES['licencephoto']['tmp_name'])) {
			$hp_error = 'A fájl formátuma nem megfelelő!';
			return;
		}
		// This can happen if a registration request is overwritten with a previous one.
		// The newly uploaded file's extension can differ, so we remove the previous image.
		$currentlicencephoto = hp_get_licencephotofn($callsign);
		if ($currentlicencephoto)
			unlink(HP_LICENCEPHOTOS_DIR . $currentlicencephoto);
		$target_file = HP_LICENCEPHOTOS_DIR . "$callsign.$licencephoto_extension";
		if (!move_uploaded_file($_FILES['licencephoto']['tmp_name'], $target_file)) {
			$hp_error = 'Hiba a fájlfeltöltés során!';
			return;
		}
		$id = hp_db_add_new_register_request($callsign, $email);
		if ($id === FALSE)
			$hp_error = 'Hiba a regisztráció során!';
		else {
			hp_sendmail($email, 'email jóváhagyás', "Szia!\n\nNyisd meg ezt a linket az email címed jóváhagyásához: " . HP_URL . "?action=registerconfirmemail&id=$id\n\n" . HP_EMAILFOOTER);
			$hp_result = 'Az email-cím ellenőrző levelet elküldtük, kattints a benne található linkre!';
		}
	}

	function action_handle_register_confirm_email() {
		global $_GET, $hp_error, $hp_result;

		if (isset($_GET['id'])) {
			$id = hp_sanitize($_GET['id']);
			$result = hp_db_process_register_confirm_email($id);
			if ($result !== FALSE) {
				hp_sendmail(HP_ADMINEMAIL, 'új regisztráció', "Szia!\n\nEgy új felhasználó vár a regisztrációjának jóváhagyására a portálon.\n\nHívójel: ${result['callsign']}\nEmail: ${result['email']}\n\n" . HP_EMAILFOOTER);
				$hp_result = 'Email-cím jóváhagyva! A felhasználód aktiválásáról rövidesen emailt fogunk küldeni!';
				return;
			}
		}
		$hp_error = 'Hiba: érvénytelen email-cím jóváhagyás azonosító!';
	}

	function action_handle_admin_reg_reject() {
		global $_POST, $hp_error, $hp_result, $_SESSION;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign) || !hp_db_is_valid_register_request_user($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik a regisztrációra várók adatbázisában!';
			return;
		}
		$email = hp_db_get_email_for_register_request_user($callsign);
		if (hp_db_remove_register_request($callsign)) {
			if ($email != FALSE && strlen($email)) { // Email can be FALSE if it's unconfirmed.
				$reason = '';
				if (isset($_POST['reason']) && strlen($_POST['reason']))
					$reason = "\nIndok: " . $_POST['reason'] . "\n";
				hp_sendmail($email, 'regisztráció elutasítva', "Szia!\n\nSajnos elutasítottuk a regisztráció kérelmedet.\n$reason\n" . HP_EMAILFOOTER);
				hp_sendmail(HP_ADMINEMAIL, "$callsign regisztrációja elutasítva", "Szia!\n\n${_SESSION['hp-loggedin-callsign']} elutasította $callsign regisztrációját.\n$reason\n" . HP_EMAILFOOTER);
			}
			$hp_result = 'Regisztráció elutasítva.';
		} else
			$hp_error = 'Regisztráció elutasítása sikertelen!';
	}

	function action_handle_admin_reg_approve() {
		global $_POST, $hp_error, $hp_result, $_SESSION;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign) ||!hp_db_is_valid_register_request_user($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik a regisztrációra várók adatbázisában!';
			return;
		}
		$password = hp_db_approve_register_request($callsign);
		if ($password !== FALSE) {
			hp_sendmail(hp_db_get_email_for_callsign($callsign), 'regisztráció jóváhagyva', "Szia!\n\nRegisztrációdat aktiváltuk! Jelszavad: $password\n\n" . HP_EMAILFOOTER);
			hp_sendmail(HP_ADMINEMAIL, "$callsign regisztrációja jóváhagyva", "Szia!\n\n${_SESSION['hp-loggedin-callsign']} jóváhagyta $callsign regisztrációját.\n\n" . HP_EMAILFOOTER);
			$hp_result = 'Regisztráció jóváhagyva!';
		} else
			$hp_error = 'Regisztráció jóváhagyása sikertelen!';
	}

	function action_handle_admin_user_activate() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		if (hp_db_user_activate($callsign)) {
			hp_sendmail(hp_db_get_email_for_callsign($callsign), 'felhasználó aktiválás', "Szia!\n\n$callsign hívójelű felhasználód újra aktív!\n\n" . HP_EMAILFOOTER);
			$hp_result = 'Felhasználó aktiválva!';
		} else
			$hp_error = 'Felhasználó aktivációja sikertelen!';
	}

	function action_handle_admin_user_deactivate() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		if (hp_db_user_deactivate($callsign)) {
			$reason = '';
			if (isset($_POST['reason']) && strlen($_POST['reason']))
				$reason = "\nIndok: " . $_POST['reason'] . "\n";
			hp_sendmail(hp_db_get_email_for_callsign($callsign), 'felhasználó deaktiválás', "Szia!\n\n$callsign hívójelű felhasználód deaktiválva lett.\n$reason\n" . HP_EMAILFOOTER);
			$hp_result = 'Felhasználó deaktiválva!';
		} else
			$hp_error = 'Felhasználó deaktivációja sikertelen!';
	}

	function action_handle_admin_user_makeadmin() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		if (hp_db_user_makeadmin($callsign))
			$hp_result = 'Felhasználó adminná téve!';
		else
			$hp_error = 'Felhasználó adminná tétele sikertelen!';
	}

	function action_handle_admin_user_unmakeadmin() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		if (hp_db_user_unmakeadmin($callsign))
			$hp_result = 'Felhasználó admin joga elvéve!';
		else
			$hp_error = 'Felhasználó admin jogának elvétele sikertelen!';
	}

	function action_handle_admin_user_delete() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		if (hp_db_user_delete($callsign))
			$hp_result = 'Felhasználó törölve!';
		else
			$hp_error = 'Felhasználó törlése sikertelen!';
	}

	function action_handle_admin_user_newpass() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		$newpass = hp_db_user_newpass($callsign);
		if ($newpass !== FALSE) {
			hp_sendmail(hp_db_get_email_for_callsign($callsign), 'új jelszó', "Szia!\n\nÚj jelszavad: $newpass\n\n" . HP_EMAILFOOTER);
			$hp_result = 'Új jelszó elküldve a felhasználónak!';
		} else
			$hp_error = 'Új jelszó küldése sikertelen!';
	}

	function action_handle_admin_user_setfixip() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		$ip = hp_sanitize($_POST['ip']);
		if (!hp_is_valid_ip($ip))
			$ip = '';

		if (hp_db_user_setfixip($callsign, $ip) !== FALSE) {
			$ip = hp_db_get_fixip_for_callsign($callsign);
			if ($ip == '') {
				$hp_result = 'Felhasználó fix IP-je törölve!';
				hp_sendmail(HP_ADMINEMAIL, "$callsign fix IP törlés", "Szia!\n\n${_SESSION['hp-loggedin-callsign']} törölte $callsign fix IP címét.\n\n" . HP_EMAILFOOTER);
			} else {
				$hp_result = "Fix IP ($ip) beállítva a felhasználónak!";
				hp_sendmail(HP_ADMINEMAIL, "$callsign fix IP beállítás", "Szia!\n\n${_SESSION['hp-loggedin-callsign']} beállította $callsign fix IP címét a következőre: $ip\n\n" . HP_EMAILFOOTER);
			}
		} else
			$hp_error = 'Fix IP beállítása sikertelen!';
	}

	function action_handle_admin_user_setcomment() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		$comment = hp_sanitize($_POST['comment']);

		if (hp_db_user_setcomment($callsign, $comment) !== FALSE) {
			$hp_result = 'Felhasználóhoz tartozó megjegyzés elmentve!';
			hp_sendmail(HP_ADMINEMAIL, "$callsign megjegyzés módosítás", "Szia!\n\n${_SESSION['hp-loggedin-callsign']} módosította $callsign felhasználóhoz tartozó megjegyzést a következőre:\n\n$comment\n\n" . HP_EMAILFOOTER);
		} else
			$hp_error = 'Felhasználóhoz tartozó megjegyzés mentése sikertelen!';
	}

	function action_handle_admin_user_allow_internet() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		if (hp_db_user_set_internet_allowed($callsign, 1))
			$hp_result = 'Felhasználó internet elérése engedélyezve!';
		else
			$hp_error = 'Felhasználó internet elérésének engedélyezése sikertelen!';
	}

	function action_handle_admin_user_deny_internet() {
		global $_POST, $hp_error, $hp_result;

		$callsign = strtolower(hp_sanitize($_POST['callsign']));
		if (!hp_is_valid_callsign($callsign)) {
			$hp_error = 'A megadott hívójel nem létezik az adatbázisban!';
			return;
		}
		if (hp_db_user_set_internet_allowed($callsign, 0))
			$hp_result = 'Felhasználó internet elérése letiltva!';
		else
			$hp_error = 'Felhasználó internet elérésének letiltása sikertelen!';
	}
?>
