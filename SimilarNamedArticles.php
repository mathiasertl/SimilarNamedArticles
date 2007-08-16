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
$wgHooks['LoadAllMessages'][] = 'SimilarNamedArticles::loadMessages';

switch ( $wgLanguageCode ) {
	case 'en':
		$wgSpecialPages[ 'SimilarNamedArticles' ] = 'SimilarNamedArticles';
		break;
	case 'de':
		$wgSpecialPages[ 'LVASuche' ] = 'SimilarNamedArticles';
		break;
	default:
		$wgSpecialPages[ 'SimilarNamedArticles' ] = 'SimilarNamedArticles';
		break;
}

require_once(dirname(__FILE__) . '/SimilarNamedArticlesHook.php');

?>
