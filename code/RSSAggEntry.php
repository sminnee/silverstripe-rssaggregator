<?php

class RSSAggEntry extends DataObject {
	static $has_one = array(
		"Page" => "RSSAggregatingPage",
		"Source" => "RSSAggSource",		
	);
	
	static $db = array(
		"Displayed" => "Boolean",
		"Date" => "SSDatetime",
		"Title" => "Varchar(255)",
		"Content" => "HTMLText",
		"Permalink" => "Varchar(255)",
		"EnclosureURL" => "Varchar(255)",
	);
	
	static $casting = array(
		"PlainContentSummary" => "Text",
	);
	
	/*
	 * Set new feed item to true if feed moderation is turned off
	 */
	public function populateDefaults() { 
		parent::populateDefaults();
		if ( !RSSAggregatingPage::get_moderation_required() ) {
			$this->Displayed = true;
		}
	}
	
	
	function getPlainContentSummary() {
		$content = trim(
			strip_tags(
				ereg_replace("&#[0-9]+;", " ", 
					str_replace(array("<p>","<br/>","<br />", "<br>"), array("\n\n","\n","\n","\n"), 
						ereg_replace("[\t\r\n ]+", " ", $this->Content)
					)
				)
			)
		);
		
		$parts = explode("\n\n", $content, 2);
		return $parts[0];
	}
	
	function isNews() {
		if($source = DataObject::get_by_id('SiteTree', $this->PageID)) {
			if($source->URLSegment == 'aggregated-news') {
				return true;
			}
		}
	}
	
	function Image() {
		if($this->isNews()) {
			$img = new NewsArticle_ArticleImage();
		} else {
			$img = new EventPage_Image();
		}
		$img->Filename = $this->EnclosureURL;
		return $img;
	}

	function getDateNice() {
		return $this->obj('Date')->Nice();
	}
	
	function Link() {
		return $this->Permalink;
	}
	
	function Permalink() {
		return str_replace('&amp;', '&', $this->Permalink);
	}

	// These functions are included for improved compatability with SiteTree
	function MenuTitle() {
		return $this->Title;
	}
	
	function LinkOrCurrent() {
		return "link";
	}
	function LinkingMode() {
		return "link";
	}
}

?>
