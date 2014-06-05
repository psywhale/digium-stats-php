<?php

require_once 'XML/Serializer.php'; 
require_once('SwitchvoxResponse.php');

class SwitchvoxRequest
{
	private $hostname;
	private $username;
	private $password;
	private $uri;

	public function __construct ($in_hostname, $in_username, $in_password)
	{
		$this->hostname = $in_hostname;
		$this->username = $in_username;
		$this->password = $in_password;

		$this->uri = "https://".$in_hostname."/xml";
	}

	public function send($in_method, $in_parameters)
	{
		$postBody = $this->formatRequestXML($in_method, $in_parameters);

		$request_options = array	(
										'httpauthtype'	=> HTTP_AUTH_DIGEST,
										'httpauth'		=> $this->username.":".$this->password,	
										'headers'		=> array ( "Content-Type" => "application/xml" )
									);

		$request = new HttpRequest($this->uri, HTTP_METH_POST, $request_options);
		$request->setRawPostData($postBody);
		$response = $request->send();
		return new SwitchvoxResponse($request);
	}

	private function formatRequestXML($in_method, $in_parameters)
	{
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

?>
