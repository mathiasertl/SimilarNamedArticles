<?php
# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/MyExtension/MyExtension.php" );
EOT;
	exit( 1 );
}

$wgAutoloadClasses['SimilarNamedArticles'] = dirname(__FILE__) . '/SpecialSimilarNamedArticles.php';
$wgSpecialPages[ 'SimilarNamedArticles' ] = 'SimilarNamedArticles';
$wgHooks['LoadAllMessages'][] = 'SimilarNamedArticles::loadMessages';
$wgHooks['LangugeGetSpecialPageAliases'][] = 'SNA_LocalizedPageName';

function SNA_LocalizedPageName( &$specialPageArray, $code) {
	SimilarNamedArticles::loadMessages();
	$text = wfMsg('similarnamedarticles');
 
	# Convert from title in text form to DBKey and put it into the alias array:
	$title = Title::newFromText( $text );
	$specialPageArray['SimilarNamedArticles'][] = $title->getDBKey();
 
	return true;
}

?>
