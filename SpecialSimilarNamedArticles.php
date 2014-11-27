<?php

/**
 * Entry point
 */
function efRunSimilarNamedArticles( $par ) {
	global $wgOut;
	$page = new SimilarNamedArticles();
	$page->execute( $par );
}

class SimilarNamedArticles extends SpecialPage
{
    function __construct() {
        parent::__construct('SimilarNamedArticles');
    }

	/**
	 * main worker-function...
	 */
	function execute( $par ) {
		global $wgOut, $wgSimilarNamedArticlesEnable;

		if ( ! $wgSimilarNamedArticlesEnable )
			return;

		if ( $par ) {
			$wgOut->setPagetitle(
				wfMessage('similarnamedarticles_title', wfMessage('similarnamedarticles')->text(), $par)->text()
			);
			$title = Title::newFromtext( $par );
			$ns = $title->getNamespace();
			$explicitNS = false;
			if ( $ns == 0 ) {
				if ( strpos( $par, ':' ) === 0 )
					$explicitNS = 0;
			} else
				$explicitNS = $ns;
			$wgOut->addWikiText( $this->getSimilarNames( $title, $explicitNS ) );
		} else {
			$wgOut->setPagetitle(wfMessage('similarnamedarticles')->text());
			$wgOut->addWikiText(wfMessage('noParamsGiven')->text());
			return;
		}
	}

	/**
	 * this function does find all pages with the given prefix.
	 * return: string in wikiformat.
	 *
	 * Note that this function sometimes never returns: if only one
	 * 	result is found and $followSingleArticle = true.
	 */
	public function getSimilarNames( $title, $explicitNS, $followSingleArticle = true ) {
		$output = "";
		$titleArray = $this->searchForPrefix( $title, $explicitNS );
		$noOfResults = count( $titleArray );

		switch ( $noOfResults ) {
			case 0:
				// only true if called from special page:
				if ( $followSingleArticle )
					return wfMessage('noResults')->text();
				else
					return '';
				break;
			case 1:
				if ( $followSingleArticle ) {
					global $wgOut;
					$wgOut->redirect( $titleArray[0]->getFullURL() );
					return ''; /* never reached */
				} else {
					return '';
				}
				break;
			default:
				$output .= wfMessage('manyResults', $noOfResults)->text();
				foreach ( $titleArray as $title ) {
					$output .= "\n* " . $this->getResultString( $title );
				}
				return $output;
				break;
		}

		return $output;
	}

	function searchForPrefix( $title, $explicitNS ) {
		global $wgSimilarNamedArticlesNamespaces, $wgNamespacesToBeSearchedDefault;
		global $wgSimilarNamedArticlesIncludeSubpages, $wgSimilarNamedArticlesIncludeRedirects;

		# search in these namespaces:
		if ( $explicitNS or $explicitNS === 0 )
			$nsToBeSearched = array( $explicitNS => 1 );
		elseif ( $wgSimilarNamedArticlesNamespaces )
			$nsToBeSearched = $wgSimilarNamedArticlesNamespaces;
		else
			$nsToBeSearched = $wgNamespacesToBeSearchedDefault;

		$includeSubpages = $wgSimilarNamedArticlesIncludeSubpages;

		$result = array();
		#$prefix = $title->getPrefixedDBkey();
		#$prefixList = SpecialAllpages::getNamespaceKeyAndText($namespace, $prefix);
		#list( $namespace, $prefixKey, $prefix ) = $prefixList;
        $prefixKey = $title->getDBkey();

		$dbr = wfGetDB( DB_SLAVE );
		$db_conditions = array(
				'page_namespace' => array_keys( $nsToBeSearched, true ),
				'page_title ' . $dbr->buildLike($prefixKey, $dbr->anyString()),
				'page_title >= ' . $dbr->addQuotes( $prefixKey ),
				);
		if ( ! $wgSimilarNamedArticlesIncludeRedirects )
			$db_conditions[] = 'page_is_redirect=0';

		$res = $dbr->select( 'page',
				array( 'page_namespace', 'page_title', 'page_is_redirect' ),
				$db_conditions,
				"SimilarNamedArticles::searchForPrefix",
				array(
					'ORDER BY'  => 'page_title',
					'USE INDEX' => 'name_title',
				     )
				);

		while ( $row = $dbr->fetchObject( $res ) ) {
			$nsString = MWNamespace::getCanonicalName( $row->page_namespace );
			$resultTitle = Title::newFromDBKey( $nsString . ":" .$row->page_title );
			if ( $includeSubpages == false ) {
				if ( $resultTitle->isSubpage() ) {
					$basePage = Title::newFromText(
						$resultTitle->getBaseText(),
						$resultTitle->getNamespace()
					);

					if ( $basePage->exists() ) {
						continue;
					}
				}
			}

			$result[] = $resultTitle;
		}

		return $result;
	}

	function getResultString( $title ) {
		global $wgNamespaceHomes;
		global $wgSimilarNamedArticlesAddInfoNamespaces;
		global $wgSimilarNamedArticlesAddInfoCategories, $wgSimilarNamedArticlesAddInfoResources;
		global $wgResourcesEnable; // foreign extension!

		$result = '[[' . $title->getPrefixedText() . '|' . $title->getText() . ']]';
		$addInfo = array();

		# add info Namespace:
		if ( $wgSimilarNamedArticlesAddInfoNamespaces ) {
			# ns 0 needs special treatment:
			if ( $title->getNamespace() == 0 )
				$ns = wfMessage('nstab-main')->text();
			else
				$ns = $title->getNsText();

			$text = preg_replace( '/_/', ' ', $ns );
			if ( is_array( $wgNamespaceHomes ) &&
				array_key_exists( $title->getNamespace(),
					$wgNamespaceHomes ) )
				$text = '[[' . $wgNamespaceHomes[$title->getNamespace()] .
					'|' . $text . ']]';
			$addInfo[] = $text;
		}

		if ( $wgSimilarNamedArticlesAddInfoCategories ) {
			$categories = array();
			$tempCat = $title->getParentCategories();
			foreach ( $tempCat as $key => $value ) {
				$tmp = Title::newFromText( $key );
				$categories[] = $tmp->getText();
			}

			if ( $wgSimilarNamedArticlesAddInfoCategories == 'all' ) {
				foreach ( $categories as $value ) {
					$addInfo[] = '[[:' . wfMessage('nstab-category')->text() . ":" . $value . '|' . $value . ']]';
				}
			} elseif ( is_array($wgSimilarNamedArticlesAddInfoCategories ) ) {
				foreach ( $categories as $value) {
					if ( in_array( $value, $wgSimilarNamedArticlesAddInfoCategories ) )
						$addInfo[] = '[[:' . wfMessage('nstab-category')->text() . ":" . $value . '|' . $value . ']]';
				}
			}
		}

		if ( $wgSimilarNamedArticlesAddInfoResources && $wgResourcesEnable ) {
			$resourcesPage = new SpecialResources();
			$resourcesCount = $resourcesPage->getResourceListCount( $title );
			$addInfo[] = '[[' . MWNamespace::getCanonicalName(NS_SPECIAL) . ':' .
				wfMessage('resources')->text() . '/' . $title->getPrefixedText() .
				'|' . $resourcesCount . ' ' . wfMessage('resources')->text() . ']]';
		}

		if ( count( $addInfo ) > 0 ) {
			$result .= ' (' . implode( ', ', $addInfo ) . ')';
		}

		return $result;
	}
}
?>
