<?php

$wgHooks['OutputPageBeforeHTML'][] = array('SNA_aboveArticle');

function SNA_aboveArticle( $output_page, $qText)
{
	SimilarNamedArticles::loadMessages();
	global $wgTitle, $wgParser;
	global $wgSNA_Namespaces, $wgSNA_SearchEnable, $wgSNA_aboveArticleEnable;
	$ns = $wgTitle->getNamespace();
	$output = "";

	# check if there is anything to be done.
	if ( ( !isset ($wgSNA_Namespaces[$ns]) || $wgSNA_Namespaces[$ns] === true )  || !$wgSNA_SearchEnable || !$wgSNA_aboveArticleEnable )
		return;

	$title = $wgTitle->getText();

	# This is custimized for our needs. We only want this substition of SNA
	# is displayed above an Article. You could do a similar thing in the
	# function getSimilarNames if you want such a substition to always
	# happen.
	$searchstring = preg_replace ( '/ (VO|VU|VL|VD|UE|LU|PS|SE|PR|AR|AG|KO|KU).*/', '', $title);

	# this gets the text that is actually prepended.
	$output .= SimilarNamedArticles::getSimilarNames($searchstring, false, false);

	$parserOutput = $wgParser->parse( "$output\n", $wgTitle, $output_page->parserOptions(),
			true, true, $output_page->mRevisionId );
	$output_page->addParserOutputNoText( $parserOutput );

	# you could invert this to append the text instead of prepending it.
	$qText =  $parserOutput->getText() . $qText;
}

?>
