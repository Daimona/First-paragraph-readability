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

/**
 * Add basic client-side validation and help toggle
 */
( function () {
	$( function() {
		var $form = $( '#main-form' ),
			cat = document.getElementById( 'catName' ),
			helpButton = document.getElementById( 'help-collapsible' );

		if ( typeof cat.setCustomValidity === 'undefined' ) {
			// Not supported only by Opera Mini. Not critical, just skip validation.
			return;
		}

		$form.on( 'keyup', function() {
			let catInvalid = (/[#<>\[\]|{}:]/).test( cat.value );

			cat.setCustomValidity( catInvalid ? 'Invalid category name.' : ''  );
		} );

		helpButton.addEventListener( 'click', function() {
			this.classList.toggle( 'active' );
			$( '#help-content' ).toggle();
		} );
	} );
}());
