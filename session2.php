<?php
require('lib/session.php');
?>
<html>
<head>
	<title>session test 2</title>
</head>
<body>
	<h2><?php echo $_SESSION['random_number'] ?></h2>
	<h3>Session ID: <?php echo session_id() ?></h3>
	<hr>
	<a href="session2.php">Go To Next Page</a>
</body>
</html>