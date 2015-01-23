<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../include/helper.php");
$loginPage = "../login.php";
$loginRedirectHeader = "Location: ".$loginPage;
session_start();

/* Generate Form Token (valid for this session) */
if (!isset($_SESSION['CSRFToken'])) {
	$_SESSION['CSRFToken'] = generateFormToken();
}

/* DEBUG 
echo "<br />SESSION TOKEN: ";
echo $_SESSION['CSRFToken'];
if (isset($_POST['CSRFToken'])) {
	echo "<br />POST TOKEN: ";
	echo $_POST['CSRFToken'];
}*/

if ( !isset($_SESSION['user_email']) || !isset($_SESSION['user_level']) || !isset($_SESSION['user_login']) ) {
    echo "Session Invalid. <a href='$loginPage'>Click here</a> to sign in.";
    
    /* No Session -> Redirect to Login */
    //header($loginRedirectHeader);
} else if ( $_SESSION['user_email'] == "" || $_SESSION['user_level'] == "" || $_SESSION['user_login'] == "") {
	echo "Empty Session Data. <a href='$loginPage'>Click here</a> to sign in.";
	
	/* Destroy Session */
	$_SESSION = array();
	session_destroy();
	
	/* Session Data Invalid -> Redirect to Login */
	//header($loginRedirectHeader);
}  else if($_SESSION['user_level']){
		header("Location: ../login.php");
		die();
	}

else {
	/* Session Valid */
	$user = new User();
	$user->getUserDataFromEmail( $_SESSION['user_email'] );
	$selectedAccount = "none";
	
	/* Selected Account Detected */
	if ( isset( $_SESSION['selectedAccount'] )) {
		$selectedAccount = $_SESSION['selectedAccount'];
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
	<title>MyBank: Account Overview</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script src="../include/jquery-2.1.3.min.js"></script>
	<link href="../style/bootstrap.css" rel="stylesheet" />
	<script src="../include/bootstrap.min.js"></script>
	<link href="../style/pure.css" type="text/css" rel="stylesheet" />
	<link href="../style/bootstrap.css" rel="stylesheet">
	<link href="../style/style.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<!--
			<div class="top_block header">
				<div class="content">
					<div class="navigation">
						Account
						<a href="transfer.php">Transfer</a>
						<a href="history.php">History</a>
					</div>
					
					<div class="balance"><?php if ($selectedAccount > 0) { echo "Account Balance: ".$user->getBalanceForAccount( $selectedAccount );}?></div>
					<div class="availableFunds"><?php if ($selectedAccount > 0) { echo "Available Funds: ".$user->getAvailableFundsForAccount( $selectedAccount );}?></div>
					<div class="userpanel">
						<?php echo $_SESSION['user_email'] ?>
						<a href="../logout.php">Logout</a><br />
						<?php 
						if ($selectedAccount > 0) {
						echo "Account: ".$_SESSION['selectedAccount'];
						} else {
						echo "Account: none";
						}
						?>
					</div>
				</div>
			</div>
		-->
		<div id="header">
		</div>
		<div id="top-block">
			<div id="top-block-main">
				<div id="logo"></div> <!-- logo -->
				<div id="navbar">
					<ul class="nav nav-tabs">
					  <li class="active">
						<a href="#">Dashboard</a>
					  </li>
					  <li><a href="#">Transfer</a></li>
					  <li><a href="#">History</a></li>
					  <li class="dropdown">
					  <a class="dropdown-toggle"
						 data-toggle="dropdown"
						 href="#">
						  Account
						  <b class="caret"></b>
					  </a>
						<ul class="dropdown-menu">
						  <li><a href="details.php">View Details</a></li>
						  <li><a href="../logout.php">Help</a></li>
						  <li class="divider"></li>
						  <li><a href="../logout.php">Log out</a></li>
						</ul>
					  </li>
					</ul>


				</div> <!-- navbar -->
			</div> <!-- top-block-main -->
		</div> <!-- top-block  -->
		
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
				$userAccounts = $user->getAccounts();
				foreach ($userAccounts as $account) {
					?>
					<form method="post" action="">
						<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
						<input type="hidden" name="accountNumber" value="<?php echo $account; ?>" />
						<input type="submit" name="selectAccount" class="pure-button pure-button-active" value="<?php echo $account; ?>" 
						<?php if (isset($_SESSION['selectedAccount']) && ( $account == $_SESSION['selectedAccount'])) echo "style=\"width: 170px; background: rgb(223, 117, 20);\"";
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
			<p>Below is list of your accounts and transactions.<br />You can view the history of individual accounts by selecting an account and then selecting history.</p>
			<hr>

			<?php
			foreach($userAccounts as $account) {
					$transactions = $user->getTransactions( $account );
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
		
		<div id="footer">Test</div>
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
