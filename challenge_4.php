#!/usr/bin/env php
<?php

require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

function syntax(){
    print "Syntax:  challenge_4.php <container_name> <source dir>\n\n";
    exit;
}

if (count($argv)==3) {
    $container_name = $argv[1];
    $source_dir = $argv[2];
} else {
	syntax();
}
if (!is_dir($source_dir)) {
    print "Invalid source directory ".$source_dir.".  Exiting....\n";         
}
$containers=$cloudFiles->ListContainers();

while($container = $containers->next()) {
    if ($container->name == $container_name) { 
	    print $container_name."CloudFiles container already exists.  Exiting...\n";  
		exit;
    }
} 



try{
    $container=$cloudFiles->CreateContainer($container_name);
	$container->enableCdn();
	$container->uploadDirectory($source_dir);
} catch (\Guzzle\Http\Exception\BadResponseException $e) {
    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();

    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
} catch (\Guzzle\Common\Exception\InvalidArgumentException $e) {
    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();

    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
} 
	
$output="\n\nCDN URL:\n".$container->getCDN()->getCdnSslUri();



print $output;
?>