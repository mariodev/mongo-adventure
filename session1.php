<?php
require('lib/session.php');

$rand = rand();
$_SESSION['random_number'] = $rand;
?>
<html>
<head>
	<title>session test</title>
</head>
<body>
	<h2><?php echo $_SESSION['random_number'] ?></h2>
	<h3>Session ID: <?php echo session_id() ?></h3>
	<hr>
	<a href="session2.php">Go To Next Page</a>
</body>
</html>