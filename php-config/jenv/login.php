<?php

namespace JENV {
	
	const ACCOUNTS_HTTPS          = 'JENV::ACCOUNTS_HTTPS';
	const BASE_DOMAIN             = 'JENV::BASE_DOMAIN';
	const CONIG_DB_SERVER         = 'JENV::CONIG_DB_SERVER';
	const CONIG_DB_SUPER_PASSWORD = 'JENV::CONIG_DB_SUPER_PASSWORD';
	const CONIG_DB_PORT           = 'JENV::CONIG_DB_PORT';
	const PROXY_SERVER_NAME       = 'JENV::PROXY_SERVER_NAME';
	
	function continue_input() {
		$curr_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'];
		$curr_url .= '?' . http_build_query(['continue' => 'yes']);
		
		return '<a href="' . $curr_url . '">continue</a>';
	}
	
	function account_url($path) {
		$account_url = (ACCOUNTS_HTTPS ? "https" : "http") . '://accounts.' . BASE_DOMAIN;
		$redirect    = $account_url . $path . '?';
		$curr_url    = (isset($_SERVER['HTTPS']) ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'];
		$redirect    .= http_build_query(['redirect' => $curr_url]);
		
		return $redirect;
	}
	
	function goto_login() {
		header('Location: ' . account_url('/login'), true, 302);
		exit(0);
	}
	
	function linkTo($uri, $title) {
		return '<a href="' . account_url($uri) . '">' . $title . '</a>';
	}
	
	/* user login */
	function try_login($requireSuperAccess = false) {
		$account_url = (ACCOUNTS_HTTPS ? "https" : "http") . '://accounts.' . BASE_DOMAIN;
		
		if (isset($_SESSION['continue'])) {
			return false;
		}
		if (isset($_GET['continue'])) {
			$_SESSION['continue'] = true;
			
			return false;
		}
		if (isset($_GET['token'])) {
			$token = $_GET['token'];
			
			try {
				$curl = curl_init($account_url . '/api/get_current_user?' . http_build_query(['token' => $token]));
				curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
				$data = curl_exec($curl);
				$obj  = json_decode($data, true);
			} catch (\Exception $e) {
				die('login server down now.');
			}
			if (!isset($obj['status']) || $obj['status'] !== 0) {
				die('failed: ' . $obj['message'] . '.<br/>' . linkTo('/login', 'retry') . '.<br/>' . continue_input());
			}
			if ($requireSuperAccess && ($obj['user']['email'] !== 'admin@' . BASE_DOMAIN)) {
				die('no permission.<br/>' . linkTo('/login/logout', 'logout') . '.<br/>' . continue_input());
			}
			
			$_SESSION['uid'] = $token;
		}
		if (isset($_SESSION['uid'])) {
			return true;
		}
		
		goto_login();
		
		return false;
	}
	
	function session_try_login($requireSuperAccess) {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
		$ret = try_login($requireSuperAccess);
		session_write_close();
		
		return $ret;
	}
}
