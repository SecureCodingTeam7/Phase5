<?php
session_start();
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../class/c_DataAccess.php");
include_once(__DIR__."/../class/c_UserController.php");
include_once(__DIR__."/../include/helper.php");
include_once(__DIR__."/../include/InvalidSessionException.php");
include_once(__DIR__."/../header.php");

$loginPage = "../login.php";
$loginRedirectHeader = "Location: ".$loginPage;
		
try {
	validateUserSession(false);
	$session_valid = true;
} catch (InvalidSessionException $ex) { 
	/* DEBUG */
	echo $ex->getMessage();
	$session_valid = false;
	//header("Location:../logout.php");
	die();
}

/* DEBUG 
echo "<br />SESSION TOKEN: ";
echo $_SESSION['CSRFToken'];
if (isset($_POST['CSRFToken'])) {
	echo "<br />POST TOKEN: ";
	echo $_POST['CSRFToken'];
}*/

if ($session_valid) {
	
	/* Session Valid */
	$user = DataAccess::getUserByEmail ($_SESSION['user_email']);
	$userAccounts = $user->getAccounts();
	
	/* Generate Form Token (valid for this session) */
	if (!isset($_SESSION['CSRFToken'])) {
		$_SESSION['CSRFToken'] = generateFormToken();
	}
	
	/* Determine Selected Account */
	if ( isset( $_SESSION['selectedAccount'] )) {
		$selectedAccount = $_SESSION['selectedAccount'];
	} else {
		if ( !empty( $userAccounts ) ) {
			$selectedAccount = $userAccounts[0];
			$_SESSION['selectedAccount'] = $selectedAccount;
		}
		else {
			$selectedAccount = "none";
		}
	}
	
	/* Create New Account Detected */
	if( ( isset( $_POST['createAccount'] )) ) { 
		/* Check presence & validity of CSRF Token */
		if (isset( $_POST['CSRFToken']) && validateFormToken($_POST['CSRFToken'])) {
			/* Perform Account Creation */
			$accNumber = randomDigits( 10 );
			$user->addAccount( $accNumber );
		} else {
			$_SESSION['error'] = "CSRF Token Invalid.";
		}
	}
	
	/* New Account Selection Detected */
	if ( ( isset( $_POST['selectAccount']))) {
		
		/* Check presence & validity of CSRF Token */
		if (isset( $_POST['CSRFToken']) && validateFormToken($_POST['CSRFToken'])) {
			/* Obtain POST DATA (Account number) */
			$selectedAccount = $_POST['accountNumber'];
			
			/* Validate that given account number belongs to this user */
			$accounts = $user->getAccounts();
			if( in_array( $selectedAccount, $accounts ) ) {
				$_SESSION['selectedAccount'] = $selectedAccount;
			} else {
				/* Possible malicious activity: Account does not belong to user
				 * Raise Session Error and close the session
				 */
				$_SESSION['error'] =  "Account mismatch detected.";
			}
		}  else {
			$_SESSION['error'] = "CSRF Token Invalid.";
		}
	}
	
	/* If error or possible malicious activity was detected close the session */ 
	if ( ( isset( $_SESSION['error'] ) ) ) {
		header("Location:../logout.php");
		die();
	}
?>
<!doctype html>
<html>
<head>
	<title>Account Dashboard | myBank</title>
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
	
		<?php render_user_header($selectedAccount, $user, "Dashboard"); ?>
		
		<div id="main">

			<div>
				Welcome, <em><?php echo $_SESSION['user_email']; ?></em>. Below is a list of your accounts. <br /><br />
				You can <em>click</em> on any of your accounts to <em>select</em> it. The <em>active</em> account is <em><FONT COLOR="orange">marked in orange</FONT></em> and indicated in the <em>top right corner</em>. <br />
				You can also <em>create a new account</em> by clicking on the <em><FONT COLOR="blue">Create new Account</FONT></em> button.
			</div>
			
			<div class="accountList">
				<div>
				<?php
				/* Get User Accounts */
				foreach ($userAccounts as $account) {
					?>
					<form method="post" action="">
						<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
						<input type="hidden" name="accountNumber" value="<?php echo $account; ?>" />
						<input type="submit" name="selectAccount" class="pure-button pure-button-active" value="<?php echo $account; ?>" 
						<?php if ( isset( $selectedAccount ) && ( $account == $selectedAccount ) ) echo "style=\"width: 170px; background: rgb(223, 117, 20);\"";
								else { echo "style=\"width: 160px;\""; }
						?>/>
					</form>
					<?php
				}
				?>
				</div>

				<?php if($user->useScs == "1") { ?>
					<div class="SCSDownload">
						<form method="post" action="download.php">
							<li class="buttons">
								<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
								<input type="submit" name="downloadSCS" value="Download SCS" class="pure-button pure-button-primary" id="DownloadSCSButton" />
							</li>
						</form>
					</div> <!-- SCS DL Button -->
				<?php }else {} ?>
					<div class="accountCreation">
						<form method="post" action="">
							<li class="buttons">
								<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
								<input type="submit" name="createAccount" onclick="setTimeout(disableFunction, 1);" value="Create New Account" class="pure-button pure-button-primary" id="createNewAccountButton" />
							</li>
						</form>
					</div> <!-- Create Account Button -->
			</div> <!-- Account List -->

		<div class="accountOverview">
			<p>Below is list of your accounts and transactions.<br />You can view the history of individual accounts by selecting an account above and then selecting Account -> History in the navigation panel.</p>
			<hr>

			<?php
			foreach($userAccounts as $account) {
					$transactions = DataAccess::getTransactions( $user, $account );
					$odd = true;
					$count = 0;
					
					
					?>
					<table class="pure-table">
					<caption style="caption-side:top"><?php echo "<br />Account #".$account;?></caption>
						<thead>
							<tr>
								<th class="tdsmall">#</th>
								<th class="tdlarge">Source</th>
								<th class="tdlarge">Src-Name</th>
								<th class="tdlarge">Destination</th>
								<th class="tdlarge">Dst-Name</th>
								<th class="tdlarge">Amount</th>
								<th class="tdlarge">Valid</th>
								<th class="tdlarge">Description</th>
								<th class="tdlarge">Time</th>
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
								<td class="tdsmall"><?php echo ++$count; ?></td>
								<td class="tdlarge"><?php if ($transaction['source'] == $account) {
									 echo "<p class=\"selectedAccount\">".$transaction['source']."</p>";
								} else { echo $transaction['source']; } ?></td>
								<td class="tdlarge"><?php echo $transaction['source_name'] ?></td>
								<td class="tdlarge"><?php if ($transaction['destination'] == $account) {
									 echo "<p class=\"selectedAccount\">".$transaction['destination']."</p>";
								} else { echo $transaction['destination']; } ?></td>
								<td class="tdlarge"><?php echo $transaction['destination_name'] ?></td>
								<td class="tdlarge"><p class=<?php if($transaction['destination'] == $account) echo "\"income\">"; else echo "\"expense\">"; echo $transaction['amount']."</p>"; ?></td>
								<td class="tdlarge"><?php if ($transaction['is_approved'] > 0) echo "yes"; else echo "no"; ?></td>
								<td class="tdlarge"><?php echo $transaction['description'] ?></td>
								<td class="tdlarge"><?php echo $transaction['date_time']; ?></td>
							</tr>
					<?php
					}
				}
					?>

					</tbody>
				</table>
			</div> <!-- Account Overview -->
		</div> <!-- main -->
		
		
	</div> <!-- content -->
	<script>
		function disableFunction() {
		    document.getElementById("createNewAccountButton").disabled = 'true';
		}
	</script>
	
	<script>
		YUI({
			classNamePrefix: 'pure'
		}).use('gallery-sm-menu', function (Y) {

			var horizontalMenu = new Y.Menu({
				container         : '#demo-horizontal-menu',
				sourceNode        : '#std-menu-items',
				orientation       : 'horizontal',
				hideOnOutsideClick: false,
				hideOnClick       : false
			});

			horizontalMenu.render();
			horizontalMenu.show();

		});
	</script>
</body>
</html>

<?php
}
?>
