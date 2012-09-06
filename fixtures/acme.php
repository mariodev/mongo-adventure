<?php

function getRandomArrayItemId($array) {
	$len = count($array);
	$randIndex = mt_rand(0, $len - 1);
	return $array[$randIndex]['id'];
}

function get_random_datetime($start_date, $end_date) {
    // Convert to timetamps
    $min = strtotime($start_date);
    $max = strtotime($end_date);

    // Generate random number using above bounds
    $val = rand($min, $max);

    // Convert back to desired date format
    return date('Y-m-d H:i:s', $val);
}

// exec('mysql -u root -e "DROP DATABASE IF EXISTS acmeproducts"')
exec('mysql -uroot -v < acme.sql');
// set connection handler
$dbh = new PDO('mysql:host=localhost;dbname=acmeproducts', 'root', '');


$products = array(
	array('name' => 'Sublime Text 2 License', 'unit_price' => 59),
	array('name' => 'Learning Node', 'unit_price' => 27.99),
	array('name' => 'Async C# 5.0', 'unit_price' => 9.99),
	array('name' => 'Kingston Digital DataTraveler SE9', 'unit_price' => 11.25),
	array('name' => 'BIC Round Stic Ball Pen', 'unit_price' => 1.81),
	array('name' => 'Red Bull Energy Drink', 'unit_price' => 40.99),
	array('name' => 'Premium Ginger Beer Sample Pack', 'unit_price' => 26.50),
	array('name' => 'Bormioli Rocco Giara Clear Glass Bottle With Stopper', 'unit_price' => 4)
);

// clear prodcuts table
// $dbh->prepare("DELETE FROM products")->execute();
// $dbh->query('DROP TABLE IF EXISTS products');

// populate products
foreach ($products as $key => $row) {
	$stmt = $dbh->prepare("INSERT INTO products (name, unit_price) VALUES (:name, :unit_price)");
	$stmt->bindParam(':name', $row['name']);
	$stmt->bindParam(':unit_price', $row['unit_price']);
	$stmt->execute();
	$products[$key]['id'] = $dbh->lastInsertId();
}

$customers = array(
	array('first_name' => 'Bill', 'last_name' => 'Gates', 'email_address' => 'bill@microsoft.com', 'date_of_birth' => '1955-09-11'),
	array('first_name' => 'Steve', 'last_name' => 'Jobs', 'email_address' => 'steve@apple.com', 'date_of_birth' => '1955-02-21'),
	array('first_name' => 'Steve', 'last_name' => 'Martin', 'email_address' => 'xx@microsoft.com', 'date_of_birth' => '1933-09-11'),
	array('first_name' => 'Danny', 'last_name' => 'DeVito', 'email_address' => 'yy@microsoft.com', 'date_of_birth' => '1945-09-21'),
	array('first_name' => 'Bill', 'last_name' => 'Murray', 'email_address' => 'zz@microsoft.com', 'date_of_birth' => '1989-02-13'),
	array('first_name' => 'Jack', 'last_name' => 'Jones', 'email_address' => 'ww@microsoft.com', 'date_of_birth' => '1985-04-01'),
	array('first_name' => 'Steve', 'last_name' => 'Wozniak', 'email_address' => 'vv@microsoft.com', 'date_of_birth' => '1976-08-22'),
);

// $dbh->prepare("DELETE FROM customers")->execute();
// $dbh->query('DROP TABLE IF EXISTS customers');

foreach ($customers as $key => $row) {
	$stmt = $dbh->prepare("INSERT INTO customers (first_name, last_name, email_address, date_of_birth) VALUES (:first_name, :last_name, :email_address, :date_of_birth)");
	$stmt->bindParam(':first_name', $row['first_name']);
	$stmt->bindParam(':last_name', $row['last_name']);
	$stmt->bindParam(':email_address', $row['email_address']);
	$stmt->bindParam(':date_of_birth', $row['date_of_birth']);
	$stmt->execute();
	$customers[$key]['id'] = $dbh->lastInsertId();
}

// populate sales table randomly
for ($i=0; $i < 50; $i++) { 	
	$row = array(
		'product_id' => getRandomArrayItemId($products),
		'customer_id' => getRandomArrayItemId($customers),
		'units_sold' => rand(1, 5),
		'time_of_sales' => get_random_datetime('2012-09-01', '2012-09-06') 
	);

	$stmt = $dbh->prepare("INSERT INTO sales (product_id, customer_id, units_sold, time_of_sales) VALUES (:product_id, :customer_id, :units_sold, :time_of_sales)");
	$stmt->bindParam(':product_id', $row['product_id']);
	$stmt->bindParam(':customer_id', $row['customer_id']);
	$stmt->bindParam(':units_sold', $row['units_sold']);
	$stmt->bindParam(':time_of_sales', $row['time_of_sales']);
	$stmt->execute();
}

echo 'Done!';
