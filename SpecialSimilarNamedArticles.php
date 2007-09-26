<?php

/**
 * Entry point
 */
function wfSpecialSimilarNamedArticles( $par ) {
	global $wgOut;
	$page = new SimilarNamedArticles();
	$page->execute($par);
}

class SimilarNamedArticles extends SpecialPage
{
	function SimilarNamedArticles() {
		self::loadMessages();
		SpecialPage::SpecialPage( "SimilarNamedArticles" );
	}

	/**
	 * main worker-function...
	 */
	function execute( $par ) {
		global $wgSimilarNamedArticlesEnable;
		global $wgOut;
		$this->setHeaders();

		if ( ! $wgSimilarNamedArticlesEnable )
			return;

		if ( $par ) {
			$title = Title::newFromtext( $par );
			$ns = $title->getNamespace();
			if ( $ns == 0 ) {
				if ( strpos( $par, ':' ) === 0 ) 
					$explicitNS = 0;
			} else
				$explicitNS = $ns;
			$wgOut->addWikiText( $this->getSimilarNames( $title, $explicitNS ) );
		} else {
			$wgOut->addWikiText( wfMsg('noParamsGiven') );
			return;
		}
	}

	function loadMessages() {
		static $messagesLoaded = false;
		global $wgMessageCache;
		if ( $messagesLoaded )
			return true;
		$messagesLoaded = true;

		require( dirname( __FILE__ ) . '/SimilarNamedArticles.i18n.php' );
		foreach ( $allMessages as $lang => $langMessages ) {
			$wgMessageCache->addMessages( $langMessages, $lang );
		}
		return true;
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
					return wfMsg('noResults');
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
				$output .= wfMsg( 'manyResults', $noOfResults );
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
		$prefix = $title->getPrefixedDBkey();
		$prefixList = SpecialAllpages::getNamespaceKeyAndText($namespace, $prefix);
		list( $namespace, $prefixKey, $prefix ) = $prefixList;

		$dbr = wfGetDB( DB_SLAVE );
		$db_conditions = array(
				'page_namespace' => array_keys( $nsToBeSearched, true ),
				'page_title LIKE \'' . $dbr->escapeLike( $prefixKey ) .'%\'',
				'page_title >= ' . $dbr->addQuotes( $prefixKey ),
				);
		if ( ! $wgSimilarNamedArticlesIncludeRedirects )
			$db_conditions[] = 'page_is_redirect=0';

		$res = $dbr->select( 'page',
				array( 'page_namespace', 'page_title', 'page_is_redirect' ),
				$db_conditions,
				$fname,
				array(
					'ORDER BY'  => 'page_title',
					'USE INDEX' => 'name_title',
				     )
				);

		while ( $row = $dbr->fetchObject( $res ) ) {
			$nsString = Namespace::getCanonicalName( $row->page_namespace );
			$resultTitle = Title::newFromDBKey( $nsString . ":" .$row->page_title );
			if ( $includeSubpages == false && $resultTitle->isSubpage() )
				continue;
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
				$ns = wfMsg ('nstab-main');
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
					$addInfo[] = '[[:' . wfMsg('nstab-category') . ":" . $value . '|' . $value . ']]';
				}
			} elseif ( is_array($wgSimilarNamedArticlesAddInfoCategories ) ) {
				foreach ( $categories as $value) {
					if ( in_array( $value, $wgSimilarNamedArticlesAddInfoCategories ) )
						$addInfo[] = '[[:' . wfMsg('nstab-category') . ":" . $value . '|' . $value . ']]';
				}
			}
		}

		if ( $wgSimilarNamedArticlesAddInfoResources && $wgResourcesEnable ) {
			$resourcesPage = new Resources();
			$resourcesCount = $resourcesPage->getResourceListCount( $title );
			$addInfo[] = '[[' . Namespace::getCanonicalName(NS_SPECIAL) . ':' . 
				wfMsg('resources') . '/' . $title->getPrefixedText() .
				'|' . $resourcesCount . ' ' . wfMsg('resources') . ']]';
		}
		
		if ( count( $addInfo ) > 0 ) {
			$result .= ' (' . implode( ', ', $addInfo ) . ')';
		}

		return $result;
	}
}
?>
