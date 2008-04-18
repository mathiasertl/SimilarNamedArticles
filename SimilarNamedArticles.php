<?php
# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/SimilarNamedArticles/SimilarNamedArticles.php" );
EOT;
	exit( 1 );
}

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['SimilarNamedArticles'] = $dir . 'SimilarNamedArticles_body.php';
$wgExtensionMessagesFiles['SimilarNamedArticles'] = $dir . 'SimilarNamedArticles.i18n.php';
$wgSpecialPages[ 'SimilarNamedArticles' ] = 'SimilarNamedArticles';
$wgHooks['LanguageGetSpecialPageAliases'][] = 'similarNamedArticlesLocalizedPageName';

$wgExtensionCredits['specialpage'][] = array (
	'name' => 'SimilarNamedArticles',
	'description' => 'Finds articles where the name starts with a given prefix',
	'version' => '2.1.3-1.12.0',
	'author' => 'Mathias Ertl',
	'url' => 'http://pluto.htu.tuwien.ac.at/devel_wiki/SimilarNamedArticles',
);

function similarNamedArticlesLocalizedPageName( &$specialPageArray, $code ) {
	wfLoadExtensionMessages( 'SimilarNamedArticles' );
	$text = wfMsg('similarnamedarticles');
 
	# Convert from title in text form to DBKey and put it into the alias array:
	$title = Title::newFromText( $text );
	$specialPageArray['SimilarNamedArticles'][] = $title->getDBKey();
 
	return true;
}

?>
