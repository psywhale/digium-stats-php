<?php

require_once 'XML/Unserializer.php';

define('SV_RESPONSE_SUCCESS', 'success');
define('SV_RESPONSE_FAULT',   'fault'  );
define('SV_RESPONSE_FAILED',  'failed' );

class SwitchvoxResponse
{
	private $apiStatus;
	private $apiErrors = array();
	private $apiResult = array();
	private $rawXML;

	public function __construct($httpRequest)
	{
		if ($httpRequest->getResponseCode() != 200)
        {
			$this->apiStatus = SV_RESPONSE_FAILED;
			array_push($this->apiErrors, 'HTTP Request failed (' . $httpRequest->getResponseCode() . ')');
		}
        else
        {
            $this->rawXML = $httpRequest->getResponseBody();
			$responseObj   = $this->parseResponseXML($this->rawXML);

			if (is_null($responseObj))
			{
				$this->apiStatus = SV_RESPONSE_FAILED;
				array_push($this->apiErrors, 'Unable to parse XML');
			}
			else
			{
				if (isset($responseObj['errors']))
				{
					$this->apiErrors = $responseObj['errors'];
					$this->apiStatus = SV_RESPONSE_FAULT;
				}
				else
				{
					$this->apiResult = $responseObj['result'];
					$this->apiStatus = SV_RESPONSE_SUCCESS;
				}
			}
        }
	}

	public function getErrors()
	{
		return $this->apiErrors;
	}

	public function getResult()
	{
		return $this->apiResult;
	}

	public function getReponseStatus()
	{
		return $this->apiStatus;
	}

	public function getRawXMLResponse()
	{
		return $this->rawXML;
	}

	private function parseResponseXML($xml)
	{
		$xmlOptions = array	(
								'complexType'		=> 'array',
								'parseAttributes'	=> TRUE
							);

		$unserializer = new XML_Unserializer();
		$result = $unserializer->unserialize($xml, FALSE, $xmlOptions);

		if (!PEAR::isError($result))
		{
			$retval = ($unserializer->getUnserializedData());
			return $retval;
		}
		else
		{
			return null;
		}
	}
}

?>
