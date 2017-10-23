<?php

use MediaWiki\Session\CookieSessionProvider;
use MediaWiki\Session\SessionInfo;
use MediaWiki\Session\UserInfo;

class UserCenterSession extends CookieSessionProvider {
	public function __construct(array $params = []) {
		$params['priority'] = SessionInfo::MAX_PRIORITY;
		parent::__construct($params);
	}
	
	private function createUser(array $user) {
		$ret = new User();
		$ret->setName(ucfirst($user['username']));
		$ret->setRealName($user['username']);
		if (isset($user['email'])) {
			$ret->setEmail($user['email']);
			$ret->confirmEmail();
		}
		if (isset($user['profile'])) {
			$profile = &$user['profile'];
			if (isset($profile['nickname'])) {
				$ret->setRealName($user['nickname']);
			}
		}
		$status = $ret->addToDatabase();
		if ($status->isGood()) {
			MWDebug::log('login: new user created');
			
			return $ret;
		} else {
			MWDebug::log('login: new user create failed');
			echo $status->getHTML('new user create failed');
			exit(500);
		}
	}
	
	private function auth(array $user) {
		if (isset($user['email']) && $user['email']) {
			$query = ['user_email' => $user['email']];
		} else if (isset($user['username']) && $user['username']) {
			$query = ['user_name' => $user['username']];
		} else {
			MWDebug::log('login: no username or email??');
			
			return false;
		}
		
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('user', 'user_id', $query, __METHOD__)->fetchObject();
		if ($res) {
			$userObj      = new User();
			$userObj->mId = $res->user_id;
			if (!$userObj->loadFromId()) {
				throw new MWException('user query failed.');
			}
		} else {
			$userObj = $this->createUser($user);
		}
		
		return $userObj;
	}
	
	private function getCurrentToken(WebRequest $request) {
		$getName    = $this->config->get('UserCenterLogin_TokenCallback');
		$cookieName = $this->config->get('UserCenterLogin_TokenCookie');
		
		$GET = $request->getQueryValues();
		if (isset($GET[$getName]) && $GET[$getName]) {
			MWDebug::log('login: found token in get params');
			
			return $GET[$getName];
		} else if ($token = $request->getCookie($cookieName, '')) {
			MWDebug::log('login: found token in cookies');
			
			return $token;
		} else {
			MWDebug::log('login: no token "' . $cookieName . '" in cookie or "' . $getName . '" in get');
		}
		
		return null;
	}
	
	private function getCurrentUser(WebRequest $request) {
		$token = $this->getCurrentToken($request);
		$user  = \JENV\verifyToken($token, $error);
		if (!$user) {
			MWDebug::log('login: token wrong: ' . $error);
			
			return false;
		}
		MWDebug::log('login: token valid! ' . $user['id']);
		
		return $user;
	}
	
	// protected function getUserInfoFromCookies(WebRequest $request) {
	// 	MWDebug::log('!!getUserInfoFromCookies!!');
	// 	$ret = parent::getUserInfoFromCookies($request);
	// 	if (!$ret[2]) {
	// 		$ret[2] = $this->getCurrentToken($request);
	// 	}
	//
	// 	return $ret;
	// }
	
	public function provideSessionInfo(WebRequest $request) {
		MWDebug::log('!!provideSessionInfo!!');
		$session = parent::provideSessionInfo($request);
		if (defined('MW_NO_SESSION')) {
			wfDebugLog(__METHOD__, "No session for request: " . $_SERVER['REQUEST_URI']);
			MWDebug::log('??MW_NO_SESSION??');
			
			return $session;
		}
		if ($session === null || $session->getUserInfo()->isAnon()) {
			$user = $this->getCurrentUser($request);
			if (!$user) {
				return false;
			}
			
			$userObj = $this->auth($user);
			
			$info = [
					'userInfo'  => UserInfo::newFromUser($userObj, true),
					'provider'  => $this,
					'persisted' => true,
			];
			
			$session = new SessionInfo($this->priority, $info);
			
			MWDebug::log('login: new session info');
		}
		
		return $session;
	}
}
