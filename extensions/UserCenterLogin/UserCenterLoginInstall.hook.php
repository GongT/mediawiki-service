<?php

/*
		echo "<pre>";
		$dbr = wfGetDB(DB_MASTER);
		// user_email
		// user_name
		// user_id
		
		$res = $dbr->select('user',                                   // $table The table to query FROM (or array of tables)
							'*',            // $vars (columns of the table to SELECT)
							'',                              // $conds (The WHERE conditions)
							__METHOD__,                                   // $fname The current __METHOD__ (for performance tracking)
							[]        // $options = array()
		)->fetchObject();
*/

class UserCenterLoginInstall {
	static function onRegistration(...$args) {
		if (empty($GLOBALS['wgUserCenterLogin_RemoteUrl'])) {
			$base       = implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), -2));
			$remoteRoot = "//accounts." . $base;
		} else {
			$remoteRoot = $GLOBALS['wgUserCenterLogin_RemoteUrl'];
		}
		$GLOBALS['wgUserCenterLogin_RemoteUrl'] = rtrim($remoteRoot, '/');
	}
	
	static function modifyLoginURLs(array &$personal_urls, Title $title = null, SkinTemplate $skin = null) {
		if (isset($personal_urls['logout'])) {
			if (!$personal_urls['logout']['class']) {
				$personal_urls['logout']['class'] = '';
			}
			$personal_urls['logout']['class'] .= 'userCenterLogout';
		}
		
		if ($title->getNamespace() === NS_USER) {
			header('Location: https://www.' . \JENV\BASE_DOMAIN . '/accounts', true, 301);
			exit(0);
		}
		
		if (!empty($personal_urls['userpage'])) {
			$page          = &$personal_urls['userpage'];
			$page['class'] = '';
			$page['href']  = 'https://www.' . \JENV\BASE_DOMAIN . '/accounts/' . urlencode($page['text']);
		}
	}
	
	public static function autoLoginInit(OutputPage &$out, &$skin) {
		$out->addScriptFile($GLOBALS['wgUserCenterLogin_RemoteUrl'] . '/client-library/client.js');
		$out->addModules('ext.UserCenterSync');
		$out->addModules('ext.UserCenterLogin');
	}
	
	public static function onAuthChangeFormFields(
			array $requests, array $fieldInfo, array &$formDescriptor, $action) {
		MWDebug::log('!!onAuthChangeFormFields!!');
		unset($formDescriptor['rememberMe']);
		
		if (isset($_GET['returnto'])) {
			$redirect = Skin::makeUrl($_GET['returnto']);
		} else {
			$redirect = Skin::makeMainPageUrl();
		}
		$domain   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http';
		$domain   .= $_SERVER['HTTP_HOST'];
		$redirect = $domain . $redirect;
		
		$a = Html::element('a', [
				'target' => 'usercenter-register',
				'href'   => $GLOBALS['wgUserCenterLogin_RemoteUrl'] . '/login/register?' . http_build_query(['redirect' => $redirect]),
		], wfMessage('createaccount')->text());
		
		$formDescriptor['linkcontainer']['default'] = '<div style="text-align:right;">' . $a . '</div>';
	}
}
