<?php
//-----------------------------
//vSoftware.org Modifications
//	(C)2007 vsoftware.org
//-----------------------------


//-----------------
//Example Use File
//-----------------
//Get the class
require('sources/templates.php');
//Define class
$template = new template;


//Parse a Template
//with a variable
$message = 'This is a template message.';

$template->parse('example','section',array(
		"title"		=>	"Message",
		"message"	=>	$message));
//-----------------------------------------------------
//Description
//-----------------------------------------------------
//$template->parse - calls the function
//'example' is the file in the skin folder for example 'example.tpl.php'
//'section' is the section in the tpl file ex $templates['section'] = 'tpldata';
//array is the tags to be replaced in the template {title} will become Message
		
//Output the template to the browser.
$template->output();

?>