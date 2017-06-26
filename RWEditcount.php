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
$wgExtensionMessagesFiles['Editcount'] = "$wgEditcountIP/RWEditcount.i18n.php";

# Load classes
$wgAutoloadClasses['SpecialEditcount'] =  "$wgEditcountIP/RWEditcount.body.php";
$wgAutoloadClasses['ApiActiveusers'] =  "$wgEditcountIP/RWEditcount.body.php";

