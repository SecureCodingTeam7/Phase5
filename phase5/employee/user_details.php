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
	if(!isset($_POST['id'])) {
		header("Location:users.php");
		die();
	} else if( !isset( $_POST['CSRFToken'] ) || !validateFormToken( $_POST['CSRFToken'] )) {
		$_SESSION['error'] = "The CSRF Token was not found or invalid.";
		header("Location:../logout.php");
		die();
	} else {
		/* Session Valid */
		$user = DataAccess::getUserByEmail ( $_SESSION['user_email'] );
		
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
					
						$selectedUser = DataAccess::getUserByID ( $_POST['id'] );
						echo "<a href=\"users.php\">back to user list</a>";
						echo "  <br /><p style=text-indent:1em;>Details for ".$selectedUser->email."</p>";
						
						$accounts = $selectedUser->getAccounts();
						
						foreach($accounts as $account) {
							$transactions = DataAccess::getTransactions( $selectedUser, $account );
							$odd = true;
							$count = 0;
							
							
							?>
							<table class="pure-table">
							<caption style="caption-side:top"><?php echo "<br />Account #".$account;?></caption>
								<thead>
									<tr>
										<th>#</th>
										<th>Source</th>
										<th>Src-Name</th>
										<th>Destination</th>
										<th>Dst-Name</th>
										<th>Amount</th>
										<th>Valid</th>
										<th>Description</th>
										<th>Time</th>
									</tr>
								</thead>
							
								<tbody>
							<?php 
							foreach ($transactions as $transaction) { 
									if ($odd) {
										echo "<tr class=\"pure-table-odd\">";
										$odd = false;
									} else { 
										echo "<tr>";
										$odd = true; 
									}?>
										<td><?php echo ++$count; ?></td>
										<td><?php if ($transaction['source'] == $account) {
											 echo "<p class=\"selectedAccount\">".$transaction['source']."</p>";
										} else { echo $transaction['source']; } ?></td>
										<td><?php echo $transaction['source_name'] ?></td>
										<td><?php if ($transaction['destination'] == $account) {
											 echo "<p class=\"selectedAccount\">".$transaction['destination']."</p>";
										} else { echo $transaction['destination']; } ?></td>
										<td><?php echo $transaction['destination_name'] ?></td>
										<td><p class=<?php if($transaction['destination'] == $account) echo "\"income\">"; else echo "\"expense\">"; echo $transaction['amount']."</p>"; ?></td>
										<td><?php if ($transaction['is_approved'] > 0) echo "yes"; else echo "no"; ?></td>
										<td><?php echo $transaction['description'] ?></td>
										<td><?php echo $transaction['date_time']; ?></td>
									</tr>
							<?php
							}
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
}
?>
