<?php
include_once(__DIR__."/include/helper.php");
include_once(__DIR__."/include/InvalidSessionException.php");

if (!function_exists('render_user_header')) {
	function render_user_header ($selectedAccount, $user, $activeTab) {
		?>
		<!-- header & top block -->
		<div id="header">
				<div id="header-contents">
					<div id="selectedAccountIcon"><?php echo $selectedAccount ?></div><!--<div class="accountInfoText"></div>-->
					<div id="balanceAccountIcon"><?php echo $user->getBalanceForAccount ( $selectedAccount ) ?></div><!--<div class="accountInfoText"></div>-->
					<div id="availableAccountIcon"><?php echo $user->getAvailableFundsForAccount ( $selectedAccount ) ?> </div><!--<div class="accountInfoText"></div>-->
				</div>
		</div>
		
		<div id="top-block">
			<div id="top-block-main">
				<div id="logo"></div> <!-- logo -->
				<div id="navbar">
					<ul class="nav nav-tabs">
						<?php if ($activeTab == "Dashboard") { ?>
							<li class="active">
						<?php } else { echo "<li>"; } ?>
								<a href="index.php">Dashboard</a>
							</li>
							
						<?php if ($activeTab == "Transfer") { ?>
							<li class="active">
						<?php } else { echo "<li>"; } ?>
								<a href="transfer.php">Transfer</a>
							</li>
						<?php if ($activeTab == "Account") { ?>
							<li class="dropdown active">
						<?php } else { ?>
							<li class="dropdown">
						<?php } ?>
								<a class="dropdown-toggle" data-toggle="dropdown" href="#">Account
								<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<li><a href="history.php">History</a></li>
									<li><a href="details.php">View Details</a></li>
									<li class="divider"></li>
									<li><a href="../logout.php">Log out</a></li>
								</ul>
							</li>
					</ul>
				</div> <!-- navbar -->
			</div> <!-- top-block-main -->
		</div> <!-- top-block  -->
		<br />
		<!-- top block ends -->
		<?php 
		}
	}

if (!function_exists('render_guest_header')) {
	function render_guest_header ($activeTab) {
		?>
		<!-- header & top block -->
		<div id="header">
				<div id="header-contents">
				</div>
		</div>
		
		<div id="top-block">
			<div id="top-block-main">
				<div id="logo"></div> <!-- logo -->
				<div id="navbar">
					<ul class="nav nav-tabs">
						<?php if ($activeTab == "Login") { ?>
							<li class="active">
						<?php } else { echo "<li>"; } ?>
								<a href="login.php">Sign in</a>
							</li>
							
						<?php if ($activeTab == "Register") { ?>
							<li class="active">
						<?php } else { echo "<li>"; } ?>
								<a href="register.php">Register</a>
							</li>
					</ul>

				</div> <!-- navbar -->
			</div> <!-- top-block-main -->
		</div> <!-- top-block  -->
		<br />
		<!-- top block ends -->
	<?php 
	}
} 
?>

<?php
if (!function_exists('render_employee_header')) {
	function render_employee_header ($activeTab) {
		?>
		<!-- header & top block -->
		<div id="header">
				<div id="header-contents">
				</div>
		</div>
		
		<div id="top-block">
			<div id="top-block-main">
				<div id="logo"></div> <!-- logo -->
				<div id="navbar">
					<ul class="nav nav-tabs">
						<?php if ($activeTab == "Approval") { ?>
							<li class="active">
						<?php } else { echo "<li>"; } ?>
								<a href="approve.php">Approval</a>
							</li>
							
						<?php if ($activeTab == "Customers") { ?>
							<li class="active">
						<?php } else { echo "<li>"; } ?>
								<a href="users.php">Customers</a>
							</li>
							
						<?php if ($activeTab == "Logout") { ?>
							<li class="active">
						<?php } else { echo "<li>"; } ?>
								<a href="../logout.php">Log out</a>
							</li>
					</ul>

				</div> <!-- navbar -->
			</div> <!-- top-block-main -->
		</div> <!-- top-block  -->
		<br />
		<!-- top block ends -->
	<?php 
	}
} 
?>