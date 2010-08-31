<?php
// absoulute path to eventcreatef.exe
define("EVENTCREATEF_FILE", "eventcreatef.exe");
// IP or DNS name of your Windows event-log server. Use 'localhost' if eventcreateservice.php and central log are on the same machine
define("REMOTE_SYSTEM", "remote-eventlog-server");
// your Active Directory domain
define("DOMAIN", "YOUR_DOMAIN");
// your Active Directory user
define("USERNAME", "YOUR_USERNAME");
// your Active Directry password
define("PASSWORD", "YOUR_PASSWORD");
// turn debug on: SOAP returns executed command line
define("DEBUG", false);
// If you use https, write "https"
define("EVENTCREATESERVICE_PROTOCOL", "http");
// Service URL to eventcreateservice.php - should not be changed
define("SERVICE_URL", EVENTCREATESERVICE_PROTOCOL . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);
?>