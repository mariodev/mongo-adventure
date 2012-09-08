<?php
// reload MySQL acme database
include 'acme.php';

require '../lib/customer.php';
printf("Saving new customer object...\n");
$customer = new Customer();

// clear mongo 'customer_metadata' collection
$customer->_collection->remove();

$customer->first_name = 'Marky'; 
$customer->last_name = 'Mark'; 
$customer->email = 'lala@lala.la'; 
$customer->date_of_birth = '1982-04-07'; 
$status = $customer->save();

printf("\tDone. ID %d\n", $customer->id);
printf("Saving Metadata....\n");
$metadata = array(
	'Middle Name' => 'The Gun',
	'Social Networking' => array(
		'Twitter Handle' => '@joethegun',
		'Facebook Username' => 'joe the socialguy'
	),
	'Has a Blog?' => True
);

$customer->setMetaData($metadata);
printf("\tDone\n");
printf("Loading metadata...\n");
print_r($customer->getMetaData());
printf("Updating metadata...\n");
$metadata = array(
	'Marriage Anniversary' => new MongoDate(strtotime('10 September 2005')),
	'Number of Kids' => 3,
	'Favorite TV Shows' => array('The Big Bang Theory', 'Star Trek Next Generation')
);
$customer->setMetaData($metadata);
printf("\tDone\n");
printf("Reloading metadata...\n");
print_r($customer->getMetaData());
