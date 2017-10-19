<?php
/**
 * @author Jean-Lou Dupont
 * @package AddScriptCss
 */
//<source lang=php>
class AddScriptCss
{
	// constants.
	const thisName = 'AddScriptCss';
	const thisType = 'other'; 

	// error codes.
	const error_none     = 0;
	const error_uri      = 1;
	const error_bad_type = 2;
	const error_bad_pos  = 3;
	
	const baseScriptDirectory = '/scripts/';
	static $base = null; 

	function __construct( )
	{
		self::$base = self::baseScriptDirectory;
		
		// take on the global setting if found.
		global $bwScriptsDirectory;
		if (isset( $bwScriptsDirectory ))		
			self::$base = $bwScriptsDirectory;
	}

	public function tag_addscript( &$text, &$params, &$parser)
	{ return $this->process( $params );	}

	/**
		Parser Tag Magic Word for adding un-restricted content in the document's 'head'
	 */
	public function tag_addtohead( &$text, &$params, &$parser )
	{
		if (!$this->canProcess( $parser->mTitle) ) 
			return "<b>AddScriptCss:</b> ".wfMsg('badaccess');
		
		$this->addHeadScript( $text );		
	}
	
	public function mg_addscript( &$parser )
	{
		$params = $this->processArgList( func_get_args(), true );		
		return $this->process( $params );
	}
	private function setupParams( &$params )
	{
		$template = array(
			array( 'key' => 'src',  'index' => '0', 'default' => '' ),
			array( 'key' => 'type', 'index' => '1', 'default' => 'js' ),
			array( 'key' => 'pos',  'index' => '2', 'default' => 'body' ),
			#array( 'key' => '', 'index' => '', 'default' => '' ),
		);
		// ask initParams to strip off the parameters
		// which aren't registered in $template.
		$this->initParams( $params, $template, true );
	}
	private function normalizeParams( &$params )
	{
		// This function checks the validity of the following
		// parameters: 'type' and 'pos'
		extract( $params );
		
		$type=strtolower( $type );
		if ( ($type!='js') && ($type!='css') )
			return self::error_bad_type;

		$pos=strtolower( $pos );
		if ( ($pos!='head') && ($pos!='body') )
			return self::error_bad_pos;

		return self::error_none;		
	}
	private function process( &$params )
	{
		$this->setupParams( $params );

		$errCode = self::error_none;
		$r = $this->normalizeParams( $params );
		if ($r!=self::error_none) return $this->errMessage( $r );

		// src, type, pos
		extract( $params );
		
		$src = $this->cleanURI( $src, $type );
		if (!$this->checkURI( $src, $type ))
			return $this->errMessage( self::error_uri ); 


		global $wgScriptPath;
		$p = $wgScriptPath.self::$base.$src.'.'.$type;

		// Which type of script does the user want?
		switch( $type )
		{
			case 'css': $t = '<link href="'.$p.'" rel="stylesheet" type="text/css" />'; break;		
			default:
			case 'js':	$t = '<script src="'.$p.'" type="text/javascript"></script>';   break;
		}	

		// Where does the user want the script?
		switch( $pos )
		{
			case 'head':
				// For 'head' scripts, we need to embed a 'meta tag' in the text
				// This 'meta tag' will be saved in the parser cache waiting to be
				// look at by the hook 'OutputPageBeforeHTML'.
				$this->addHeadScript( $t );
				break;
			default:
			case 'body': 
				// For 'body' scripts, we need to intercept the processing flow
				// after the 'tidy' process in the parser and feed script tags there.
				// No need to encode them, they should be safe from the parser/parser cache.
				$this->addBodyScript( $t );
				break;	
		}
		// everything OK
		return null;
	}
	private function cleanURI( $uri )
	{
		return str_replace( array('/../', '../', '\\..\\',
									"..\\",'"','`','&','?',
									'<','>','.' ), "", $uri);
	}
	private function checkURI( $uri, $type )
	{
		// uri must resolved to a local file in the $base directory.
		global $IP;
		$spath = $IP.self::$base.$uri.'.'.$type;
		return file_exists( $spath );
	} 
	private function errMessage( $errCode )  // FIXME
	{
		$m = array(
			self::error_none     => 'no error',
			self::error_uri      => 'invalid URI',
			self::error_bad_type => 'invalid TYPE parameter',
			self::error_bad_pos  => 'invalid POS parameter',
		);
		return 'AddScriptCss: '.$m[ $errCode ];
	}

/****************************************************************************
Add scripts & stylesheets functionality.
This process must be done in two phases:
phase 1- encode information related to the required
         scripts & stylesheets in a 'meta form' in
		 the parser cache text.
phase 2- when the page is rendered, extract the meta information
         and include the information appropriately in the 'head' of the page.		  
*****************************************************************************/
	static $scriptsHeadList = array();
	static $scriptsBodyList = array();

	function addHeadScript( &$st )
	{
		if ( empty($st) ) return;
		
		// try to add scripts only once!
		if	(in_array($st, self::$scriptsHeadList))
			return;
		
		self::$scriptsHeadList[] = $st;						
		//$this->setupScriptsInjectionFeeder();					
	}
	private static function encodeHeadScriptTag( &$st )
	{
		return '<!-- META_SCRIPTS '.base64_encode($st).' -->';	
	}
	
	/** 
		This hook should *ALWAYS* be initialized if we are to have any chance
		of catching the 'head' scripts we need to add !! 
		
		This function sifts through 'meta tags' embedded in html comments
		and picks out scripts & stylesheet references that need to be put
		in the page's HEAD.

	*/
	function hOutputPageBeforeHTML( &$op, &$text )
	{
		static $scriptsAdded = false;
		
		// some hooks get called more than once...
		// In this case, since ExtensionClass provides a 
		// base class for numerous extensions, then it is very
		// likely this method will be called more than once;
		// so, we want to make sure we include the head scripts just once.
		if ($scriptsAdded) return true;
		$scriptsAdded = true;
		
		if (preg_match_all(
        	'/<!-- META_SCRIPTS ([0-9a-zA-Z\\+\\/]+=*) -->/m', 
        	$text, 
        	$matches)===false) return true;
			
    	$data = $matches[1];

	    foreach ($data AS $item) 
		{
	        $content = @base64_decode($item);
	        if ($content) $op->addScript( $content );
	    }
	    return true;
	}
	
	
	// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
	
	public function addBodyScript( &$st )
	{
		if ( empty($st) ) return;
		
		// try to add scripts only once!
		if	(!in_array($st, self::$scriptsBodyList)) 
		{
			self::$scriptsBodyList[] = $st;
			//$this->setupScriptsInjectionFeeder();
		}
	}
	
	// Scripts Feeding Logic
	// Required for both 'head' & 'body' injected scripts.
	function hParserAfterTidy( &$parser, &$text )
	// set the meta information in the parsed 'wikitext'.
	{
		// it seems that trying to protect
		// against multiple calls break more things
		// than help.
		
#		static $scriptsListed = false;
#		if ($scriptsListed) return true;
#		$scriptsListed = true;

		if (!empty(self::$scriptsBodyList))
			foreach(self::$scriptsBodyList as $sc)
				$text .= $sc; 

		if (!empty(self::$scriptsHeadList))
			foreach(self::$scriptsHeadList as $sc)
				$text .= $this->encodeHeadScriptTag( $sc ); 
	
		return true;
	}

	private function canProcess( &$obj )
	{
		if (!is_object( $obj ))
			return false; // paranoia
			
		if (is_a( $obj, 'Article'))
			$title = $obj->mTitle;
		elseif (is_a( $obj, 'Title'))
			$title = $obj;
		else
			return false;
		
		// check protection status
		if ( $title->isProtected( 'edit' ) ) return true;
		
		return false;
	}
	public function initParams( &$alist, &$templateElements, $removeNotInTemplate = true )
	{
		if ($removeNotInTemplate)
			foreach( $templateElements as $index => &$el )
				if ( !isset($alist[ $el['key'] ]) )
					unset( $alist[$el['key']] );
		
		foreach( $templateElements as $index => &$el )
			$alist[$el['key']] = $this->getParam( $alist, $el['key'], $el['index'], $el['default'] );
	}

	public function processArgList( $list, $getridoffirstparam=false )
	/*
	 * The resulting list contains:
	 * - The parameters extracted by 'key=value' whereby (key => value) entries in the list
	 * - The parameters extracted by 'index' whereby ( index = > value) entries in the list
	 */
	{
		if ($getridoffirstparam)   
			array_shift( $list );
			
		// the parser sometimes includes a boggie
		// null parameter. get rid of it.
		if (count($list) >0 )
			if (empty( $list[count($list)-1] ))
				unset( $list[count($list)-1] );
		
		$result = array();
		foreach ($list as $index => $el )
		{
			$t = explode("=", $el);
			if (!isset($t[1])) 
				continue;
			$result[ "{$t[0]}" ] = $t[1];
			unset( $list[$index] );
		}
		if (empty($result)) 
			return $list;
		return array_merge( $result, $list );	
	}
	public function getParam( &$alist, $key, $index, $default )
	/*
	 *  Gets a parameter by 'key' if present
	 *  or fallback on getting the value by 'index' and
	 *  ultimately fallback on default if both previous attempts fail.
	 */
	{
		if (array_key_exists($key, $alist) )
			return $alist[$key];
		elseif (array_key_exists($index, $alist) && $index!==null )
			return $alist[$index];
		else
			return $default;
	}

} // END CLASS DEFINITION
//</source>