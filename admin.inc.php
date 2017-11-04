<?php
	if (!hp_db_is_admin(@$_SESSION['hp-loggedin-callsign']))
		die();
?>

	<style>
		.hamnet-portal .hp-admin .hp-callsign { font-weight: bold; }
	</style>

	<div class="hp-admin">
		<h2>Jóváhagyásra váró felhasználók</h2>

<?php
	$query_result = $hp_db->query('select * from `' . HP_DB_REGISTER_REQUESTS_TABLE . '`');
	if (!$query_result || $query_result->num_rows == 0) {
?>
		Nincs jóváhagyásra váró felhasználó.
<?php
	} else {
?>
		<ul>
<?php
		while ($row = $query_result->fetch_array(MYSQLI_ASSOC)) {
?>
			<li>
				Hívójel: <span class="hp-callsign"><?php echo $row['callsign']; ?></span><br/>
				Hívójelkönyvben szerepel: <?php echo (hp_db_is_callsign_in_callsigndb($row['callsign']) ? 'igen' : '<span style="color: red;">NEM</span>'); ?><br/>
				Email-cím: <a href="mailto:<?php echo $row['email']; ?>"><?php echo $row['email']; ?></a>
<?php 		if ($row['emailconfirmed'] > 0) { ?>
					<span class="hp-email-confirmed">(IGAZOLVA)</span><br/>
<?php		} else { ?>
					<span class="hp-email-unconfirmed">(IGAZOLÁSRA VÁR)</span><br/>
<?php		} ?>
				Jelentkezés ideje: <?php echo $row['createdat']; ?><br/>
				Engedély fotója:
<?php
			$licencephotofn = hp_get_licencephotofn($row['callsign']);
			if ($licencephotofn === FALSE) {
?>
					<span class="hp-error">Hiba: nincs fotó a szerveren!</span><br/>
<?php
			} else {
?>
					<a href="<?php echo HP_URL; ?>licences/<?php echo $licencephotofn; ?>" data-rel="lightbox-0"><img src="<?php echo HP_URL; ?>licences/<?php echo $licencephotofn; ?>" /></a><br/>
<?php
			}
?>
				<input type="button" value="Jóváhagyás" onclick="hp_admin_reg_approve('<?php echo $row['callsign']; ?>')" />
				<input type="button" value="Visszautasítás" onclick="hp_admin_reg_reject('<?php echo $row['callsign']; ?>')" />
			</li>
<?php
		}
	}
?>
		</ul>

		<div class="hp-spacer"></div>

		<h2>Regisztrált felhasználók</h2>

<?php
	$query_result = $hp_db->query('select * from `' . HP_DB_USERS_TABLE . '`');
	if (!$query_result || $query_result->num_rows == 0) {
?>
		Nincsenek regisztrált felhasználók.
<?php
	} else {
?>
		<ul>
<?php
		while ($row = $query_result->fetch_array(MYSQLI_ASSOC)) {
			$isactive = hp_db_is_user_active($row['callsign']);
			$is_in_callsignbook = hp_db_is_callsign_in_callsigndb($row['callsign']);
			$fixip = hp_db_get_fixip_for_callsign($row['callsign']);
			$is_internet_allowed = hp_db_get_internet_allowed_for_callsign($row['callsign']);
?>
			<li class="hp-userlist-dropdown-li">
				<div>
					Hívójel: <a href="javascript:hp_admin_userlist_dropdown_toggle('<?php echo $row['callsign']; ?>');"><span class="hp-callsign"><?php echo $row['callsign']; ?></span> <span id="hp-userlist-dropdown-<?php echo $row['callsign']; ?>-arrow">&#x25ba;</span></a>
					<span style="color: red;"><?php echo (!$is_in_callsignbook ? '(NINCS A HÍVÓJELKÖNYVBEN)' : ''); ?></span>
				</div>
				<div class="hp-userlist-dropdown-content" id="hp-userlist-dropdown-<?php echo $row['callsign']; ?>">
					Hívójelkönyvben szerepel: <?php echo ($is_in_callsignbook ? 'igen' : '<span style="color: red;">NEM</span>'); ?><br/>
					Email-cím: <a href="mailto:<?php echo $row['email']; ?>"><?php echo $row['email']; ?></a><br/>
					Fix IP: <input type="text" class="hp-userlist-dropdown-fixip" id="hp-userlist-dropdown-fixip-<?php echo $row['callsign']; ?>" value="<?php echo $fixip; ?>" /> <input type="button" value="Mentés" onclick="hp_admin_user_setfixip('<?php echo $row['callsign']; ?>', $('#hp-userlist-dropdown-fixip-<?php echo $row['callsign']; ?>').val())" /><br/>
					Internet elérés: <?php echo ($is_internet_allowed ? 'igen' : 'nem'); ?> <input type="button" value="Módosít" onclick="hp_admin_user_changeinternetallowed('<?php echo $row['callsign']; ?>', <?php echo ($is_internet_allowed ? 0 : 1); ?>)" /><br/>
					Megjegyzés a felhasználóhoz:<br/>
					<textarea id="hp-userlist-dropdown-comment-<?php echo $row['callsign']; ?>"><?php echo $row['comment']; ?></textarea> <input type="button" value="Mentés" onclick="hp_admin_user_setcomment('<?php echo $row['callsign']; ?>', $('#hp-userlist-dropdown-comment-<?php echo $row['callsign']; ?>').val())" /><br/>
					Regisztráció ideje: <?php echo $row['registeredat']; ?><br/>
					Aktív: <?php echo ($isactive > 0 ? 'igen' : 'nem'); ?><br/>
					Admin: <?php echo ($row['isadmin'] > 0 ? 'igen' : 'nem'); ?><br/>
					Engedély fotója:
<?php
			$licencephotofn = hp_get_licencephotofn($row['callsign']);
			if ($licencephotofn === FALSE) {
?>
						<span class="hp-error">Hiba: nincs fotó a szerveren!</span><br/>
<?php
			} else {
?>
						<a href="<?php echo HP_URL; ?>licences/<?php echo $licencephotofn; ?>" data-rel="lightbox-0" id="hp-userlist-licencephoto-<?php echo $row['callsign']; ?>" licencephotourl="<?php echo HP_URL; ?>licences/<?php echo $licencephotofn; ?>"></a><br/>
<?php
			}

			if ($isactive) {
?>
					<input type="button" value="Deaktiválás" onclick="hp_admin_user_deactivate('<?php echo $row['callsign']; ?>')" />
<?php		} else { ?>
					<input type="button" value="Aktiválás" onclick="hp_admin_user_activate('<?php echo $row['callsign']; ?>')" />
<?php
			}

			if ($row['isadmin'] > 0) {
?>
					<input type="button" value="Admin jog elvétele" onclick="hp_admin_user_unmakeadmin('<?php echo $row['callsign']; ?>')" />
<?php		} else { ?>
					<input type="button" value="Adminná tétel" onclick="hp_admin_user_makeadmin('<?php echo $row['callsign']; ?>')" />
<?php		} ?>
					<input type="button" value="Új jelszó küldése emailben" onclick="hp_admin_user_newpass('<?php echo $row['callsign']; ?>')" />
					<input type="button" value="Törlés" onclick="hp_admin_user_delete('<?php echo $row['callsign']; ?>')" />
				</div>
			</li>
<?php
		}
	}
?>
		</ul>
	</div> <!-- div:hp-admin -->
