<?

class RSSAggSource extends DataObject {
	static $has_one = array(
		"Page" => "RSSAggregatingPage",
	);
	
	static $db = array(
		"Title" => "Varchar(255)",
		"RSSFeed" => "Varchar(255)",
		"LastChecked" => "SSDatetime",
	);
	
	static $singular_name = 'RSS Source';
	
	static $plural_name = 'RSS Sources';
}

?>