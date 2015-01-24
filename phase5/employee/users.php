<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../include/helper.php");
include_once(__DIR__."/../include/InvalidInputException.php");
include_once(__DIR__."/../header.php");

$loginPage = "../login.php";
$loginRedirectHeader = "Location: ".$loginPage;
session_start();

try {
	validateUserSession(true);
	$session_valid = true;
} catch (InvalidSessionException $ex) { 
	/* DEBUG */
	// echo $ex->getMessage();
	$session_valid = false;
	header("Location:../logout.php");
	die();
}

if ($session_valid) {
	/* Generate Form Token (valid for this session) */
	if (!isset($_SESSION['CSRFToken'])) {
		$_SESSION['CSRFToken'] = generateFormToken();
	}
	
	/* Session Valid */
	$user = new User();
	$user->getUserDataFromEmail( $_SESSION['user_email'] );	
?>
<!doctype html>
<html>
	<head>
		<title>Customer Listing | myBank</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script src="../include/jquery-2.1.3.min.js"></script>
		<link href="../style/bootstrap.css" rel="stylesheet" />
		<script src="../include/bootstrap.min.js"></script>
		<link href="../style/pure.css" type="text/css" rel="stylesheet" />
		<link href="../style/bootstrap.css" rel="stylesheet">
		<link href="../style/style.css" type="text/css" rel="stylesheet" />
	</head>
	
	<body>
		<div id="content">
		
			<?php render_employee_header("Customers"); ?>
		
			<div id="main">
			<?php 
				echo "Current Customers (Click on the email to see details)<br /><br />";
				
				$users = $user->getAllUsers();
				$odd = true;
				$count = 0;
				
				?>
				<table class="pure-table">
					<thead>
						<tr>
							<th>#</th>
							<th>Email</th>
							<th>Approved</th>
						</tr>
					</thead>
				
					<tbody>
						<?php 
						foreach ($users as $user) { 
								if ($odd) {
									echo "<tr class=\"pure-table-odd\">";
									$odd = false;
								} else { 
									echo "<tr>";
									$odd = true; 
								}?>
									<td><?php echo ++$count ?></td>
									<td>
									<form method="post" action="user_details.php">
										<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
										<input type="hidden" name="id" value="<?php echo $user['id']; ?>" />
										<input type="submit" name="userDetailsSubmit" class="pure-button2" value="<?php echo $user['email']; ?>" />
									</form>
									</td>
									<td><?php if ($user['is_active'] > 0) echo "yes"; else echo "no"; ?></td>
								</tr>
						<?php
						}
						?>
					</tbody>
				</table>
				<br>
			</div><!-- main -->
		</div><!-- content -->
	</body>
</html>

<?php
}
?>
