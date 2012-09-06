<?php
require 'mysql.class.php';

db::configure( 'db_dsn', 'mysql:host=localhost;dbname=acmeproducts' );
db::configure( 'db_user', 'root' );
db::configure( 'db_password', '' );

$db = db::get_instance();