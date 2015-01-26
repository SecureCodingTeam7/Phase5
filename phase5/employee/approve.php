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
	/* Session Valid */
	$user = DataAccess::getUserByEmail ( $_SESSION['user_email'] );
	
	$submitSucces = false;
	if (isset($_POST['approve'])) {
		$submitMessage = "Please select what to approve.";

		if(isset($_POST['transactions'])) {
			EmployeeController::approveTransactions($_POST['transactions']);
			$submitMessage = "Approval was successful!";
		}
		
		if( isset( $_POST['users'] ) ) {
			try {
			EmployeeController::approveUsers( $_POST );
			$submitMessage = "Approval was successful!";
			} catch ( InvalidInputException $ex ) {
				$submitMessage = $ex->errorMessage();
			}
		}
	}
	
?>
<!doctype html>
<html>
	<head>
		<title>Account Approval | myBank</title>
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
		
			<?php render_employee_header("Approval"); ?>
			
			<div id="main">
				<form method="post">
				<?php
					if (isset($submitMessage))  {
						echo $submitMessage."<br />";
					}
					echo "Currently unapproved Transactions<br /><br />";
					
					$transactions = TransactionController::getInApprovedTransactions();
					$odd = true;
					$count = 0;
					
					?>
					<table class="pure-table">
						<thead>
							<tr>
								<th>#</th>
								<th>Source</th>
								<th>Destination</th>
								<th>Amount</th>
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
								<td><?php echo "<input type=\"checkbox\" name=\"transactions[]\" value=\"".$transaction['id']."\">" ?></td>
								<td><?php echo $transaction['source']; ?></td>
								<td><?php echo $transaction['destination']; ?></td>
								<td><?php echo $transaction['amount']; ?></td>
								<td><?php echo $transaction['date_time']; ?></td>
							</tr>
					<?php
					}
					?>

						</tbody>
					</table>
					<br>
					
				<?php 
					echo "Currently unapproved Users<br /><br />";
					
					$users = DataAccess::getInApprovedUsers();
					$odd = true;
					$count = 0;
					
					?>
					<table class="pure-table">
						<thead>
							<tr>
								<th>#</th>
								<th>Email</th>
								<th>Employee</th>
								<th>Set Balance</th>
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
								<td><?php echo "<input type=\"checkbox\" name=\"users[]\" value=\"".$user['id']."\">" ?></td>
								<td><?php echo $user['email']; ?></td>
								<td><?php if ($user['is_employee'] > 0) echo "yes"; else echo "no"; ?></td>
								<td>
								<?php 
									if ($user['is_employee'] > 0) {
										echo "N/A";
									} else {
									?>
									
									<div class="pure-control-group">
									<input name="<?php echo "balance".$user['id']; ?>" id="balances" type="text" placeholder="">
									</div>
								
								
									<?php
									}
								?>
								</td>
							</tr>
					<?php
					}
					?>

						</tbody>
					</table>

				<br>
				<div class="pure-controls">
					<button id="approveButton" onclick="setTimeout(disableFunction, 1)" type="submit" name="approve" class="pure-button pure-button-primary">Approve</button>
				</div>
				</form>
			</div><!-- main -->
		</div><!-- content -->
		<script>
			function disableFunction() {
				document.getElementById("approveButton").disabled = 'true';
			}
		</script>
	</body>
</html>

<?php
}
?>
