<?php

$wgSpecialPages[ 'LVASuche' ] = 'SimilarNamedArticles';

/**
 * Entry point
 */
function wfSpecialSimilarNamedArticles ($par) {
	global $wgOut;
	$page = new SimilarNamedArticles();
	$page->execute($par);
}

class SimilarNamedArticles extends SpecialPage
{
	function SimilarNamedArticles() {
		global $wgOut;
		self::loadMessages();

		// the output of similarnamedarticles_title will be what
		// the link points to. the lowercase version will be used
		// as displayed link-text
		SpecialPage::SpecialPage( wfMsg('similarnamedarticles_title') ); // this is where the link points to
	}

	function execute( $par ) {
		global $wgOut, $wgRequest;

		$this->setHeaders();

		if ($par)
		{
			$searchstring = str_replace('_',' ',$par);
			$output = $this->getSimilarNames($searchstring, true, true);
		} else {
			$output = wfMsg('noParamsGiven');
		}
		$wgOut->addWikiText($output);
	}

	function loadMessages() {
		static $messagesLoaded = false;
		global $wgMessageCache;
		if ( $messagesLoaded ) return;
		$messagesLoaded = true;

		require( dirname( __FILE__ ) . '/SimilarNamedArticles.i18n.php' );
		foreach ( $allMessages as $lang => $langMessages ) {
			$wgMessageCache->addMessages( $langMessages, $lang );
		}
	}

	/** getSimilarNames
	 * @param searchstring String:
	 *	  We will search for Articles where the title starts with this
	 *	string. Note that namespaces will be cut out.
	 * @param mayIncludeNamespace bool:
	 *	  Check if the above searchstring may include a namespace.
	 * @param FollowSingleArticle bool:
	 *	  If we want to redirect to an Article, when we find only one match.
	 * @return String if
	 *	  # no Articles are found
	 *	  # more than one article is found
	 *	  # one article is found and FollowSingleArticle = false
	 *	  if only one article is found and FollowSingleArticle = true, this will
	 *	  redirect us to that found page.
	 *
	 * This function is currently called by the above by the execute() function as
	 * well as a Hook in SimilarNamedArticlesHook.php
	 */
	static function getSimilarNames ($searchstring,
				$mayIncludeNamespace = true,
				$FollowSingleArticle = true)
	{
		require_once( 'SearchEngine.php' );
		global $wgSNA_Namespaces, $wgNamespacesToBeSearchedDefault, $wgSNA_includeSubpages,
			$wgNamespaceHomes, $wgSNA_addInfoNamespace, $wgSNA_addInfoCategories;
		global $wgContLang;
		$outtext = "";
		$categoryNSText = $wgContLang->getNSText ( NS_CATEGORY );

		# if an explicit Namespace was given in the query, we only search in
		# that Namespace AND strip the title of that Namespace

		# if a namespace was possibly given as parameter, we need it in
		# a seperate variable.
		if ( $mayIncludeNamespace && substr_count ($searchstring, ":") > 0 ) {
			$possible_namespace = preg_replace('/:.*/', '', $searchstring);
			$possible_namespace = preg_replace('/ /', '_', $possible_namespace);
		}

		# This is true if $title includes at least one ':' and the text before
		# the first ':' matches a defined namespace.
		if ( isset($possible_namespace) && ($namespaceID = ( Namespace::getCanonicalIndex ( strtolower("$possible_namespace") ) ) ) != 0 ) {
			# if title is "TU Wien:Bla" then namespaceID is ID of "TU Wien" Namespace.
			$NamespacesToSearch[] = $namespaceID;
			# now strip the namespace of the searchstring (or we won't find anything).
			$searchstring = preg_replace("/^[^:]*:/", "", $searchstring);
		}
		elseif ( isset($wgSNA_Namespaces) ) {
			foreach ($wgSNA_Namespaces as $key) {
				$NamespacesToSearch[] = $key;
			}  
		}
		elseif ( isset($wgNamespacesToBeSearchedDefault) ) {
			foreach ($wgNamespacesToBeSearchedDefault as $key => $value) {
				if ($value == true)
					$NamespacesToSearch[] = $key;
			}  
		}
		else {
			$NamespacesToSearch[] = NS_MAIN;
		}

		$search = SearchEngine::create();
		$search->setLimitOffset( 100, 0 );
		$search->setNamespaces( $NamespacesToSearch );
		$matches = $search->searchTitle( $searchstring );

		while( $row = $matches->next() ) {
			$addInfo = array ();
			$fulltitle = $row->getTitle();
			if ( $wgSNA_includeSubpages == false && $fulltitle->isSubpage() )
				continue;
			$namespace = $fulltitle->getNamespace(); # this is an ID
			$namespaceName = ereg_replace("_", " ", Namespace::getCanonicalName($namespace));
			$title = $fulltitle->getText();
			$categories = $fulltitle->getParentCategories();
			$fulltitle = $fulltitle->getPrefixedText(); // this becomes a string here.

			# we need to cover the case of a main namespace article
			if ( $namespace == NS_MAIN )
				$namespaceName = wfMsg ('nstab-main');
			
			# this check ensures that only Articles with the exact searchstring as
			# name are found. Otherwise all Articles with a name CONTAINING the
			# search string are found.
			if ( strpos ($title, $searchstring) === 0 )
			{
				# print list element plus actual link to page:
				$outtext .= "* [[" . $fulltitle . "|" . $title . "]]";
				
				# print the namespaces
				if ( $wgSNA_addInfoNamespace ) {
					if ( isset ($wgNamespaceHomes[$namespace]) && $wgNamespaceHomes[$namespace] !== '' )
						$addInfo[] = "[[" .  $wgNamespaceHomes[$namespace] . "|" . $namespaceName . "]]";
					else
						$addInfo[] = "$namespaceName";
				}
				
				# print the categories
				if ( is_array($wgSNA_addInfoCategories) && is_array ($categories) ) {
					if ( in_array("all", $wgSNA_addInfoCategories) ) {
						# found the "all" keyword
						foreach ($categories as $key => $value ) {
							$tempTitle = Title::newFromText($key);
							$addInfo[] = "[[:" . $key . "|" . $tempTitle->getText() . "]]";
						}
					} else {
						# no all keyword
						foreach ($categories as $key => $value) {
							$tempTitle = Title::newFromText($key);
							$pagename = $tempTitle->getText();
							
							// Do we have a defined home?
							if ( array_key_exists ( $pagename, $wgSNA_addInfoCategories ) ) {
								if ( $wgSNA_addInfoCategories[$pagename] == 1 )
									$addInfo[] = "[[:" . $key . "|" . $pagename . "]]";
								else
									$addInfo[] = "[[" . $wgSNA_addInfoCategories[$pagename] . "|" . $pagename . "]]";
							} elseif ( in_array ( $pagename, $wgSNA_addInfoCategories ) ) {
								$addInfo[] = "$pagename";
							}
						}
					}
				}
				
				$addInfoText = implode(", ", $addInfo);
				if ( $addInfoText )
					$outtext .= " ($addInfoText)";

				$outtext .= "\n";
				$final_title = $fulltitle;
			}
		}
	
		# note: since we always end $outtext with a \n, $array will always have one empty element at the end.
		$array=explode("\n", $outtext);
		$array_size = count($array) - 1;

		if ( $array_size == 0 ) {
			# No Articles found
			$output = wfMsg('no-articles-found');
		} else if ( $array_size == 1 && $FollowSingleArticle == true ) {
			# one Article found and we want to redirect
			global $wgOut;
			$redir_lva = Title::newFromText( $final_title );
			$wgOut->redirect($redir_lva->getFullURL());
		} else if ($array_size > 1 ) {
			# more than one article found.
			$output = wfMsg ('articles-found') . "\n";
			$output .= $outtext;
		}

		return $output;
	}
}
?>
