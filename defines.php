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

// Value to pass to error_reporting
define( 'ERROR_LEVEL', 0 );
// Seconds to wait between API requests
define( 'MAXLAG_WAIT_SEC', 0.2 );
// Whether to use cURL ('curl') or file_get_contents ('native')
define( 'REQUEST_TYPE', extension_loaded( 'curl' ) ? 'curl' : 'native' );
// How many pages to retrieve
define( 'RESULT_LIMIT', 50 );
// Round precision for scores, specified as the amount of decimal digits
define( 'SCORE_PRECISION', 1 );
// If set to true, pages which don't have an introductory section are reported as having
// a score of 0, otherwise are omitted.
define( 'NO_TEXT_AS_ZERO', true );
