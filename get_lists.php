#!/usr/bin/env php
<?php

require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

$imageList=$compute->imageList();
$flavorList=$compute->flavorList();
$serverList=$compute->serverList();
$checkTypes = $monitorService->getCheckTypes();

print "\n\nImage Types:\n";
while($image = $imageList->next()) { 
    print $image->id() . " -> " . $image->name()."\n";
}
print "\n\n";

print "\n\nFlavor Types:\n";
	
while($flavor = $flavorList->next()) { 
    print $flavor->id() . " -> " . $flavor->name()."\n";
}

print "\n\nCheck Types:\n";

foreach ($checkTypes as $checkType) {
   echo $checkType->getId()."\n";
}

$entities = $monitorService->getEntities();	
foreach ($entities as $ent) {
	echo $ent->getId()."\n";
	print_r($ent->getMetadata()->toArray());
	print_r($ent->createJson());
	$checks=$ent->getChecks();
	foreach ($checks as $check) {
		echo "     ".$check->getId()."\n";
		print_r($check->createJson());
	}  
}

while($server = $serverList->next()) { 
    print $server->id() . " -> " . $server->name(). " -> " . $server->url()."\n";
}

$entities = $monitorService->getEntities();	
foreach ($entities as $ent) {
	//echo $ent->getId()." -> ". $ent->agent_id . "\n";
	}
$entities = $monitorService->getEntities();	
	for ($i = 0; $i < count($entities); $i++) {
        print  $entities[$i]->id ." -> ". $entities[$i]->agent_id."\n";
    }
?>