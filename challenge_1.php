#!/usr/bin/env php
<?php

require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

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