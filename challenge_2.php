#!/usr/bin/env php
<?php

require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

function syntax(){
    print "Syntax:  challenge_2.php <server_name> <server qty: 1 - 3>\n\n";
    exit;
}
if (count($argv)==3) {
    $server_name = $argv[1];
    $server_qty = $argv[2];
    if ($server_qty < 1 || $server_qty > 3) { print "\n\nServer_qty invalid. Value must be between 1 - 3\n\n";  syntax(); }
} else {
    print "\n\nSyntax Invalid. Value must be between 1 - 3\n\n";
	syntax();
}
$keypairs=$compute->listKeypairs();
$keypair=null;
while($kp = $keypairs->next()) {
   if ($kp->name() == $server_name) { $keypair=$kp; }
} 
if ( $keypair == null) {
    $keypair = $compute->keypair();

    try{
        $keypair->create(array(
           'name' => $server_name
        ));
    } catch (\Guzzle\Http\Exception\BadResponseException $e) {
        // No! Something failed. Let's find out:

        $responseBody = (string) $e->getResponse()->getBody();
        $statusCode   = $e->getResponse()->getStatusCode();
        $headers      = $e->getResponse()->getHeaderLines();

        echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
    } 
}
$output="\n\nPublic Key:\n".$keypair->getPublicKey();

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
			'keypair'  => $server_name	
			
        ));
    } catch (\Guzzle\Http\Exception\BadResponseException $e) {

         // No! Something failed. Let's find out:

        $responseBody = (string) $e->getResponse()->getBody();
        $statusCode   = $e->getResponse()->getStatusCode();
        $headers      = $e->getResponse()->getHeaderLines();

        echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
    }
}

for ($i = 1; $i<= $server_qty;$i++){
    $callback = function($server) {
        if (!empty($server->error)) {
            var_dump($server->error);
            exit;
        } else {
            echo sprintf(
                "Waiting on %s/%-12s %4s%% \n",
                $server->name(),
                $server->status(),
                isset($server->progress) ? $server->progress : 0
            );
        }
    };

    $server[$i]->waitFor(ServerState::ACTIVE, 600, $callback);

    $output.= "\n\nName: " . $server[$i]->name() . "\n";
    $output.= "ID: " . $server[$i]->id . "\n";
    $output.= "IP Address: " . $server[$i]->accessIPv4 . "\n";
    $output.= "Root Password: " . $server[$i]->adminPass . "\n";
}

print $output;
?>