#!/usr/bin/env php
<?php
/*
Challenge 8: Write a script that creates a Cloud Performance 1GB Server. The 
script should then add a DNS "A" record for that server. Create a Cloud 
Monitoring Check and Alarm for a ping of the public IP address on your new 
server. Return to the STDOUT the IP Address, FQDN, and Monitoring Entity ID of 
your new server. Choose your language and SDK! 



*/
require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

//read server name and domain from command line
function syntax(){
    print "Syntax:  challenge_8.php <server_name> <domain_name>\n\n";
    exit;
}

if (count($argv)==3) {
    $server_name = $argv[1];
    $domain_name = $argv[2];
} else {
    print "\n\nInvalid number of arguments. \n\n";
	syntax();
}
$output="";

//connect to DNS domain or exit on fail
$dlist = $dns->DomainList();

$domain=null;

while($d = $dlist->next()) { 
    if ($d->name()==$domain_name) { 
	    $domain=$d;
	    break;
	}
}
	
if ($domain==null){ 
    print $domain_name." domain not found.  Exiting...\n";
	exit;
}

//create cloud server
$server = $compute->server();

try {
    $response = $server->create(array(
        'name'     => $server_name,
        'image'    => $compute->image($ini['Server_Info']['image']),
        'flavor'   => $compute->flavor('performance1-1'),
        'networks' => array(
            $compute->network(Network::RAX_PUBLIC),
            $compute->network(Network::RAX_PRIVATE)),
    ));
} catch (\Guzzle\Http\Exception\BadResponseException $e) {

     // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();

    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
}

$server->waitFor(ServerState::ACTIVE, 600, $server_callback);
//add DNS record

$record=$domain->record();
$record->type = 'A';
$record->name = $server_name.".".$domain->name();
$record->ttl = '300';
$record->data = $server->accessIPv4;
$record->comment = 'DevOps Challenge 8';

try {
    $record->create();
} catch (\Guzzle\Http\Exception\BadResponseException $e) {

    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();
    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
}

//create monitor check
/*
$ips=array();

for($i=0;$i<count($server->addresses['public']);$i++) {
	$ips['access_ip'.$i.'_v'.$server->address['public'][$i]->version]=$server->address['public'][$i]->address;
	$ips['public'.$i.'_v'.$server->address['public'][$i]->version]=$server->address['public'][$i]->address;
}
for($i=0;$i<count($server->addresses['private']);$i++) {
		$ips['private'.$i.'_v'.$server->address['private'][$i]->version]=$server->address['private'][$i]->address;
}
*/

try {
	$entity = $monitorService->getEntity();
	$response=$entity->create(array(
		'label' => $server->name,
		'agent_id' => $server->id,
		//'ip_addresses' => $ips
		'ip_addresses' => array('default' => '127.0.0.4'),
    	
	));
	//print_r($entity->getHeaderLines());
  //  $entity->waitFor(ServerState::ACTIVE, 600, $server_callback);
} catch (\Guzzle\Http\Exception\BadResponseException $e) {

    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();
    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
}	
	$check = $entity->getCheck();
	
	$params = array(
		'type'   => 'remote.ping',
		'details' => array(
			'count'    => '5',
        ),
		'monitoring_zones_poll' => array('mzlon', 'mzdfw','mzord'),
		'period' => '60',
		'timeout' => '30',
		'target_alias' => 'default',
		'label'  => 'Challenge 8 ping Check'
	);

// You can do a test to see what would happen 
// if a Check is launched with these params

	//$r = $check->checkParams($params);
try {
	$r=$entity->testNewCheckParams($params);
	
	echo "Results: ".$r->available."\n"; // Was it available?
echo "Average: ".$r->average."\n"; // When was it executed?
echo "Minimum: ".$r->minimum."\n"; // When was it executed?
echo "Maximum: ".$r->maximum."\n"; // When was it executed?

	$response=$check->create($params);
	
} catch (\Guzzle\Http\Exception\BadResponseException $e) {

    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();
    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
}



//create alarm

//output FQDN, IP, and monitor ID

$output.= "\nFQDN: " . $record->name . "\n";
$output.= "IP: " . $record->data . "\n";
$output.= "Monitor ID: " . $check->getId() . "\n";

print $output;
?>