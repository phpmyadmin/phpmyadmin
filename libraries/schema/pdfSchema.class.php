<?php
include_once("relationSchema.abstract.class.php");
class pdfSchema extends exportRelationSchema
{
	
}
$pdf= new pdfSchema();
$pdf->createPageHTML();
?>