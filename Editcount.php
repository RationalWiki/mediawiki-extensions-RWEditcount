<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$wgExtensionCredits['specialpage'][] = array(
  'name' => 'RationalWiki Editcount Query',
  'author' => '[http://rationalwiki.com/wiki/User:Tmtoulouse Trent Toulouse], [http://rationalwiki.com/wiki/User:Nx Nx]',
  'url' => 'http://rationalwiki.com/',
  'description' => 'Makes it easier to get edit counts for users'
);

$wgSpecialPages['Editcount'] = 'SpecialEditcount';

# register api query module
$wgAPIListModules['activeusers'] = 'ApiActiveusers';

# include path
$wgEditcountIP = dirname( __FILE__ );
$wgExtensionMessagesFiles['Editcount'] = "$wgEditcountIP/Editcount.i18n.php";

# Load classes
$wgAutoloadClasses['SpecialEditcount'] =  "$wgEditcountIP/Editcount.body.php";
$wgAutoloadClasses['ApiActiveusers'] =  "$wgEditcountIP/Editcount.body.php";

//Avoid unstubbing $wgParser on setHook() too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'editcountinit';
} else { // Otherwise do things the old fashioned way
	$wgExtensionFunctions[] = 'editcountinit';
}

function editcountinit() {
  global $wgParser;
  wfLoadExtensionMessages('Editcount');
  return true;
}
