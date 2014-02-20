#!/usr/bin/env php
<?php

require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

$imageList=$compute->imageList();
$flavorList=$compute->flavorList();
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
	echo $ent->getParent()->getName()."\n";
	print_r($ent->createJson());
	$checks=$ent->getChecks();
	foreach ($checks as $check) {
		echo "     ".$check->getId()."\n";
		print_r($check->createJson());
	}  
}

?>