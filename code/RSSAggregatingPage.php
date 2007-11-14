<?

class RSSAggregatingPage extends Page {
	static $has_many = array(
		"SourceFeeds" => "RSSAggSource",
		"Entries" => "RSSAggEntry"
	);
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		
		if(!$_REQUEST[executeForm]) $this->updateRSS();
		
		$fields->addFieldToTab("Root.Content.Sources", new TableField("SourceFeeds", "RSSAggSource",
			array(
				"RSSFeed" => "RSS Feed URL",
				"Title" => "Feed Title"
			),
			array(
				"RSSFeed" => "TextField",
				"Title" => "ReadonlyField",
			), "PageID", round($this->ID), true, "RSSFeed"
		));

		// Insert Items as the first tab
		$fields->addFieldToTab("Root.Content", new Tab("Items"), "Main");
		$fields->addFieldToTab("Root.Content.Items", $entries = new TableField("Entries", "RSSAggEntry",
			array(
				"Displayed" => "Show",
				"DateNice" => "Date",
				"SourceTitle" => "Source",
				"Title" => "Title",
			),
			array(
				"Displayed" => "CheckboxField",
				"DateNice" => "ReadonlyField",
				"SourceTitle" => "ReadonlyField",
				"Title" => "ReadonlyField",
			), "PageID", round($this->ID), true, "Date DESC"
		));
		$entries->setPermissions(array("edit"));
			
		return $fields;
	}
	
	/**
	 * Use SimplePie to get all the RSS feeds and agregate them into Entries
	 */
	function updateRSS() {
		if(!is_numeric($this->ID)) return;
		
		foreach($this->SourceFeeds() as $sourceFeed) {
			$goodSourceIDs[] = $sourceFeed->ID;
			
			if($_REQUEST['flush'] || strtotime($sourceFeed->LastChecked) < time() - 3600) {
				$simplePie = new SimplePie($sourceFeed->RSSFeed);
				/*$simplePie->enable_caching(false);
				$simplePie->enable_xmldump(true);*/
				$simplePie->init();
			
				$sourceFeed->Title = Convert::xml2raw($simplePie->get_feed_title());
				$sourceFeed->LastChecked = date('Y-m-d H:i:s');
				$sourceFeed->write();
				
				$goodIDs = array();
			
				$items = $simplePie->get_items();
				if(!$items) user_error("RSS Error: $simplePie->error", E_USER_WARNING);
				
				if($items) foreach($items as $item) {
					$entry = new RSSAggEntry();
					$entry->Permalink = $item->get_permalink();
					$entry->Date = $item->get_date('Y-m-d H:i:s');
					$entry->Title = $item->get_title();
					$entry->Content = str_replace(array(
						'&nbsp;',
						'&lsquo;',
						'&rsquo;',
						'&ldquo;',
						'&rdquo;',
						'&amp;'
					), array(
						'&#160;',
						"'",
						"'",
						'"',
						'"',
						'&'
					), $item->get_description());
					$entry->PageID = $this->ID;
					$entry->SourceID = $sourceFeed->ID;
					
					if($enclosure = $item->get_enclosure()) {
						$entry->EnclosureURL = $enclosure->get_link();
					}
				
					$SQL_permalink = Convert::raw2sql($entry->Permalink);
					$existingID = DB::query("SELECT ID FROM RSSAggEntry WHERE Permalink = '$SQL_permalink' AND SourceID = $entry->SourceID AND PageID = $entry->PageID")->value();
				
					if($existingID) $entry->ID = $existingID;
					$entry->write();
				
					$goodIDs[] = $entry->ID;
				}
				if($goodIDs) {
					$list_goodIDs = implode(', ', $goodIDs);
					$idClause = "AND ID NOT IN ($list_goodIDs)";
				}
				DB::query("DELETE FROM RSSAggEntry WHERE SourceID = $sourceFeed->ID AND PageID = $this->ID $idClause");
			}
		}
		if($goodSourceIDs) {
			$list_goodSourceIDs = implode(', ', $goodSourceIDs);
			$sourceIDClause = " AND SourceID NOT IN ($list_goodSourceIDs)";
		}		
		DB::query("DELETE FROM RSSAggEntry WHERE PageID = $this->ID $sourceIDClause");
	}
	
	function Children() {
		$this->updateRSS();
		
		// Tack the RSS feed children to the end of the children provided by the RSS feed
		$c1 = DataObject::get("SiteTree", "ParentID = '$this->ID'");
		$c2 = DataObject::get("RSSAggEntry", "PageID = $this->ID AND Displayed = 1", "Date ASC");
		
		if($c1) {
			if($c2) $c1->append($c2);
			return $c1;
		} else {
			return $c2;
		}
	}
	
}

class RSSAggregatingPage_Controller extends Page_Controller {
	
}

?>