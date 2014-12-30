<?php
// Uncomment the following section to include as a notification message.

$page_specific_css .= '';
$cuser = wp_get_current_user();
$login_message = '
  <h3>
    Welcome to the Cloverbelt Local Food Co-Op, '.$cuser->user_firstname.' '.$cuser->user_lastname.'!
  </h3><br />

  <p style="width:65%;padding:10px;margin-left:auto;margin-right:auto;margin-top:-10px;background-color:#800;border:2px solid #ffe;box-shadow:0 0 0 5px #800, 5px 5px 10px 5px #222;font-size:18px;text-align:center;color:#ffe;-webkit-transform:rotate(7deg);-moz-transform:rotate(7deg);-o-transform:rotate(7deg);transform:rotate(7deg);">
    <span style="font-style:italic;font-size:150%">Remember...</span><br>You are obligated to buy whatever is in your basket when ordering closes.  PLEASE do not leave any products in your basket you don\'t intend to buy.<br />There is no &quot;checkout&quot; process.</strong></p>
  <h1 class="f20">PLEASE NOTE!!</h1>
  <p>When adding items to your shopping basket, pay attention to the "# in basket" that appears below the basket and +/- image to the left of the product.  The number in the "View Basket" link at the top of the screen will not automatically update.  The number in the product list will.<br /><br /><br /></p>

  <p><strong>The Cloverbelt Local Food Co-operative aims to strengthen food security by encouraging diverse local food production, thereby enhancing overall rural sustainability.</strong></p>

  <p><br />We strive to foster a thriving local food community by:
  <ul style="margin-left:20px">
    <li>cultivating & facilitating farmer-consumer relationships</li>
    <li>promoting the enjoyment of naturally grown, fairly priced, healthy food</li>
    <li>providing education & resources regarding environmentally sensitive agriculture.</li>
  </ul></strong></p>
';
?>