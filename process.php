<?php
/*
 * First paragraph readability
 *
 * Copyright (C) 2019  E. L. (https://meta.wikimedia.org/wiki/User:Daimona_Eaytoy)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/** Driver code */
if ( !isset( $_GET[ 'catName' ] ) ) {
	// Request not sent
	return;
}

$CONFIG = require "config.php";

try {
	processInput( $_GET[ 'catName' ] );
} catch ( Exception $e ) {
	echo '<p class="error">' . htmlspecialchars( $e->getMessage() ) . '</p>';
	return;
}

/**
 * Main routine: validate input, process it and output result
 * @param string $catName Unprefixed
 * @throws Exception
 */
function processInput( $catName ) {
	global $CONFIG;

	$wikiURL = $CONFIG[ 'wiki_url' ] ?? 'en.wikipedia.org';
	if ( !validateWiki( $wikiURL ) ) {
		throw new Exception( 'Invalid wiki URL: ' . htmlspecialchars( $wikiURL ) . '.' );
	}
	if ( !validateName( $catName ) ) {
		// Perform some simple validation to avoid sending a request with an obviously invalid title.
		throw new Exception( 'Invalid category name: ' . htmlspecialchars( $catName ) . '.' );
	}

	$apiUrl = "https://$wikiURL/w/api.php";

	$data = getPageData( $apiUrl, $catName );
	// Sort from less readable to most readable
	arsort( $data );
	displayData( $data );
}

/**
 * @param string $wiki The URL of the wiki
 * @return bool
 */
function validateWiki( $wiki ) {
	$re = '!^[a-z]{2,4}\.wikipedia\.org$!';
	return (bool)preg_match( $re, $wiki );
}

/**
 * @param string $name
 * @return bool
 */
function validateName( $name ) {
	$re = '/[#<>\[\]|{}:]/';
	return !preg_match( $re, $name );
}

/**
 * @param string $url
 * @param string $name
 * @throws Exception
 * @return array
 */
function getPageData( $url, $name ) {
	global $CONFIG;

	$ns = $CONFIG[ 'include_namespaces' ] ?? [ 0 ];
	if ( $ns !== array_filter( $ns, 'is_numeric' ) ) {
		throw new Exception( 'The specified "include_namespaces" option is invalid.' );
	}
	$queryBits = [
		'action' => 'query',
		'format' => 'json',
		'prop' => 'extracts',
		'exintro' => 1,
		'explaintext' => 1,
		'generator' => 'categorymembers',
		'gcmtitle' => "Category: $name"
	];

	if ( $ns ) {
		$queryBits['gcmnamespace'] = implode( '|', $ns );
	}

	$ret = [];
	$remaining = RESULT_LIMIT;
	do {
		// Extracts API only allows retrieving 20 pages at a time
		$limit = min( $remaining, 20 );
		$queryBits['gcmlimit'] = $limit;
		$queryBits['exlimit'] = $limit;
		$remaining -= $limit;

		$query = "$url?" . http_build_query( $queryBits );
		$res = makeRequest( $query );

		if ( isset( $res->error ) ) {
			throw new Exception( 'API error: ' . $res->error->info );
		} elseif ( isset( $res->warnings ) ) {
			$warnings = reset( $res->warnings );
			throw new Exception( 'API error: ' . reset( $warnings ) );
		} elseif ( !isset( $res->query->pages ) ) {
			break;
		}

		$ret += getBatchReadability( $res );

		// We consider the request finished as soon as the API returns less results than
		// we asked, or we don't want more.
		$finished = count( get_object_vars( $res->query->pages ) ) < $limit || $remaining <= 0;
		if ( isset( $res->continue ) ) {
			$queryBits = array_merge( $queryBits, get_object_vars( $res->continue ) );
		}

		usleep( MAXLAG_WAIT_SEC * 10e5 );
	} while ( !$finished );

	return $ret;
}

/**
 * Perform an API request, either via cURL (if available) or file_get_contents
 *
 * @param string $query
 * @return mixed
 */
function makeRequest( $query ) {
	switch ( REQUEST_TYPE ) {
		case 'native':
			return json_decode( file_get_contents( $query ) );
		case 'curl':
			$curl = curl_init( $query );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_HEADER, false );
			$result = json_decode( curl_exec( $curl ) );
			curl_close( $curl );
			return $result;
		default:
			throw new Exception( 'No request type specified.' );
	}
}

/**
 * Get a readability score for each page in $data
 *
 * @param stdClass $data
 * @return array
 */
function getBatchReadability( stdClass $data ) {
	$ret = [];
	foreach ( $data->query->pages as $el ) {
		if ( !isset( $el->extract ) || $el->extract === '' ) {
			if ( NO_TEXT_AS_ZERO ) {
				$ret[ $el->title ] = 0;
			} else {
				continue;
			}
		} else {
			$ret[ $el->title ] = getTextReadability( $el->extract );
		}
	}
	return $ret;
}

/**
 * Main routine which gets a readability score for the text. This makes use of the
 * Automated Readability Index (ARI), see https://en.wikipedia.org/wiki/Automated_readability_index.
 *
 * @param string $text The text to check.
 * @return float
 */
function getTextReadability( $text ) {
	$str = cleanText( $text );
	$letterCount = preg_match_all( '/\p{L}/u', $str );
	$wordCount = preg_match_all( '/[^\p{L}\p{N}]+/u', $str );
	// This can be tricked by abbreviations, but it's pretty difficult to identify all of them
	$sentenceCount = substr_count( $str, '.' );

	if ( $letterCount * $wordCount * $sentenceCount === 0 ) {
		// Avoid division by zero
		return round( 0, SCORE_PRECISION );
	}
	$rawScore = 4.71 * ( $letterCount / $wordCount ) + 0.5 * ( $wordCount / $sentenceCount ) - 21.43;

	return round( $rawScore, SCORE_PRECISION );
}

/**
 * Remove extra punctuation and spaces, make all sentences end with a period.
 *
 * @todo Add some more punctuation
 * @param string $str
 * @return string
 */
function cleanText( $str ) {
	// Remove periods from numbers to avoid counting extra sentences
	$str = preg_replace( '/(\p{N})\.(\p{N})/u', '$1$2', $str );
	// Replace punctuation with spaces.
	$str = preg_replace( '/[",:;()\/`\'-]/', ' ', $str );
	// Make all sentences end with a period.
	$str = preg_replace( '/[\.!?]/', '.', $str );
	// Aggregate periods
	$str = preg_replace( '/([\.\s]*\.[\.\s]*)/', '. ', $str );
	// Make the text end with a period
	$str = trim( $str, '. ' ) . '.';
	// Strip extra whitespace
	$str = preg_replace( '/\s\s+/', ' ', $str );
	return $str;
}

/**
 * Display all data in an ordered list
 *
 * @param array $data
 */
function displayData( array $data ) {
	global $CONFIG;

	echo '<p>Found ', count( $data ), ' pages.</p><ol>';
	$url = $CONFIG['wiki_url'];
	$prec = SCORE_PRECISION;
	$template = "<li><b><a href='https://$url/wiki/%s'>%s</a></b>: <span class='%s'>%.{$prec}f</span></li>";

	foreach ( $data as $title => $readability ) {
		$class = $readability >= 14 ? 'score-bad' : 'score-good';
		echo sprintf(
			$template,
			htmlspecialchars( rawurlencode( $title ) ),
			htmlspecialchars( $title ),
			$class,
			$readability
		);
	}
	echo "</ol>";
}
