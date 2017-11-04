<?php
	// This file is included from wp-content/themes/hamnetradio/hamnet-portal.php

	ini_set('display_errors', 'On');
    error_reporting(E_ALL);

	include('config.inc.php');
	include('functions-hp-db.inc.php');
	include('functions-hp.inc.php');
	include('functions-action.inc.php');

	session_name('hp');
	session_start();

	get_header();
?>
		<link rel="stylesheet" type="text/css" href="style.css">

		<script type="text/javascript" src="<?php echo HP_URL; ?>md5.min.js"></script>
		<script type="text/javascript" src="<?php echo HP_URL; ?>portal.js"></script>

		<div id="primary">
			<div id="content" role="main">
				<div class="entry-content hamnet-portal">

<?php
	hp_db_open();

	if (isset($_GET['action']))
		$action = $_GET['action'];
	else
		$action = @$_POST['action'];

	switch ($action) {
		case 'login': action_handle_login(); break;
		case 'logout': action_handle_logout(); break;
		case 'resetpass': action_handle_resetpass(); break;
		case 'register': action_handle_register(); break;
		case 'registerconfirmemail': action_handle_register_confirm_email(); break;
		case 'changeemail': action_handle_changeemail(); break;
		default: break;
	}

	$validuser = hp_db_is_valid_user(@$_SESSION['hp-loggedin-callsign']);
	if ($validuser && !hp_db_is_user_active(@$_SESSION['hp-loggedin-callsign'])) {
		$hp_error = 'Nem aktív a felhasználód!';
		$validuser = 0;
	}

	if ($validuser && hp_db_is_admin(@$_SESSION['hp-loggedin-callsign'])) {
		hp_callsigndb_open();
		switch ($action) {
			case 'admin-reg-approve': action_handle_admin_reg_approve(); break;
			case 'admin-reg-reject': action_handle_admin_reg_reject(); break;
			case 'admin-user-activate': action_handle_admin_user_activate(); break;
			case 'admin-user-deactivate': action_handle_admin_user_deactivate(); break;
			case 'admin-user-makeadmin': action_handle_admin_user_makeadmin(); break;
			case 'admin-user-unmakeadmin': action_handle_admin_user_unmakeadmin(); break;
			case 'admin-user-delete': action_handle_admin_user_delete(); break;
			case 'admin-user-newpass': action_handle_admin_user_newpass(); break;
			case 'admin-user-setfixip': action_handle_admin_user_setfixip(); break;
			case 'admin-user-setcomment': action_handle_admin_user_setcomment(); break;
			case 'admin-user-allow-internet': action_handle_admin_user_allow_internet(); break;
			case 'admin-user-deny-internet': action_handle_admin_user_deny_internet(); break;
			default: break;
		}
	}

	if (isset($hp_error)) {
?>
					<div class="hp-error"><?php echo $hp_error; ?></div>
<?php
	}
	if (isset($hp_result)) {
?>
					<div class="hp-result"><?php echo $hp_result; ?></div>
<?php
	}

	if (!$validuser) {
		the_post();
		get_template_part('content', 'page');
?>
					<form action="<?php echo HP_URL; ?>" method="post" enctype="multipart/form-data">
						<input type="hidden" name="action" value="register" />
						<h2>Regisztráció</h2>
						<table>
							<tr><td width="300">Hívójel:</td>					<td><input type="text" name="callsign" /></td></tr>
							<tr><td>E-mail:</td>								<td><input type="text" name="email" /></td></tr>
							<tr><td>Fénykép a rádióamatőr igazolványról:</td>	<td><input type="file" name="licencephoto" /><br/><small><small>(a kép mérete max. 1mb lehet)</small></small></td></tr>
							<tr><td></td>										<td><input type="submit" value="Regisztráció" /></td></tr>
						</table>
					</form>

					<form action="<?php echo HP_URL; ?>" method="post" id="hp-form-login">
						<input type="hidden" name="action" value="login" />
						<input type="hidden" id="hp-form-login-nonce" name="nonce" value="<?php echo md5(date('Y-m-d H:i:s') . rand(0, 1000000) . 'eljen a sor'); ?>" />
						<h2>Belépés</h2>
						<table>
							<tr><td width="300">Hívójel:</td>		<td><input type="text" name="callsign" /></td></tr>
							<tr><td>Jelszó:</td>					<td><input id="hp-form-login-password" type="password" name="password" /></td></tr>
							<tr><td></td>							<td><input type="button" value="Belépés" onclick="hp_login()" /></td></tr>
						</table>
					</form>

					<form action="<?php echo HP_URL; ?>" method="post">
						<input type="hidden" name="action" value="resetpass" />
						<h2>Elfelejtett jelszó</h2>
						<table>
							<tr><td width="300">Hívójel:</td>		<td><input type="text" name="callsign" /></td></tr>
							<tr><td></td>							<td><input type="submit" value="Új jelszó küldése" /></td></tr>
						</table>
					</form>
<?php
	} else {
?>
					<div id="hp-statusline">
						Bejelentkezve mint <span id="hp-statusline-callsign"><?php echo $_SESSION['hp-loggedin-callsign']; ?></span> (email: <?php echo hp_db_get_email_for_callsign($_SESSION['hp-loggedin-callsign']); ?>).
						<input id="hp-button-logout" type="button" onclick="hp_logout()" value="Kilépés" />
					</div>

					<div id="hp-hamdmr-password">
						ham-dmr.hu jelszavad: <?php echo hp_db_get_hamdmr_password($_SESSION['hp-loggedin-callsign']); ?>
					</div>
<?php
	$fixip = hp_db_get_fixip_for_callsign($_SESSION['hp-loggedin-callsign']);
	if ($fixip != '') {
?>
					<div id="hp-fixip">
						Felhasználódhoz tartozó fix IP cím: <strong><?php echo $fixip; ?></strong>
					</div>
<?php
	}
?>
					<div id="hp-changeemail">
						<form action="<?php echo HP_URL; ?>" method="post">
							<input type="hidden" name="action" value="changeemail" />
							<h2>Email-cím megváltoztatása</h2>
							<table>
								<tr><td width="300">Új email-cím:</td>	<td><input type="text" name="newemail" /></td></tr>
								<tr><td></td>							<td><input type="submit" value="Email-cím megváltoztatása" /></td></tr>
							</table>
						</form>
					</div>
<?php
	if (hp_db_is_admin(@$_SESSION['hp-loggedin-callsign']))
		include('admin.inc.php');
	else {
?>
					Egyelőre itt nem lehet semmi mást csinálni. Ha be tudtál lépni, akkor a Hamnet elérése is menni fog az elérési pontokon keresztül.
<?php
		}
	}

	hp_db_close();
	hp_callsigndb_close();
?>
				</div><!-- #entry-content -->
			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
