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

require_once "defines.php";
error_reporting( ERROR_LEVEL );
header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" >
<head>
	<title>First paragraph readability from category</title>
	<link rel="stylesheet" type="text/css" href="default.css" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body>
<h1>First paragraph readability from category</h1>
<form method="GET" id="main-form">
	<label for="catName">Insert category name (without "Category:" prefix):</label>
	<input type="text" name="catName" id="catName" placeholder="Animals" required
		value="<?php echo isset( $_GET['catName'] ) ? htmlspecialchars( $_GET['catName'] ) : '';?>"/>
	<button type="submit">Get results!</button>
</form>
<br />
<?php require "process.php"; ?>
<br />
<button id="help-collapsible">What do these scores mean?</button>
<div id="help-content">
	<p>These scores are generated using the Automated Readability Index (ARI), a simple but effective readability test.
	While ARI was specifically designated for English texts, it's only based on word, letters and sentences count,
	which makes it yield plausible results for other languages, too.</p>
	<p>The score returned by ARI indicates the U.S. grade level required to understand the text. A higher score
	means the text is complex, while a low (or even negative) score means that the text is easy to read.</p>
	<?php
		if ( NO_TEXT_AS_ZERO ) {
			echo "<p>A score equal to 0 means that the page doesn't have an introductory section.</p>";
		}
	?>
	<p>A table with the meaning of each score can be found <a href="https://en.wikipedia.org/wiki/Automated_readability_index">
	on Wikipedia</a>.</p>
</div>
<br />
<footer><p>Made with &#10084; by Emanuele</p></footer>
<script defer src="dynamic.js" ></script>
</body>
</html>
