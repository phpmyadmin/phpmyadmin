<?php
// Using Abstract Factory Pattern for exporting relational Schema in different Formats !
abstract class exportRelationSchema
{
	private $pageTitle; // Title of the page
	private  $autoLayoutType; // Internal or Foreign Key Relations;
	
	public function setPageTitle($title)
	{
		$this->pageTitle=$title;
	}
	
	public function createPage()
	{
		
	}
}
?>