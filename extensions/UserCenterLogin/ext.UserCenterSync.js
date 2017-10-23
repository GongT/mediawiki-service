$(function () {
	if (!window.userCenter) {
		mw.notify(mw.message('login service has gone, please contact us.').text());
	}
	var currentToken = userCenter.getUserToken();
	if (currentToken && mw.user.isAnon()) {
		if (!$('#wpLoginAttempt').length) { // already on login page
			var buttonHref = $('#pt-login').find('>a').attr('href');
			if (buttonHref) {
				location.href = buttonHref;
			} else {
				mw.notify(mw.message('login button has gone').text());
			}
		}
	}
	if (!currentToken && !mw.user.isAnon()) {
		var buttonHref = $('.userCenterLogout').attr('href');
		if (buttonHref) {
			location.href = buttonHref;
		} else {
			alert(mw.message('logout button has gone').text());
		}
	}
	
	userCenter.onChange(function () {
		if (currentToken !== userCenter.getUserToken()) {
			var $bar = $('#p-personal').hide();
			// var height = $bar.offset().top + $bar.outerHeight();
			$('<div>').addClass('mw-ui-button mw-ui-primary mw-ui-progressive').css({
				position: 'absolute',
				left: 0,
				width: '100%',
				top: 0,
				// height: height,
				zIndex: 10000,
				background: 'rgb(200, 200, 255)',
				padding: '5px 15px',
				textAlign: 'center',
				cursor: 'pointer'
			}).text(mw.message('login state changed, click here to reload page.').text()).appendTo('body').on('click', function () {
				location.reload();
			});
		}
	});
	
	$('.userCenterLogout').on('click', (e) => {
		userCenter.logout().then(function () {
			location.href = $('.userCenterLogout').attr('href');
		});
		e.preventDefault();
	});
});
