<?php

class UserCenterLogin extends PluggableAuth {
	public function authenticate(&$id, &$username, &$realname, &$email, &$errorMessage) {
		if (!$username && !$email) {
			return false;
		}
		var_dump('authenticate', $id, $username, $realname, $email, $errorMessage);
		die('debug');
	}
	
	public function saveExtraAttributes($id) {
		var_dump('saveExtraAttributes', $id);
		die('debug');
	}
	
	public function deauthenticate(User &$user) {
		var_dump('deauthenticate', $user);
		die('debug');
	}
}
