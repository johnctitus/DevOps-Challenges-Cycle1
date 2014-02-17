#!/usr/bin/env php
<?php

require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

$dlist = $dns->DomainList();

$input_domain=null;
$input_str="";
while($input_domain==null){
    while($domain = $dlist->next()) { 
        $current_domain=$domain->name();
	    if ($current_domain==$input_str) { 
	        $input_domain=$domain;
			print "Found ".$input_str."\n";
		} else {   
		    if ($input_str=="") { print $current_domain."\n"; }
        }
    }
	
	if ($input_domain==null){ 
	   if ($input_str!="") { print "ERROR: ".$input_str." Domain not found.\n\n"; }
	   print "Which domain would you like to add a record to?\n";
	   $dlist->rewind();
	   $input_str = trim(fgets(STDIN));
	}
}

$new_rec=$input_domain->record();
$new_rec->type = 'A';
print "\nWhat name would you like to use for the new record?\n";
$new_rec->name = trim(fgets(STDIN)).".".$input_domain->name();
print "\nWhat ttl would you like to use for the new record?\n";
$new_rec->ttl = trim(fgets(STDIN));
print "\nWhat IP would you like to use for the new record?\n";
$new_rec->data = trim(fgets(STDIN));
$new_rec->comment = 'DevOps Challenge 3';

try {
    $new_rec->create();
} catch (\Guzzle\Http\Exception\BadResponseException $e) {

    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();
    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
}

print "TYPE: ".$new_rec->type."\n";
print "NAME: ".$new_rec->name."\n";
print "TTL: ".$new_rec->ttl."\n";
print "DATA: ".$new_rec->data."\n";
print "PRIORITY: ".$new_rec->priority."\n";
print "COMMENT: ".$new_rec->comment."\n\n";

?>