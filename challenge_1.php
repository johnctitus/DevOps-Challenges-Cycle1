#!/usr/bin/env php
<?php

//Challenge 1: Write a script that builds a 512MB Cloud Server and returns the root password and IP address for the server.

require 'vendor/autoload.php';

use OpenCloud\Rackspace;


// Get credentials and configuration from ~/.rackspace_cloud_credentials
// $_SERVER['HOME'] does not exist in Windows, but it does in linux
// the equivalent in windows that i could see was USERPROFILE
$inifile = (array_key_exists('HOME', $_SERVER)?$_SERVER['HOME']: $_SERVER['USERPROFILE']). "/.rackspace_cloud_credentials";

$ini = parse_ini_file($inifile,TRUE);
if (!$ini) {
    printf("Unable to load .ini file [%s]\n", INIFILE);
    exit;
}

//print_r($ini);

$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $ini['Rackspace_Auth']);

$compute = $client->computeService('cloudServersOpenStack', $ini['Server_Info']['dc']);
//print_r($compute->imageList());

$images = $compute->imageList();
while ($image = $images->next()) { if (strpos($image->name, 'Ubuntu') !== false) { break; } }

$flavors = $compute->flavorList();
while ($flavor = $flavors->next()) { if (strpos($flavor->name, '512MB') !== false) { break; } }


use OpenCloud\Compute\Constants\Network;

$server = $compute->server();

try {
    $response = $server->create(array(
        'name'     => 'challenge_1',
        'image'    => $compute->image($ini['Server_Info']['image']),
        'flavor'   => $compute->flavor($ini['Server_Info']['flavor']),
        'networks' => array(
            $compute->network(Network::RAX_PUBLIC),
            $compute->network(Network::RAX_PRIVATE)
        )
    ));
} catch (\Guzzle\Http\Exception\BadResponseException $e) {

    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();

    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
}

use OpenCloud\Compute\Constants\ServerState;

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

$server->waitFor(ServerState::ACTIVE, 600, $callback);
print "\n\nName: " . $server->name() . "\n";
print "ID: " . $server->id . "\n";
print "IP Address: " . $server->accessIPv4 . "\n";
print "Root Password: " . $server->adminPass . "\n";
?>