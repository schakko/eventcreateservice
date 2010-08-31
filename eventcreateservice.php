<?php
/**
 * eventcreateservice.php
 * Routes any type of event/log entry over SOAP to eventcreatef.exe
 * @author Christopher Klein <christopher[dot]klein[at]ecw[dot]de>
 * 
 * Requirements:
 *  * Latest Zend Framework Release 1.10.5 or later - use Full package for Zend_Soap_AutoDiscover class
 *  * Windows System
 * 
 * Installation:
 *  * Copy this file to Webserver root (htdocs / Inetpub / whatever) of Windows system
 *  * Change the define's() in config.php
 * 
 * Execute:
 *  * Call http[s]://<your server>/eventcreateservice.php?wsdl for retrieving WSDL
 *  * Call http[s]://<your server>/eventcreateservice.php?bash for retrieving Bash script
 */

// include configuration file
include_once("./config.php");

// include SOAP classes from 1.10.5
require("Zend/Soap/AutoDiscover.php");
require("Zend/Soap/Server.php");

/**
 * SOAP response
 */
class CreateEventResponse {
	/**
     * Status code
	 * @var int
	 */
	public $value = null;
	
	/**
     * Output from executed command line
	 * @var string
	 */
	public $output = null;
	
	/**
     * executed command line
	 * @var string
	 */
	public $exec = null;
}

/**
 * class EventCreateService has only one method for fetching log events
 */
class EventCreateService
{
	const ERROR_SEVERITY = 1;
	const ERROR_NO_TEXT = 2;
	const ERROR_NO_LOGNAME = 3;
	
	/**
	 * Parameter 'severity' can only be 'WARNING', 'ERROR', 'INFORMATION' or 'SUCCESS'.
	 * Parameter 'logname' and 'text' must not be null.
	 * 
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param int
	 * @return CreateEventResponse
	 */
	public function create(string $text, string $logname, string $severity, string $source, int $id) {
		$id = (int)$id;
		$severity = strtoupper($severity);
		
		// test input data
		if (strlen($logname) == 0) {
			throw new SoapFault(ERROR_NO_LOGNAME, "Logname must be given");
		}
		
		if (!(('WARNING' == $severity) || ('ERROR' == $severity) || ('INFORMATION' == $severity) || ('SUCCESS' == $severity))) {
			throw new SoapFault(ERROR_SEVERITY, "Severity can only be 'WARNING', 'ERROR', 'INFORMATION' or 'SUCCESS' and not '" . $severity ."'");
		}
		
		if (strlen($text) == 0) {
			throw new SoapFault(ERROR_NO_TEXT, "Text can not be empty");
		}

		// escape arguments
		$logname = escapeshellarg($logname);
		$source = escapeshellarg($source);
		
		// make MD5 hash for temporary data
		$md5 = md5(time() . $text);
		$fileName = dirname(__FILE__) . "/" . $md5 . ".data";
		
		// create new response object
		$r = new CreateEventResponse();
		file_put_contents($fileName, $text);
		$arrOutput = array();
		
		// execute command line
		$exec = EVENTCREATEF_FILE . " -user:" . USERNAME . " -pass:" . PASSWORD . " -domain:" . DOMAIN ." -s " . REMOTE_SYSTEM . " -t " . $severity . " -id " . $id . " -so " . $source . " -l " . $logname ." -f:" . $fileName;
		exec($exec, $arrOutput, $r->value);
		$r->output = implode("\n", $arrOutput);
		$r->exec = $exec;
		
		// remove temporay data file
		unlink($fileName);
		
		return $r;
	}
}

if (isset($_GET['wsdl'])) {
	$ad = new Zend_Soap_AutoDiscover();
	$ad->setClass('EventCreateService');
	$ad->handle();
}
elseif (isset($_GET['bash'])) {
	header("Content-Disposition: attachment; filename=\"log_over_soap.sh\"");
	$SERVICE_URL = SERVICE_URL;
	$out = <<<EOT
#!/bin/sh
# Install: chmod +x this file
# Author: Christopher Klein <christopher[dot]klein[at]ecw[dot]de
# Website: http://wap.ecw.de / http://www.ecw.de
TEXT=\$1
LOGNAME=\$2
SEVERITY=\$3
SOURCE=\$4
ID=\$5
SERVICE_URL={$SERVICE_URL}

if [ \$# -ne "5" ]; then
	echo "Usage \$0 <text:string> <logname:string> <severity:enum[ERROR|WARNING|INFORMATION|SUCCESS]> <source:string> <id:id>"
	exit
fi

DATA=$(cat <<EOF
<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:event="\$SERVICE_URL">
   <soapenv:Header/>
   <soapenv:Body>
      <event:create soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <text xsi:type="xsd:string">\$TEXT</text>
         <severity xsi:type="xsd:string">\$SEVERITY</severity>
         <source xsi:type="xsd:string">\$SOURCE</source>
		 <logname xsi:type="xsd:string">\$LOGNAME</logname>
         <id xsi:type="xsd:int">\$ID</id>
      </event:create>
   </soapenv:Body> 
</soapenv:Envelope>
EOF
)

curl -H "Content-Type: text/xml; charset=utf-8" -H "SOAPAction: create" -d "\$DATA" \$SERVICE_URL
EOT;

	$out = str_replace("\r", "", $out);
	die($out);
} else {
	$ad = new Zend_Soap_Server(SERVICE_URL . '?wsdl=true', array());
	$ad->setClass('EventCreateService');
	$ad->handle();
}
