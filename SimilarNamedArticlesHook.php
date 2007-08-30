<?php

$wgHooks['OutputPageBeforeHTML'][] = array('SNA_aboveArticle');

function SNA_aboveArticle( $output_page, $qText)
{
	global $wgTitle, $wgParser, $wgRequest;
	global $wgSNA_Namespaces, $wgSNA_SearchEnable, $wgSNA_aboveArticleEnable,
		$wgSNA_aboveArticleNamespaces, $wgSNA_aboveSubpages;
	$ns = $wgTitle->getNamespace();
	$output = ""; // init

	if ( $wgRequest->getVal('action') == '' )
		return;
	if ( ! $wgSNA_aboveSubpages && $wgTitle->isSubpage() )
		return;

	# check if there is anything to be done.
	if ( ( isset ($wgSNA_aboveArticleNamespaces) && ! in_array($ns, $wgSNA_aboveArticleNamespaces) )
		|| !$wgSNA_SearchEnable || !$wgSNA_aboveArticleEnable ) {
		return;
	}
	if ( ! isset ($wgSNA_aboveArticleNamespaces) )
		$wgSNA_aboveArticleNamespaces = array ( 0 );
	
	# we need to call loadMessages befor the call getSimilarNames,
	# otherwise the Translation will not work (here).
	SimilarNamedArticles::loadMessages();

	$title = $wgTitle->getText();

	# This is custimized for our needs. The List of SimilarNamedArticles
	# that is displayed above each article should be created from a
	# searchstring that is NOT the entire title (it would always only find
	# itself) but rather only the first part (in our case only 'til the
	# regular expression).
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
