<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

// Get credentials and configuration from ~/.rackspace_cloud_credentials
// $_SERVER['HOME'] does not exist in Windows, but it does in linux
// the equivalent in windows that i could see was USERPROFILE
$inifile = (array_key_exists('HOME', $_SERVER)?$_SERVER['HOME']: $_SERVER['USERPROFILE']). "/.rackspace_cloud_credentials";

$ini = parse_ini_file($inifile,TRUE);
if (!$ini) {
    printf("Unable to load .ini file [%s]\n", INIFILE);
    exit;
}

$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $ini['Rackspace_Auth']);

$compute = $client->computeService('cloudServersOpenStack', $ini['Server_Info']['dc']);
$dns = $client->dnsService();
$cbs = $client->VolumeService();
$cloudFiles = $client->objectStoreService('cloudFiles', $ini['Server_Info']['dc']);

$loadBalancerService = $client->loadBalancerService('cloudLoadBalancers', $ini['Server_Info']['dc']);

$server_callback = function($server) {
    if (!empty($server->error)) {
        var_dump($server->error);
        exit;
    } else {
        echo sprintf(
            "Waiting on %s/%-12s %4s \n",
            $server->name(),
            $server->status(),
            isset($server->progress) ? $server->progress."%" : ""
        );
    }
};

?>