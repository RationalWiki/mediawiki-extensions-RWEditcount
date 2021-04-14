<?php

use MediaWiki\MediaWikiServices;

class SpecialEditcount extends SpecialPage {

	var $target, $cutoff, $month, $year;

	function __construct() {
		parent::__construct( 'Editcount' );
	}

	protected function getGroupName() {
		return 'users';
	}

	function execute( $par ) {
		global $wgRequest, $wgOut;
		if ( wfReadOnly() ) {
			throw new ReadOnlyError;
		}

		$this->target = $wgRequest->getVal( 'name', $par );
		$this->target = strtr( $this->target, '_', ' ' );
		$this->cutoff = $wgRequest->getIntOrNull( 'cutoff' );
		$this->month = $wgRequest->getVal( 'month', '__' );
		$this->year = $wgRequest->getVal( 'year', '____' );

		if ( $this->target ) {
			$this->cutoff = null;
		}

		$this->setHeaders();

		$mAll = wfMessage( 'editcount-all' )->escaped();
		$currentYear = gmdate( 'Y' );

		$yearoptions = '';

		for ( $i = $currentYear; $i >= 2007; -- $i ) {
			$yearoptions .= "<option value=\"$i\" " . ( ( $this->year == $i ) ? "selected" : "" ) .
				">$i</option>\n";
		}

		$titleObject = $this->getPageTitle();
		$wgOut->addHTML( Xml::openElement( 'form', array(
				'id' => 'editcount',
				'method' => 'post',
				'action' => $titleObject->getLocalURL( "action=submit" ),
			) ) . Xml::openElement( 'fieldset' ) .
			Xml::element( 'legend', null, wfMessage( 'editcountlegend' )->text() ) .
			Xml::label( wfMessage( 'editcount-username' )->text(), 'name' ) . "&nbsp;" .
			Xml::input( 'name', 45, $this->target, array(
					'tabindex' => '1',
					'id' => 'name',
				) ) . "<p>" . Xml::label( wfMessage( 'editcount-month' )->text(), 'month' ) .
			"&nbsp;" .
			"<select id=\"month\" name=\"month\" tabindex=\"2\">
				<option value=\"__\">{$mAll}</option>
				<option value=\"01\" " . ( ( $this->month == '01' ) ? 'selected' : '' ) . ">01</option>
				<option value=\"02\" " . ( ( $this->month == '02' ) ? 'selected' : '' ) . ">02</option>
				<option value=\"03\" " . ( ( $this->month == '03' ) ? 'selected' : '' ) . ">03</option>
				<option value=\"04\" " . ( ( $this->month == '04' ) ? 'selected' : '' ) . ">04</option>
				<option value=\"05\" " . ( ( $this->month == '05' ) ? 'selected' : '' ) . ">05</option>
				<option value=\"06\" " . ( ( $this->month == '06' ) ? 'selected' : '' ) . ">06</option>
				<option value=\"07\" " . ( ( $this->month == '07' ) ? 'selected' : '' ) . ">07</option>
				<option value=\"08\" " . ( ( $this->month == '08' ) ? 'selected' : '' ) . ">08</option>
				<option value=\"09\" " . ( ( $this->month == '09' ) ? 'selected' : '' ) . ">09</option>
				<option value=\"10\" " . ( ( $this->month == '10' ) ? 'selected' : '' ) . ">10</option>
				<option value=\"11\" " . ( ( $this->month == '11' ) ? 'selected' : '' ) . ">11</option>
				<option value=\"12\" " . ( ( $this->month == '12' ) ? 'selected' : '' ) . ">12</option>
			</select> " .
			Xml::label( wfMessage( 'editcount-year' )->text(), 'year' ) .
			"&nbsp;
			<select id=\"year\" name=\"year\" tabindex=\"3\">
				<option value=\"____\">{$mAll}</option>
				{$yearoptions}
			</select>
		</p><p> " . Xml::label( wfMessage( 'editcount-returntop' )->text(), 'month' ) . "&nbsp;" .
			Xml::input( 'cutoff', 1, $this->cutoff, array(
					'tabindex' => '4',
					'id' => 'cutoff',
					'maxlength' => '3',
				) ) . "
		</p><p> " . Xml::submitButton( wfMessage( 'editcount-go' )->text(), array(
					'name' => 'editcount_go',
					'tabindex' => '5',
					'accesskey' => 's',
				) ) . "
		</p>" . Xml::closeElement( 'fieldset' ) . Xml::closeElement( 'form' ) );

		$dbr = wfGetDB( DB_REPLICA );
		$like = $this->sanitizeLike( $dbr, "{$this->year}{$this->month}" );
		$totallabel = wfMessage( 'editcount-total' )->text();
		$actorMigration = MediaWikiServices::getInstance()->getActorMigration();
		if ( $this->target ) {
			$actorQueryInfo = $actorMigration->getWhere( $dbr, 'rev_user',
				User::newFromName( $this->target ) );
			$conds = array(
				$actorQueryInfo['conds'],
				'rev_timestamp ' . $like,
			);
			$res = $dbr->select(
				array( 'page', 'revision' ) + $actorQueryInfo['tables'],
				array( 'page_namespace', 'count(page_namespace)' ),
				$conds,
				'SpecialEditcount::execute',
				array( 'GROUP BY' => 'page_namespace' ),
				array( 'revision' => array( 'JOIN', 'page_id=rev_page' ) ) + $actorQueryInfo['joins'] );
			$total = 0;
			$data = array();
			foreach ( $res as $row ) {
				$total += $row['count(page_namespace)'];
				$data[] = array(
					'ns' => $row['page_namespace'],
					'count' => $row['count(page_namespace)'],
				);
			}
			$res->free();
			$this->month = $this->month == '__' ? '*' : $this->month;
			$this->year = $this->year == '____' ? '*' : $this->year;
			$wgOut->addHTML( "<table>
		<tr>
			<td style='padding-right:4em;'>" . htmlspecialchars( $this->target ) . "</td>
			<td style='padding-right:1em;'>" . htmlspecialchars( "{$this->month}/{$this->year}" ) . "</td>
			<td>$totallabel&nbsp;{$total}</td>
		</tr>
		" );
			global $wgCanonicalNamespaceNames;
			for ( $i = 0; $i < count( $data ); ++ $i ) {
				$ns = htmlspecialchars( ( $data[$i]['ns'] == NS_MAIN )
					? wfMessage( 'blanknamespace' )->text()
					: strtr( $wgCanonicalNamespaceNames[$data[$i]['ns']], '_', ' ' ) );
				$num = $data[$i]['count'];
				$perc = round( $num / $total * 100, 2 );
				$wgOut->addHTML( "
			<tr>
			<td>{$ns}</td>
				<td>{$num}</td>
				<td>{$perc}%</td>
			</tr>
			" );
			}
			$wgOut->addHTML( '</table>' );
		} elseif ( $this->cutoff ) {
			$actorQueryInfo = $actorMigration->getJoin( 'rev_user' );
			$res = $dbr->select(
				[ 'revision' ] + $actorQueryInfo['tables'],
				$actorQueryInfo['fields'] + array(
					'count(rev_timestamp)'
				),
				array( 'rev_timestamp ' . $like ),
				__METHOD__,
				array(
					'GROUP BY' => $actorQueryInfo['fields']['rev_user_text'],
					'ORDER BY' => 'count(rev_timestamp) desc',
					'LIMIT' => $this->cutoff,
				),
				$actorQueryInfo['joins']
				);
			$wgOut->addHTML( "<table>" );
			$mmonth = $this->month == '__' ? '*' : $this->month;
			$myear = $this->year == '____' ? '*' : $this->year;
			foreach ( $res as $row ) {
				$name = $row['rev_user_text'];
				$total = $row['count(rev_timestamp)'];
				$userlink = Xml::openElement( 'a', array(
						'href' => $titleObject->getLocalURL( array(
							'name' => $name,
							'year' => $this->year,
							'month' => $this->month,
						) ),
					) ) . htmlspecialchars( $name ) . Xml::closeElement( 'a' );
				$wgOut->addHTML( "
		<tr>
			<td style='padding-right:4em;'>{$userlink}</td>
			<td style='padding-right:1em;'>{$mmonth}/{$myear}</td>
			<td>$totallabel&nbsp;" . htmlspecialchars( $total ) . "</td>
		</tr>
		" );
			}
			$wgOut->addHTML( "</table>" );
		}
	}

	/**
	 * @param Wikimedia\Rdbms\DBConnRef $db
	 * @param string $input
	 * @return string
	 */
	protected function sanitizeLike( $db, $input ) {
		$parts = array();
		$pos = 0;
		while ( $pos < strlen( $input ) ) {
			$literalLength = strcspn( $input, '_', $pos );
			if ( $literalLength > 0 ) {
				$parts[] = substr( $input, $pos, $literalLength );
			}
			$pos += $literalLength;
			while ( isset( $input[$pos] ) && $input[$pos] === '_' ) {
				$parts[] = $db->anyChar();
				$pos ++;
			}
		}
		$parts[] = $db->anyString();

		return call_user_func_array( array( $db, 'buildLike' ), $parts );
	}
}

class ApiActiveusers extends ApiQueryBase {
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'ac' );
	}

	public function execute() {
		$db = $this->getDB();
		$params = $this->extractRequestParams();

		$month = $params['month'];
		if ( !is_null( $month ) ) {
			$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
		}

		$year = $params['year'];
		$limit = $params['limit'];
		$this->addTables( 'revision' );

		//"rev_timestamp like \"{$this->year}{$this->month}%\""

		if ( !is_null( $year ) && !is_null( $month ) ) {
			$this->addWhere( 'rev_timestamp' .
				$this->getDB()->buildLike( "{$year}{$month}", $this->getDB()->anyString() ) );
		} elseif ( !is_null( $year ) ) {
			$this->addWhere( 'rev_timestamp' .
				$this->getDB()->buildLike( "{$year}", $this->getDB()->anyString() ) );
		} elseif ( !is_null( $month ) ) {
			$this->addWhere( 'rev_timestamp' .
				$this->getDB()->buildLike( $this->getDB()->anyChar(), $this->getDB()->anyChar(),
					$this->getDB()->anyChar(), $this->getDB()->anyChar(), $month,
					$this->getDB()->anyString() ) );
		}

		$actorMigration = MediaWikiServices::getInstance()->getActorMigration();
		$actorQueryInfo = $actorMigration->getJoin( 'rev_user' );
		$this->addTables( $actorQueryInfo['tables'] );
		$this->addJoinConds( $actorQueryInfo['joins'] );
		$this->addFields( $actorQueryInfo['fields'] + array( 'count(rev_timestamp)' ) );
		$this->addOption( 'GROUP BY', $actorQueryInfo['fields']['rev_user_text'] );
		$this->addOption( 'ORDER BY', "count(rev_timestamp) desc" );
		$this->addOption( 'LIMIT', "{$limit}" );

		$res = $this->select( __METHOD__ );
		$result = $this->getResult();

		$data = array();

		foreach ( $res as $row ) {
			$data[] =
				array(
					'name' => $row['rev_user_text'],
					'editcount' => $row['count(rev_timestamp)'],
				);
		}
		$db->freeResult( $res );

		$result->setIndexedTagName( $data, 'u' );
		$result->addValue( 'query', $this->getModuleName(), $data );


	}

	public function getAllowedParams() {
		return array(
			'month' => null,
			'year' => null,
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'month' => 'Get contributions for this month only',
			'year' => 'Get contributions for this year only',
			'limit' => 'How many total user names to return.',
		);
	}

	public function getDescription() {
		return 'Enumerate active users';
	}

	public function getExamples() {
		return array(
			'api.php?action=query&list=activeusers&aclimit=150',
			'api.php?action=query&list=activeusers&aclimit=150&acmonth=03&acyear=2009',
		);
	}

	public function getVersion() {
		return "1.0";
	}
}

