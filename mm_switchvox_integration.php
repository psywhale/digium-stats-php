<?php
/*
 * mm_switchvox_integration
 * @author	Christopher Gray
 * @copyright portions Copyright (c) 2015 Metamedia Corporation www.metamedia.us
 * @license http://opensource.org/licenses/gpl-3.0.html GNU GENERAL PUBLIC LICENSE 3
 * @version	1.0
 * @package	mm_switchvox_integration
 * @desc	Abstraction layer between Digium Switchvox VOIP appliance and this web application
 * 
 * This improves and expands upon the limited sample classes supplied by Digium as it:
 * 	- does not require the HTTP pecl extension, so all code may live in the repo
 * 	- uses a singleton class to simplfy access
 * 
 * Requires PEAR libraries: XML_Serializer-0.20.2 (use most current version), HTTP_Request2
 *
 * Sample code:

// change the path to the path to this file
require_once "mm_functions/mm_switchvox_integration.php";

// replace the URL, USER, PASSWORD with values of your Switchvox
$_switchvox = mm_switchvox::getInstance("URL-OR-IP-OF-SWITCHVOX", "ADMIN-USER", "PASSWORD");

// get a list of all phones and their status
// http://developers.digium.com/switchvox/wiki/index.php/Switchvox.status.phones.getList
$requestParams = array( 'sort_field' => "extension",
 						'sort_order' => "DESC",
 						'items_per_page' => 1000  );
$result = $switchvox->request("switchvox.status.phones.getList", $requestParams);
print_r($result, "phones.getList");

 * References:
 * http://developers.digium.com/switchvox/wiki/index.php/WebService_overview
 * http://forums.digium.com/viewforum.php?f=29

 * 
 * v 0.1	4/3/15	cgray
 * 	- eliminates dependency on PECL HTTP extension
 * 	- fixes error when calling sw methods which have no parameters 
 * 	- extended from PHP sample code supplied by Digium, inproves error logging, &etc
 * 
*/

require_once 'XML/Serializer.php';
require_once 'XML/Unserializer.php';
require_once 'HTTP/Request2.php';
// if any of these fail, you need to install the PEAR libraries, as noted above

class mm_switchvox_request{
	private $hostname;
	private $username;
	private $password;
	private $uri;

	public function __construct ($in_hostname, $in_username, $in_password){
		$this->hostname = $in_hostname;
		$this->username = $in_username;
		$this->password = $in_password;

		$this->uri = "https://".$in_hostname."/xml";
	}

	public function send($in_method, $in_parameters = NULL){
		$postBody = $this->formatRequestXML($in_method, $in_parameters);

		// ref: https://pear.php.net/package/HTTP_Request2/docs/latest/HTTP_Request2/HTTP_Request2.html
		$request = new HTTP_Request2($this->uri);
		$request->setConfig("ssl_verify_peer",false);
		
		$request->setMethod($request::METHOD_POST);
		$request->setAuth($this->username, $this->password, $request::AUTH_DIGEST);
		$request->setHeader( array ( "Content-Type" => "application/xml" ) );
		$request->setBody($postBody);
		$response = $request->send();
		
		return new mm_switchvox_response($response);
	}

	private function formatRequestXML($in_method, $in_parameters){
		$xml_serializer_opts = array(
										'addDecl'				=> FALSE,
										'addDoctype'			=> FALSE,
										'indent'				=> '  ',
										'rootName'				=> 'request',
										'scalarAsAttributes'	=> TRUE,
										'mode'					=> 'simplexml'
									);

		$request_array = array	(
									'method'		=> $in_method,
									'parameters'	=> $in_parameters
								);

		$XMLSerializer = new XML_Serializer($xml_serializer_opts);
		$result = $XMLSerializer->serialize($request_array);

		return $XMLSerializer->getSerializedData();
	}
}


class mm_switchvox_response{
	private $apiStatus;
	private $apiErrors = array();
	private $apiResult = array();
	private $rawXML;
	
	const SV_RESPONSE_SUCCESS = 'success';
	const SV_RESPONSE_FAULT = 'fault';
	const SV_RESPONSE_FAILED = 'failed';

	public function __construct($_response){
		if ($_response->getStatus() != 200){
			$this->apiStatus = self::SV_RESPONSE_FAILED;
			array_push($this->apiErrors, 'HTTP Request failed (' . $_response->getStatus() . ')');
		}else{
			$this->rawXML = $_response->getBody();
			$responseObj   = $this->parseResponseXML($this->rawXML);

			if (is_null($responseObj)){
				$this->apiStatus = self::SV_RESPONSE_FAILED;
				array_push($this->apiErrors, 'Unable to parse XML');
			}else{
				if (isset($responseObj['errors'])){
					$this->apiErrors = $responseObj['errors'];
					$this->apiStatus = self::SV_RESPONSE_FAULT;
				}else{
					$this->apiResult = $responseObj['result'];
					$this->apiStatus = self::SV_RESPONSE_SUCCESS;
				}
			}
		}
	}

	public function getErrors(){
		return $this->apiErrors;
	}

	public function getResult(){
		return $this->apiResult;
	}

	public function getReponseStatus(){
		return $this->apiStatus;
	}

	public function getRawXMLResponse(){
		return $this->rawXML;
	}

	private function parseResponseXML($xml){
		$xmlOptions = array	(
								'complexType'		=> 'array',
								'parseAttributes'	=> TRUE
							);

		$unserializer = new XML_Unserializer();
		$success = $unserializer->unserialize($xml, FALSE, $xmlOptions);

		if ($success){
			$retval = ($unserializer->getUnserializedData());
			return $retval;
		}else{
			return null;
		}
	}
}


// ------------------------------------------------


/**
 * mm_switchvox()
 * @desc	singleton instance of mm_switchvox class, so that all switchvox interations are accessible from same persistent class instance
 * 			(set it up once, use it anywhere)
 * 
 * 	To use, $switchvox = mm_switchvox::getInstance();
 * 		anywhere in your code.
 * 	and NOT: $switchvox = new mm_switchvox();
 * 
 * 	The first time you call, you must pass the Switchvox connection parameters, ie.
 * 		$switchvox = mm_switchvox::getInstance($host, $user, $pass);
 * 	or these values may be preset by defining these GLOBALs:
 * 		MM_SWITCHVOX_HOST, MM_SWITCHVOX_USER, MM_SWITCHVOX_PASS
 * 
 * 
 * 
*/
class mm_switchvox{
	
	private static $__instance = NULL;
	
	private static $_request = NULL;
	private static $_response = NULL;
		
	/**
	 * getInstance()
	 * returns singleton instance of this class
	 * 
	 * All params are optional and may only be set on first connection
	 * @param	string	$host, if not passed attempts to use GLOBAL MM_SWITCHVOX_HOST
	 * @param	string	$user, if not passed attempts to use GLOBAL MM_SWITCHVOX_USER
	 * @param	string	$pass, if not passed attempts to use GLOBAL MM_SWITCHVOX_PASS
	 * @param	bool	(optional) debug trace to error_log?
	 * @return	obj singleton mm_switchvox
	 */
	public static function getInstance($host = NULL, $user = NULL, $pass = NULL, $debug = FALSE){
		if(self::$__instance == NULL){ // create instance
			
			$error = false;
			
			if( is_null($host) && defined('MM_SWITCHVOX_HOST') ){
				$host = MM_SWITCHVOX_HOST;
			}elseif(is_null($host)){
				$error[] = "host";
			}
			
			if( is_null($user) && defined('MM_SWITCHVOX_USER') ){
				$user = MM_SWITCHVOX_USER;
			}elseif(is_null($user)){
				$error[] = "user";
			}
			
			if( is_null($pass) && defined('MM_SWITCHVOX_PASS') ){
				$pass = MM_SWITCHVOX_PASS;
			}elseif(is_null($pass)){
				$error[] = "pass";
			}
			
			if(!$error){
				self::$__instance = new mm_switchvox($host, $user, $pass);
				self::$_request = new mm_switchvox_request($host, $user, $pass);
			}else{
				error_log( __CLASS__."->".__FUNCTION__."() : Connection parameters must be set when creating class instance.");
				if($debug){
					ob_start();
					debug_print_backtrace();
					error_log(ob_get_clean());
				}
				die;
			}
			
		}else{
			// only accept switchvox connection parameters at instance creation to avoid confusion if allowed to be dynamically set
			if(!is_null($host) || !is_null($user) || !is_null($pass) ){
				error_log( __CLASS__."->".__FUNCTION__."() : Connection parameters must be set when creating class instance." );
				if($debug){
					ob_start();
					debug_print_backtrace();
					error_log(ob_get_clean());
				}
				die;
			}
		}
		return self::$__instance;
	}
	
	/**
	* Protected constructor to prevent creating a new instance of the
	* *Singleton* via the `new` operator from outside of this class.
	*/
	protected function __construct()
	{
	}
	

	/**
	* Private clone method to prevent cloning of the instance of the
	* *Singleton* instance.
	*
	* @return void
	*/
	private function __clone()
	{
	}

	/**
	* Private unserialize method to prevent unserializing of the *Singleton*
	* instance.
	*
	* @return void
	*/
	private function __wakeup()
	{
	}
	
	/* request()
	 * @desc	performs a request
	 * @param	string	$method name from Switchvox API
	 * @param	array	(optional) of method parameters
	 * @param	bool	(optional) debug trace to error_log?
	 * @return	array of result values, or FALSE on error
	 */
	public function request($method, $parameters = NULL, $debug = FALSE){
		self::$_response = NULL;
		
		if( is_null(self::$_request) ){
			error_log( __CLASS__."->".__FUNCTION__.'() : Attempting request without establishing the class singleton instance, ie. $switchvox = mm_switchvox::getInstance();' );
			if($debug){
				ob_start();
				debug_print_backtrace();
				error_log(ob_get_clean());
			}
			die;
		}
		
		$_response = self::$_request->send($method, $parameters);
		self::$_response = $_response;
		
		// trap and report errors to error_log
		$ReponseStatus = $_response->getReponseStatus();
		if( $ReponseStatus !== $_response::SV_RESPONSE_SUCCESS ){
			$errors = $_response->getErrors();
			$err = array();
			foreach($errors as $error){
				$err[] = "Code:{$error['code']} : {$error['message']}";
			}
			$msg = implode(", ",$err);
			error_log( __CLASS__."->".__FUNCTION__."() : $ReponseStatus : $msg" );
			if($debug){
				ob_start();
				debug_print_backtrace();
				error_log(ob_get_clean());
			}
			return false;
		}
		return $_response->getResult();
	}
	
	/* getResponse()
	 * @desc	provides access to the response object
	 * @return	obj	mm_switchvox_response
	 */
	public function getResponse(){
		return self::$_response;
	}
	
}

?>
