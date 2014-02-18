#!/usr/bin/env php
<?php

require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

$server_name = "challenge7_";
$server_qty = 2;
 $output="";
 
for ($i = 1; $i<= $server_qty;$i++){
    $server[$i] = $compute->server();

    try {
        $response = $server[$i]->create(array(
            'name'     => $server_name.$i,
            'image'    => $compute->image($ini['Server_Info']['image']),
            'flavor'   => $compute->flavor($ini['Server_Info']['flavor']),
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
}

$loadBalancer = $loadBalancerService->loadBalancer();
$lbNodes = array();


for ($i = 1; $i<= $server_qty;$i++){

    $server[$i]->waitFor(ServerState::ACTIVE, 600, $server_callback);
	
	$lbNodes[$i] = $loadBalancer->node();
    $lbNodes[$i]->address = $server[$i]->addresses->private[0]->addr;
    $lbNodes[$i]->port = 80;
    $lbNodes[$i]->condition = 'ENABLED';

    $output.= "\n\nName: " . $server[$i]->name() . "\n";
    $output.= "ID: " . $server[$i]->id . "\n";
    $output.= "IP Address: " . $server[$i]->accessIPv4 . "\n";
    $output.= "Root Password: " . $server[$i]->adminPass . "\n";
}

$loadBalancer->addVirtualIp('PUBLIC');
$loadBalancer->create(array(
    'name' => $server_name.'lb',
    'port' => 80,
    'protocol' => 'HTTP',
    'nodes' => $lbNodes
));

$loadBalancer->waitFor(ServerState::ACTIVE, 600, $server_callback);

$errorPage=$loadBalancer->errorPage();
//print "Error Page:".$errorPage->content."\n\n"; // = '<html><head><Title>Challenge 7 Error Page</title></Head><body><h1>Challenge 7 Error Page</h1></body></html>';
try {


$errorPage->create(array(
    'content' => '<html><head><Title>Challenge 7 Error Page</title></Head><body><h1>Challenge 7 Error Page</h1></body></html>'
));
} catch (\Guzzle\Http\Exception\BadResponseException $e) {

         // No! Something failed. Let's find out:

        $responseBody = (string) $e->getResponse()->getBody();
        $statusCode   = $e->getResponse()->getStatusCode();
        $headers      = $e->getResponse()->getHeaderLines();

        echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
    }
//print "Error Page:".$errorPage->content."\n\n";

	
    $output.= "\n\nLoad Balancer\nName: " . $loadBalancer->name() . "\n";
    $output.= "ID: " . $loadBalancer->id . "\n";
  
  $vips = $loadBalancer->virtualIpList();
    foreach ($vips as $vip) {
        $output.= "IP: " . $vip->address . "\n";}
    
	


print $output;
?>