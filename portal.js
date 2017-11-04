function hp_post(path, params, method) {
	method = method || "post"; // Set method to post by default if not specified.

	// The rest of this code assumes you are not using a library.
	// It can be made less wordy if you use one.
	var form = document.createElement("form");
	form.setAttribute("method", method);
	form.setAttribute("action", path);

	for (var key in params) {
	    if (params.hasOwnProperty(key)) {
			var hiddenField = document.createElement("input");
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", key);
			hiddenField.setAttribute("value", params[key]);

			form.appendChild(hiddenField);
		}
	}

	document.body.appendChild(form);
	form.submit();
}

function hp_login() {
	var password = $('#hp-form-login-password').val();
	var nonce = $('#hp-form-login-nonce').val();
	$('#hp-form-login-password').val(md5(password + nonce));
	$('#hp-form-login').submit();
	$('#hp-form-login-password').val(password);
}

function hp_logout() {
	hp_post('/portal/', { action: 'logout' });
}

function hp_admin_reg_approve(callsign) {
	if (window.confirm('Biztosan elfogadod a regisztrációt?'))
		hp_post('/portal/', { action: 'admin-reg-approve', callsign: callsign });
}

function hp_admin_reg_reject(callsign) {
	if (window.confirm('Biztosan vissza akarod utasítani a regisztrációt?')) {
		reason = window.prompt('Elutasítás indoka (nem kötelező megadni):');
		hp_post('/portal/', { action: 'admin-reg-reject', callsign: callsign, reason: reason });
	}
}

function hp_admin_user_activate(callsign) {
	if (window.confirm('Biztosan aktiválod a felhasználót?'))
		hp_post('/portal/', { action: 'admin-user-activate', callsign: callsign });
}

function hp_admin_user_deactivate(callsign) {
	if (window.confirm('Biztosan deaktiválod a felhasználót?')) {
		reason = window.prompt('Deaktiválás indoka (nem kötelező megadni):');
		hp_post('/portal/', { action: 'admin-user-deactivate', callsign: callsign, reason: reason });
	}
}

function hp_admin_user_changeinternetallowed(callsign, value) {
	if (value) {
		if (window.confirm('Biztosan engedélyezed az internet elérést a felhasználóhoz?'))
			hp_post('/portal/', { action: 'admin-user-allow-internet', callsign: callsign });
	} else {
		if (window.confirm('Biztosan tiltod az internet elérést a felhasználóhoz?'))
			hp_post('/portal/', { action: 'admin-user-deny-internet', callsign: callsign });
	}
}

function hp_admin_user_newpass(callsign) {
	if (window.confirm('Biztosan küldesz egy új generált jelszót emailben a felhasználónak?'))
		hp_post('/portal/', { action: 'admin-user-newpass', callsign: callsign });
}

function hp_admin_userlist_dropdown_toggle(callsign) {
	if (!$('#hp-userlist-dropdown-'+callsign).is(':visible')) {
		$('#hp-userlist-dropdown-'+callsign).css('display', 'block');
		$('#hp-userlist-dropdown-'+callsign+'-arrow').html('&#x25bc;');
		$('#hp-userlist-licencephoto-'+callsign).append('<img src="' + $('#hp-userlist-licencephoto-'+callsign).attr('licencephotourl') +'" />');
	} else {
		$('#hp-userlist-dropdown-'+callsign).css('display', 'none');
		$('#hp-userlist-dropdown-'+callsign+'-arrow').html('&#x25ba;');
		$('#hp-userlist-licencephoto-'+callsign).empty();
	}
}

function hp_admin_user_makeadmin(callsign) {
	if (window.confirm('Biztosan adminná teszed a felhasználót?'))
		hp_post('/portal/', { action: 'admin-user-makeadmin', callsign: callsign });
}

function hp_admin_user_unmakeadmin(callsign) {
	if (window.confirm('Biztosan elveszed az admin jogot a felhasználótól?'))
		hp_post('/portal/', { action: 'admin-user-unmakeadmin', callsign: callsign });
}

function hp_admin_user_delete(callsign) {
	if (window.confirm('Biztosan törölni akarod a felhasználót?'))
		hp_post('/portal/', { action: 'admin-user-delete', callsign: callsign });
}

function hp_admin_user_setfixip(callsign, ip) {
	hp_post('/portal/', { action: 'admin-user-setfixip', callsign: callsign, ip: ip });
}

function hp_admin_user_setcomment(callsign, comment) {
	hp_post('/portal/', { action: 'admin-user-setcomment', callsign: callsign, comment: comment });
}
