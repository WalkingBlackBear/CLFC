<?php

function your_prefix_nq_append_posts($quotes, $customQuoteCount, $totalLinks, $totalExcerpts, $totalQuotes){
	$more_quotes = array(
		"Quote 1",
		"Quote 2",
		"Quote 3"	
	);
	return array_merge($more_quotes,$quotes);
}

add_filter("get_append_plugin_quotes", "your_prefix_nq_append_posts");

?>