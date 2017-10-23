$(function () {
	if (!window.userCenter) {
		mw.notify('服务器错误，目前无法登录账号，请联系我们。');
	}
	var currentToken = userCenter.getUserToken();
	if (currentToken && mw.user.isAnon()) {
		if (!$('#wpLoginAttempt').length) { // already on login page
			var buttonHref = $('#pt-login').find('>a').attr('href');
			if (buttonHref) {
				location.href = buttonHref;
			} else {
				mw.notify('糟糕！登录按钮没有了！');
			}
		}
	}
	if (!currentToken && !mw.user.isAnon()) {
		var buttonHref = $('.userCenterLogout').attr('href');
		if (buttonHref) {
			location.href = buttonHref;
		} else {
			alert('糟糕！登出按钮没有了！');
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
				background: ''
			}).text('login state changed, click here to reload.').appendTo('body').on('click', function () {
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
