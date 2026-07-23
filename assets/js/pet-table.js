/**
 * Pet Table — client-side pagination.
 *
 * The Petstore API has no limit/offset, so the full result set is rendered and
 * paginated in the browser. Each widget instance paginates independently.
 */
( function () {
	'use strict';

	function paginate( root ) {
		if ( ! root || root.dataset.wppdInit === '1' ) {
			return;
		}
		root.dataset.wppdInit = '1';

		var perPage = parseInt( root.getAttribute( 'data-rows-per-page' ), 10 );
		if ( isNaN( perPage ) || perPage < 1 ) {
			perPage = 10;
		}

		var rows = Array.prototype.slice.call(
			root.querySelectorAll( 'tbody .wppd-row' )
		);
		var nav = root.querySelector( '.wppd-pagination' );

		// Nothing to paginate — leave everything visible, hide the nav.
		if ( rows.length <= perPage || ! nav ) {
			if ( nav ) {
				nav.hidden = true;
			}
			return;
		}

		var pageCount = Math.ceil( rows.length / perPage );
		var current = 1;

		var prevBtn = nav.querySelector( '.wppd-prev' );
		var nextBtn = nav.querySelector( '.wppd-next' );
		var statusEl = nav.querySelector( '.wppd-page-status' );

		function render() {
			var start = ( current - 1 ) * perPage;
			var end = start + perPage;

			rows.forEach( function ( row, i ) {
				row.style.display = i >= start && i < end ? '' : 'none';
			} );

			if ( statusEl ) {
				statusEl.textContent = 'Page ' + current + ' of ' + pageCount;
			}
			if ( prevBtn ) {
				prevBtn.disabled = current === 1;
			}
			if ( nextBtn ) {
				nextBtn.disabled = current === pageCount;
			}
		}

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				if ( current > 1 ) {
					current--;
					render();
				}
			} );
		}
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				if ( current < pageCount ) {
					current++;
					render();
				}
			} );
		}

		nav.hidden = false;
		render();
	}

	function initAll( scope ) {
		var context = scope && scope.querySelectorAll ? scope : document;
		var tables = context.querySelectorAll( '.wppd-pet-table[data-rows-per-page]' );
		Array.prototype.forEach.call( tables, paginate );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	// Re-init inside Elementor's live editor preview when a widget is (re)drawn.
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
				window.elementorFrontend.hooks.addAction(
					'frontend/element_ready/wppd_pet_table.default',
					function ( $scope ) {
						initAll( $scope && $scope[ 0 ] ? $scope[ 0 ] : document );
					}
				);
			}
		} );
	}
} )();
