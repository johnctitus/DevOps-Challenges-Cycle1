#!/usr/bin/env php
<?php
/*
Challenge 5: Write a script that creates a Cloud Database. 
If a CDB already exists with that name, suggest a new name 
like "name1" and give the user the choice to proceed or exit. 
The script should also create X number of Databases and X 
number of users in the new Cloud Database Instance. The 
script must return the Cloud DB URL. Choose your language 
and SDK! 
*/
require 'devops_include.php';
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

/*
 Create a new DbService Instance

$dbaas = $cloud->DbService();
$inst = $dbaas->Instance();         // new, empty Instance
$inst->flavor = $dbaas->Flavor(1);  // flavor ID
$inst->volume->size = 1;            // this specifies 4GB of disk
$inst->Create();                    // this actually creates the instance

Listing flavors:

$compute = $cloud->Compute();
$dbaas = $cloud->DbService();
$compute_flavors = $compute->FlavorList();
$dbaas_flavors = $dbaas->FlavorList();


 Creating a new database

To create a new database, you must supply it with a name; you can optionally specify its character set and collating sequence:

$db = $instance->Database();            // new, empty database object
$db->Create(array('name'=>'simple'));   // creates the database w/defaults
$prod = $instance->Database();          // empty database
$prod->Create(array(
    'name' => 'production',
    'character_set' => 'utf8',          // specify the character set
    'collate' => 'utf8_general_ci'      // specify sort/collate sequence
));

 Creating users

Database users exist at the Instance level, but can be associated with a specific Database. They are represented by the User object, which is constructed by the User() factory method:

$instance = $dbaas->Instance();
$user = $instance->User();          // a new, empty user

Users cannot be altered after they are created, so they must be assigned to databases when they are created:

$user->name = 'Fred';               // the user must have a name
$user->password = 'S0m3thyng';
$user->AddDatabase('simple');       // Fred can access the 'simple' DB
$user->AddDatabase('production');   // as well as 'production'
$user->Create();                    // creates the user

If you need to add a new database to a user after it's been created, you must delete the user and then re-add it.

As a shortcut, you can specify all the info in the parameter of the Create() method:

$user = $instance->User();
$user->Create(array(
    'name' => 'Fred',
    'password' => 'S0m3thyng',      // I made this up; don't bother trying it
    'databases => array('simple','production')));
	
Get Instance URL	
$instance->getUrl();

*/

//read instance_name # of databases from command line parameters
function syntax(){
    print "Syntax:  challenge_5.php <instance_name> <DB qty:>\n\n";
    exit;
}
if (count($argv)==3) {
    $instance_name = $argv[1];
    $db_qty = $argv[2];
    if ($db_qty < 0) { print "\n\nServer_qty invalid. Value must be between greater than 0\n\n";  syntax(); }
} else {
    print "\n\nSyntax Invalid.\n\n";
	syntax();
}
//check if name exists.  If name exists suggest alternate and prompt to continue or exit
$instances=$dbService->instanceList();
$instance=null;
$instance_found=FALSE;
while($i = $instances->next()) {
   if ($i->name() == $instance_name) { $instance_found=TRUE; }
} 

$instance = $dbService->instance();

//Create instance
if ( $instance_found ) {
    $instance_name.="_01";   
}

$instance->name = $instance_name;
$instance->flavor = $dbService->Flavor(1); 
$instance->volume->size = 1;    

try{
    $instance->create();
} catch (\Guzzle\Http\Exception\BadResponseException $e) {
    // No! Something failed. Let's find out:
    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();

    echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
} 
	$instance->waitFor(ServerState::ACTIVE, 600, $server_callback);


for ($i = 1; $i<= $db_qty;$i++){

//create x databases name <instance_name>_db_n
    $db = $instance->Database();            // new, empty database object
    try{
        $db->Create(array('name'=>$instance_name."_db".$i));   // creates the database w/defaults
    } catch (\Guzzle\Http\Exception\BadResponseException $e) {
        // No! Something failed. Let's find out:
        $responseBody = (string) $e->getResponse()->getBody();
        $statusCode   = $e->getResponse()->getStatusCode();
        $headers      = $e->getResponse()->getHeaderLines();
    
        echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
    } 

//$db->waitFor(ServerState::ACTIVE, 600, $server_callback);
//create x users name <instance_name>_user_n.  Tie user nn to db nn
    $user = $instance->User();

    try{
        $user->Create(array(
            'name' => "user".$i,
            'password' => 'S0m3thyngIm@d3uP',      // I made this up; don't bother trying it
            'databases' => array($instance_name."_db".$i)));
    } catch (\Guzzle\Http\Exception\BadResponseException $e) {
        // No! Something failed. Let's find out:
        $responseBody = (string) $e->getResponse()->getBody();
        $statusCode   = $e->getResponse()->getStatusCode();
        $headers      = $e->getResponse()->getHeaderLines();

        echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
    } 

}

//output instance url
//print "\n\n".$instance->getUrl()."\n";
print $instance->getHostname()."\n";
?>