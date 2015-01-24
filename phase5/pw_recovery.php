<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/include/db_connect.php"); 
include_once(__DIR__."/class/c_user.php");
include_once(__DIR__."/header.php");
session_start();

if ( !isset($_SESSION['user_email']) || !isset($_SESSION['user_level']) || !isset($_SESSION['user_login']) ) {
    // thats okay
} else if ( $_SESSION['user_email'] == "" || $_SESSION['user_level'] == "" || $_SESSION['user_login'] == "") {
	// thats okay
} else {
	// user already logged in
	if($_SESSION['user_level']) {
		header("Location: employee/approve.php");
	} else {
		header("Location: account/index.php");
	}
	die();
}


if( isset($_GET['email']) && isset($_GET['id']) ) {
	
	$user = new User();
	$user->getUserDataFromEmail($_GET['email']);
	$success = false;
	if($user->email) {
		// if we found the mail it is a valid user
		$success = $user->doPwRecovery($_GET['id']);
	}?>
	
	<!doctype html>
	<html>
		<head>
			<title>Password Recovery | myBank</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<script src="include/jquery-2.1.3.min.js"></script>
			<link href="style/bootstrap.css" rel="stylesheet" />
			<script src="include/bootstrap.min.js"></script>
			<link href="style/pure.css" type="text/css" rel="stylesheet" />
			<link href="style/bootstrap.css" rel="stylesheet">
			<link href="style/style.css" type="text/css" rel="stylesheet" />
		</head>
		
		<body>
			<div id="content">
				
				<?php render_guest_header("Login"); ?>
				
			
				<div id="main">
					<p>
					<?php 
					if($success) {
						echo "Your new password has been sent to you via email!";
					} else {
						echo "Something went wrong, please try again later!";
					}?>
					</p>
				</div> <!-- main -->
			</div><!-- content -->
		</body>
	</html>

<?php 
} else if( !(isset( $_POST['recoverPassword'] ) ) ) { ?>
	<!doctype html>
	<html>
		<head>
			<title>Password Recovery | myBank</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<script src="include/jquery-2.1.3.min.js"></script>
			<link href="style/bootstrap.css" rel="stylesheet" />
			<script src="include/bootstrap.min.js"></script>
			<link href="style/pure.css" type="text/css" rel="stylesheet" />
			<link href="style/bootstrap.css" rel="stylesheet">
			<link href="style/style.css" type="text/css" rel="stylesheet" />
		</head>
		
		<body>
			<div id="content">
				
				<?php render_guest_header("Login"); ?>
				
			
				<div id="main">
					<p>If you changed your mind, <a href="login.php">Click here</a> to go back to login.<br /><br /></p>
					<form method="post" action="" class="pure-form pure-form-aligned">
						<fieldset>
							<div class="pure-control-group">
								<label for="email">Email</label>
								<input name="email" id="email" type="email" placeholder="YourAccount@bank.de" required>
							</div>
					
							<div class="pure-controls">
								<button id="SignInButton" onclick="setTimeout(disableFunction, 1)" type="submit" name="recoverPassword" class="pure-button pure-button-primary">Send new Password</button>
							</div>
						</fieldset>
					</form>	
					<script>
						function disableFunction() {
							document.getElementById("SignInButton").disabled = 'true';
						}
					</script>
				</div><!-- main -->
			</div><!-- content -->
		</body>
	</html>

<?php 
} else {
	
	$user = new User();
	
	$user->getUserDataFromEmail($_POST['email']);
	
	if($user->email) {
		// if we found the mail it is a valid user
		$user->sendPwRecoveryMail();
	}?>
		<!doctype html>
	<html>
		<head>
			<title>Password Recovery | myBank</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<script src="include/jquery-2.1.3.min.js"></script>
			<link href="style/bootstrap.css" rel="stylesheet" />
			<script src="include/bootstrap.min.js"></script>
			<link href="style/pure.css" type="text/css" rel="stylesheet" />
			<link href="style/bootstrap.css" rel="stylesheet">
			<link href="style/style.css" type="text/css" rel="stylesheet" />
		</head>
		
		<body>
			<div id="content">
				
				<?php render_guest_header("Login"); ?>
			
				<div id="main">
					<p>A link to recover your password has been dispatched to the provided email address.</p>
					<p><br /><a href="login.php">Click here</a> to go back to login.</p>
			
				</div><!-- main -->
			</div><!-- content -->
		</body>
	</html>
<?php 
}
?>
