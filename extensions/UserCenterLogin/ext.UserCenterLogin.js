$(function () {
	var $login = $('#userloginForm input[name=wpName]');
	var $password = $('#userloginForm input[name=wpPassword]');
	var $button = $('#wpLoginAttempt')
		.removeAttr('disabled')
		.on('click', function (e) {
			if ($login.val().toLowerCase() !== 'admin') {
				doLogin($login.val(), $password.val());
				e.preventDefault();
			}
		});
	var orignalButton = $button.text();
	var $form = $([$login[0], $password[0], $button[0]]);
	
	$login.val('');
	
	function finish() {
		$form.removeAttr('disabled');
		$button.text(orignalButton);
	}
	
	function doLogin(username, password) {
		$button.text('...');
		$form.attr('disabled', 'disabled');
		userCenter.loginWithPassword({login: username, password: password}).then((data) => {
			// var token = userCenter.getUserToken();
			location.reload(true);
		}, function (e) {
			finish();
			mw.notify(mw.message('login failed').text() + e.message);
		});
	}
});
