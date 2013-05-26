<?php
# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install SimilarNamedArticles, put the following line in LocalSettings.php:
require_once( "$IP/extensions/SimilarNamedArticles/SimilarNamedArticles.php" );
EOT;
	exit( 1 );
}

$wgAutoloadClasses['SimilarNamedArticles'] = __DIR__ . '/SpecialSimilarNamedArticles.php';
$wgExtensionMessagesFiles['SimilarNamedArticles'] = __DIR__ . '/SimilarNamedArticles.i18n.php';
$wgExtensionMessagesFiles['SimilarNamedArticlesAlias'] = __DIR__ . '/SimilarNamedArticles.alias.php';
$wgSpecialPages[ 'SimilarNamedArticles' ] = 'SimilarNamedArticles';

$wgExtensionCredits['specialpage'][] = array (
    'path' => __FILE__,
	'name' => 'SimilarNamedArticles',
	'author' => 'Mathias Ertl',
	'url' => 'https://fs.fsinf.at/wiki/SimilarNamedArticles',
	'description' => 'Finds articles where the name starts with a given prefix',
	'version' => '2.1.5-1.21.0',
);

?>
