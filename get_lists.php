#!/usr/bin/env php
<?php

require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

$imageList=$compute->imageList();
$flavorList=$compute->flavorList();

while($image = $imageList->next()) { 
    print $image->id() . " -> " . $image->name()."\n";
}
print "\n\n";
	
while($flavor = $flavorList->next()) { 
    print $flavor->id() . " -> " . $flavor->name()."\n";
}
?>