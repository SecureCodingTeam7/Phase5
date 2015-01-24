<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/include/db_connect.php");
include_once(__DIR__."/include/InvalidInputException.php"); 
include_once(__DIR__."/class/c_user.php");
include_once(__DIR__."/header.php");

if( !(isset( $_POST['checkRegister'] ) ) ) { ?>
<!doctype html>
<html>
	<head>
		<title>Sign up | myBank</title>
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
		
			<?php render_guest_header("Register"); ?>
		
			<div id="main">
				<p>Already have an account? <a href="login.php"><em>Click here</em></a> to sign in.<br /><br /></p>
	
				<form method="post" action="" class="pure-form pure-form-aligned">
					<fieldset>
						<div class="pure-control-group">
							<label for="email">Email</label>
							<input name="email" id="email" type="email" placeholder="YourAccount@bank.de" required>
						</div>
						
						<div class="pure-control-group">
							<label for="username">Name</label>
							<input name="username" id="username" type="text" placeholder="Name" onkeyup="check_un()" required>
							<b id=un_info></b>
						</div>
				
						<div class="pure-control-group">
							<label for="password">Password</label>
							<input name="password" id="password" type="password" placeholder="***********" onkeyup="check_pw()" required>
							<b id=password_info></b>
						</div>
						
						<div class="pure-control-group">
							<label for="confirm_password">Confirm Password</label>
							<input name="confirm_password" id="confirm_password" type="password" placeholder="***********" onkeyup="check_pw()" required>
							<b id=confirm_password_info></b>
						</div>
					   
						<div class="pure-control-group">
							<label for="status">Your Status</label>
							<select id="state" name="status" size="1">
								<option value="0">Client</option>
								<option value="1">Employee</option>
							</select>
						</div> 
						
						<div id="use_scs_div" class="pure-control-group">
							<label id="use_scs_label" for="use_scs">TAN method</label>
							<select id="use_scs" name="use_scs" size="1">
								<option value="0">Email: PDF List</option>
								<option value="1">SCS</option>
							</select>
						</div> 
						
						<div class="pure-control-group">
							<label for="sec_q_number">Security Question (Needed for Password recovery)</label>
							<select id="sec_q_number" name="sec_q_number" size="1">
							<?php
							$counter = 0;
							foreach($SECURITY_QUESTIONS as $question) {
							echo "<option value=\"$counter\">$question</option>";
							$counter = $counter +1;
							}
							?>
							</select>
						</div>

						<div class="pure-control-group">
							<label for="sec_q_answer">Answer</label>
							<input name="sec_q_answer" id="sec_q_answer" type="text" placeholder="Answer" onkeyup="check_answer()" required>
							<b id=answer_info></b>
						</div>
						
						<div class="pure-controls">
							<button id="SignInButton" type="submit" name="checkRegister" class="pure-button pure-button-primary" onclick="setTimeout(disableFunction, 1);">Finish Registration</button>
						</div>
					</fieldset>
				</form>
			</div> <!-- main -->
		</div> <!-- content -->
		<script>
			function disableFunction() {
				document.getElementById("SignInButton").disabled = 'true';
			}
		</script>
	</body>
	
	<script>
		var pw_info = document.getElementById("password_info")
		var confirm_pw_info = document.getElementById("confirm_password_info")
		
		var pw_field = document.getElementById("password")
		var confirm_pw_field = document.getElementById("confirm_password")
		
		var submit_button = document.getElementById("SignInButton")
		submit_button.disabled = true
		
		function check_pw() {
			// lets check if the pw is strong enough
			var pw = pw_field.value
			var lowercase = pw.search("[A-Z]")
			var uppercase = pw.search("[a-z]")
			var number = pw.search("[0-9]")
			
			if(pw.length < 8 || lowercase == -1 || uppercase == -1 || number == -1) {
				pw_info.textContent = "Password must have at least eight characters, one upper case, one lower case letter and one number"
				submit_button.disabled = true
			} else {
				pw_info.textContent = ""
			}
		
			if(confirm_pw_field.value != pw_field.value) {
				confirm_pw_info.textContent = "The two passwords do not match!"
				submit_button.disabled = true
			} else {
				confirm_pw_info.textContent = ""
				submit_button.disabled = false
			}
		}

		function charUpperCase( char ) {
			return char !== char.toLowerCase();
		}

		function startsUpperCase( string ) {
			return charUpperCase( string.charAt(0) );
		}
		
		function check_un() {
			var un_field = document.getElementById("username")
			var un_info = document.getElementById("un_info")
			
			if (un_field.value.length >= 4) {
				if (un_field.value.match(/^[a-zA-Z ]+$/)) {
					un_info.textContent = ""

					if (!startsUpperCase(un_field.value)) {
						submit_button.disabled = true;
						un_info.textContent = "Name needs to start with a capital letter."	
					} else {
						submit_button.disabled = false;
						check_pw()
					}
				} else {
					submit_button.disabled = true;
					un_info.textContent = "Your name must contain only letters."
				}
			} else {
				submit_button.disabled = true;
				un_info.textContent = "Your name must be at least 4 characters long."
			}
		}
		
		function check_answer() {
			var an_field = document.getElementById("sec_q_answer")
			var an_info = document.getElementById("answer_info")

			if (an_field.value.length >= 4) {
				submit_button.disabled = false;
				an_info.textContent = ""
			} else {
				submit_button.disabled = true;
				an_info.textContent = "Answer must be at least 4 characters"
			}
		}
		
		var sel = document.getElementById('state');
		sel.onchange = function() {
			toggle_visibility("use_scs_div")
		}
		
		function toggle_visibility(cl){
			var els = document.getElementById(cl);
			var s = els.style;
			s.display = s.display==='none' ? 'block' : 'none'
		}
		
	</script>
</html>
<?php 
} else {
	//~ echo "checkRegister Post";

	$user = new User();
	//~ echo "<br />[DEBUG] Email: ". $_POST['email'];
	//~ echo "<br />[DEBUG] Password: " . $_POST['password'];
	//~ echo "<br />[DEBUG] Status: " . $_POST['status'];

	try {
		$success =  $user->register( $_POST );
		
		if ($success) {
			echo "<br />Registration for ". $_POST['email']." successful. Go to <a href='login.php'>Sign in</a>.";
		} else {
			echo "<br />Unable to register at this time. Please <a href='register.php'>try again</a>.";
		}
	} catch (InvalidInputException $ex) {
		echo $ex->errorMessage();
		echo "<br />Please <a href='register.php'>try again</a>.";
	}
}
?>
