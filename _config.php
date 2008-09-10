<?php
// number of feed items to display
global $number_of_items_list;
$number_of_items_list = array (
	"5" => "5",
	"10" => "10",
	"15" => "15",
	"20" => "20",
	"25" => "25",
	"30" => "30"
	);

// set feed moderation on(true) or off(false)
RSSAggregatingPage::set_moderation_required( false );
?>