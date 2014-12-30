<?php
/* Configuration variables */
	define('HOME_URL','http://www.cloverbeltlocalfoodcoop.com/');
	define('COOP_NAME', 'Cloverbelt Local Food Co-Op');
?>
<!DOCTYPE html>
<html>
<head>
	<title>CLFC -- HTML Template</title>
	<link rel="icon" type="image/png" href="images/clover_icon.png">
	<link rel="stylesheet" type="text/css" href="styles/clfc.css" media="all" />
	<link href='http://fonts.googleapis.com/css?family=Shadows+Into+Light+Two&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
</head>

<body>
	<div id="wrapper">
		<div id="header" class="trans_white_bg shadow">
			<div id="logo">
				<a href="<?php echo HOME_URL; ?>"><img src="images/CLFClogo_280x80.png" alt="CLFC" title="<?php echo COOP_NAME; ?>" class="logo" /></a><p class="f22 Windsor"><?php echo COOP_NAME; ?></p>
			</div>
			<div id="memberSocial">
				<!--<a href="http://www.cloverbeltlocalfoodcoop.com/member_form.php"><img src="images/CLFC.gif" alt="CLFC" title="CLFC" /></a><br />-->
				<a href="http://www.cloverbeltlocalfoodcoop.com/docs/CLFCMembershipApplication.pdf"><img src="images/ClickToJoin.gif" alt="CLFC" title="Become a CLFC member!" /></a><br />
				<span class="Windsor f16">
					<a href="http://www.facebook.com/cloverbelt" target="_blank" class="black"><img src="images/facebook.png" width="32" height="32" alt="CLFC on Facebook" title="CLFC on Facebook" class="vert_middle" /> Facebook</a>
					&nbsp;&nbsp;
					<a href="mailto:membership@cloverbeltlocalfoodcoop.com" class="black"><img src="images/email-icon32.png" alt="email" title="Email us" class="vert_middle" /> Email Us</a>
				</span>
			</div>
		</div>
		<!-- END OF HEADER -->

<!-- THIS IS THE SIDEBAR THAT WE WON'T USE FOR THE ORDERING SITE -->
		<div id="quotes" class="float_right trans_black_bg intoLight">
			<h1 class="f14 white">Word on the street!</h1>
			<p class="f10 indent">"We are enthusiastic about the opportunity to sell our home baked breads and other products, as well as continuing to source local ingredients and flours for both my customers and family." 
				<span class="f12 italic">--Beth Zurbrigg, producer, baked goods, preserves, and veggies</span>
			</p>
			<br />
			<p class="f10 indent">"We are looking forward to selling our beef online to customers." 
				<span class="f12 italic">--Maria Wildhaber, Milkwell Farm</span>
			</p>
<!--			<p class="f14 indent">"" 
				<span class="f12 italic">--</span>
			</p>-->
			<br />
			<div id="downloads" class="left">
				<h1 class="f14 white">Downloads</h1>
				<p>
					<a href="docs/CLFCMembershipApplication.pdf" class="white"><img src="images/pdficon_large.png" alt="Application" title="Application" class="vert_middle" /> Membership Application</a>
				</p>
				<p>
					<a href="docs/CLFCMembershipHandbook.pdf" class="white"><img src="images/pdficon_large.png" alt="Handbook" title="Handbook" class="vert_middle" /> Handbook</a>
				</p>
				<p>
					<a href="docs/CLFCbylaws.pdf" class="white"><img src="images/pdficon_large.png" alt="Bylaws" title="Bylaws" class="vert_middle" /> Bylaws</a>
				</p>
			</div>
		</div>
		<!-- END OF SIDE COLUMN -->
		
		<div id="content" class="float_left trans_white_bg2 shadow">

			<div id="post3">
				<h1 class="Windsor f24">National Co-Op Week</h1>
				<p class="f14 justify indent">
					October 13-19, 2013 is National Co-Op Week in Canada, and this year's theme is "A Better Way."
				</p>
				<p class="f14 justify indent">
					Co-operatives are a better way to do business, and a great alternative to traditional business models.  Find out more about co-ops at the <a href="http://www.coopscanada.coop/en/orphan/CoopWeek2013">Canadian Co-Operative Association</a> website, and read the <a href="http://www.coopscanada.coop/assets/firefly/files/files/PM_coopweek_message_2013.pdf">National Co-Op Week message from Canada's Prime Minister</a>.
				</p>
			</div>
			<!--New post-->
			<div id="post2" class="center">
				<h1 class="Windsor f24">Reasons to love your Local Food Co-Op!</h1>
				<p class="f14 justify indent">
					The stats in this video may be from the United States, but the reasons are just as valid!
				</p>
				<iframe width="560" height="315" src="//www.youtube.com/embed/BpG8wzx1qO0?rel=0" frameborder="0" allowfullscreen></iframe>
			</div>
			<br />
			<!--New post-->
			<div id="post1" class="center">
				<h1 class="Windsor f24">We're hard at work!</h1>
				<p class="f14 justify indent">
					It may not look like it on the surface, but there is a lot of work going on behind the scenes to make the Cloverbelt Local Food Co-Op ready to take your orders.  We're busy lining up producers, taking memberships, and getting this website ready to go.
				</p>
				<p class="f14 justify indent">
					We're just as excited as you are about this new opportunity to purchase locally produced food and products, but we want to be sure everything goes as smoothly as possible, so it may be a few weeks yet.  We appreciate your patience as we strive to create a Local Food Co-Op you'll be proud to say you're a member of!
				</p>
			</div>

			<!--New post-->
<!--			<div id="xxx" class="center">
				<h1 class="WindsorBold">xxx</h1>
				<p class="intoLight f18 bold justify">
					xxx
				</p>
			</div>
-->

<?php
/* THIS IS THE FOOTER SECTION */
?>
		</div>
		
		<!-- END OF CONTENT -->
		<div id="footer" class="trans_white_bg shadow">
			<br />
			<p class="center vert_middle Windsor f16">&copy;<?php echo date("Y", time()); ?> Cloverbelt Local Food Co-Op Inc.</p>
			<p class="center Windsor">66 Keith Ave. Unit 2<br />P.O. Box 668<br />Dryden, ON P8N 2Z3</p>
			<p class="center intoLight f18"><a href="mailto:membership@cloverbeltlocalfoodcoop.com">membership@cloverbeltlocalfoodcoop.com</a></p>
			<br />
		</div>
		<!-- END OF FOOTER -->
	</div>
	<!-- END OF WRAPPER -->
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-44446160-1', 'cloverbeltlocalfoodcoop.com');
  ga('send', 'pageview');

</script>
</body>
</html>