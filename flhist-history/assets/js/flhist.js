/*
 * Past Chairs History Timeline
 * Plain JavaScript. No jQuery, no build step.
 * Step 5: decade jump bar. Step 6: search, filters, sort. The entry dialog (step 8) is added below.
 */
( function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function all( parent, selector ) {
		return Array.prototype.slice.call( parent.querySelectorAll( selector ) );
	}

	/* ------------------------------------------------------------ Jump bar */

	/**
	 * Highlights the decade in view, scrolls to a decade on click, and hides
	 * decades that have no visible entries.
	 */
	function initJumpBar( root ) {
		var bar = root.querySelector( '[data-flhist-jump]' );
		if ( ! bar ) {
			return null;
		}
		var visible = {};
		var current = null;

		function sections() {
			return all( root, '.flhist-decade' ); // Always in current page order, so sorting works.
		}

		function itemFor( decade ) {
			return bar.querySelector( '.flhist-jump__item[data-decade="' + decade + '"]' );
		}

		// Keep the bar height available to CSS so headings clear it when scrolled to.
		function measure() {
			root.style.setProperty( '--flhist-bar-height', bar.offsetHeight + 'px' );
		}

		function setCurrent( decade ) {
			if ( decade === current ) {
				return;
			}
			current = decade;
			all( bar, '.flhist-jump__item' ).forEach( function ( item ) {
				var link = item.firstElementChild;
				if ( item.getAttribute( 'data-decade' ) === decade ) {
					link.setAttribute( 'aria-current', 'true' );
					keepInView( item );
				} else {
					link.removeAttribute( 'aria-current' );
				}
			} );
		}

		// Scroll the bar sideways (never the page) so the current decade stays visible.
		function keepInView( item ) {
			var list = item.parentElement;
			if ( list.scrollWidth <= list.clientWidth ) {
				return;
			}
			var target = item.offsetLeft - ( list.clientWidth - item.offsetWidth ) / 2;
			if ( list.scrollTo ) {
				list.scrollTo( { left: target, behavior: reduceMotion ? 'auto' : 'smooth' } );
			} else {
				list.scrollLeft = target;
			}
		}

		function pickCurrent() {
			var list = sections();
			for ( var i = 0; i < list.length; i++ ) {
				var decade = list[ i ].getAttribute( 'data-decade' );
				if ( visible[ decade ] && ! list[ i ].hidden ) {
					setCurrent( decade );
					return;
				}
			}
			if ( current && ( ! itemFor( current ) || itemFor( current ).hidden ) ) {
				all( bar, '[aria-current]' ).forEach( function ( link ) {
					link.removeAttribute( 'aria-current' );
				} );
				current = null;
			}
		}

		// A thin band just below the sticky header and the bar decides which decade is current.
		var observer = null;
		function observe() {
			if ( ! ( 'IntersectionObserver' in window ) ) {
				return;
			}
			if ( observer ) {
				observer.disconnect();
			}
			var offset = parseFloat( window.getComputedStyle( root ).getPropertyValue( '--flhist-sticky-offset' ) ) || 0;
			var viewport = window.innerHeight;
			var top = Math.min( offset + bar.offsetHeight + 24, viewport * 0.6 );
			var bottom = Math.max( viewport - top - Math.max( 60, viewport * 0.1 ), 0 );
			observer = new IntersectionObserver(
				function ( records ) {
					records.forEach( function ( record ) {
						visible[ record.target.getAttribute( 'data-decade' ) ] = record.isIntersecting;
					} );
					pickCurrent();
				},
				{ rootMargin: '-' + Math.round( top ) + 'px 0px -' + Math.round( bottom ) + 'px 0px' }
			);
			sections().forEach( function ( section ) {
				observer.observe( section );
			} );
		}

		bar.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a' );
			if ( ! link ) {
				return;
			}
			var target = document.getElementById( link.getAttribute( 'href' ).slice( 1 ) );
			if ( ! target ) {
				return;
			}
			event.preventDefault();
			target.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' } );
			// Move focus to the heading so keyboard and screen reader users land there.
			var title = target.querySelector( '.flhist-decade__title' );
			if ( title ) {
				title.setAttribute( 'tabindex', '-1' );
				title.focus( { preventScroll: true } );
			}
		} );

		measure();
		observe();
		window.addEventListener( 'resize', function () {
			measure();
			observe();
		} );

		return {
			/** Hide bar links and decade sections that have no visible entries. Call after filtering. */
			refresh: function () {
				var anyShown = false;
				sections().forEach( function ( section ) {
					var empty = ! section.querySelector( '.flhist-entry:not([hidden])' );
					var item = itemFor( section.getAttribute( 'data-decade' ) );
					section.hidden = empty;
					if ( item ) {
						item.hidden = empty;
					}
					anyShown = anyShown || ! empty;
				} );
				bar.hidden = ! anyShown;
				measure();
				pickCurrent();
			},
			measure: measure,
		};
	}

	/* ------------------------------------------------------------ Search and filters */

	// Lowercase, no accents, single spaces.
	function normalize( text ) {
		var out = String( text ).toLowerCase();
		if ( out.normalize ) {
			out = out.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' );
		}
		return out.replace( /\s+/g, ' ' ).trim();
	}

	function initFilters( root, jump ) {
		var toolbar = root.querySelector( '[data-flhist-toolbar]' );
		var entries = all( root, '.flhist-entry' );
		var timeline = root.querySelector( '[data-flhist-timeline]' );
		var none = root.querySelector( '[data-flhist-none]' );
		var clears = all( root, '[data-flhist-clear]' );
		var qInput = toolbar && toolbar.querySelector( '[data-flhist-q]' );
		var typeSelect = toolbar && toolbar.querySelector( '[data-flhist-type]' );
		var decadeSelect = toolbar && toolbar.querySelector( '[data-flhist-decade]' );
		var status = toolbar && toolbar.querySelector( '[data-flhist-status]' );
		var sortButton = toolbar && toolbar.querySelector( '[data-flhist-sort]' );
		var timer = null;

		if ( ! toolbar || ! timeline ) {
			return;
		}

		function message( count ) {
			var text = 1 === count ? toolbar.getAttribute( 'data-text-one' ) : toolbar.getAttribute( 'data-text-many' );
			return text.replace( '%d', count );
		}

		// A word matches if the entry text contains it, or if it is a year inside the entry's range.
		function matches( entry, words ) {
			var blob = entry.getAttribute( 'data-search' ) || '';
			var start = parseInt( entry.getAttribute( 'data-year-start' ), 10 );
			var end = parseInt( entry.getAttribute( 'data-year-end' ), 10 ) || start;
			return words.every( function ( word ) {
				if ( blob.indexOf( word ) !== -1 ) {
					return true;
				}
				if ( /^\d{4}$/.test( word ) ) {
					var year = parseInt( word, 10 );
					return year >= start && year <= end;
				}
				return false;
			} );
		}

		function apply() {
			var q = normalize( qInput.value );
			var words = q ? q.split( ' ' ) : [];
			var type = typeSelect.value;
			var decade = decadeSelect.value;
			var shown = 0;

			entries.forEach( function ( entry ) {
				var ok = ( ! type || entry.getAttribute( 'data-type' ) === type ) &&
					( ! decade || entry.getAttribute( 'data-decade' ) === decade ) &&
					( ! words.length || matches( entry, words ) );
				entry.hidden = ! ok;
				if ( ok ) {
					shown++;
				}
			} );

			var active = !! ( q || type || decade );
			clears.forEach( function ( button ) {
				// The button inside the empty message stays visible with that message.
				if ( button.closest( '[data-flhist-none]' ) ) {
					return;
				}
				button.hidden = ! active;
			} );
			none.hidden = shown > 0;
			timeline.hidden = 0 === shown;
			status.textContent = message( shown ) + ( sortButton ? ', ' + sortLabel().toLowerCase() : '' );
			if ( jump ) {
				jump.refresh();
			}
			writeUrl( qInput.value.trim(), type, decade );
		}

		/* Keep the filters in the address so a result can be shared. */
		function writeUrl( q, type, decade ) {
			if ( ! window.history || ! window.history.replaceState || ! window.URLSearchParams ) {
				return;
			}
			var params = new URLSearchParams( window.location.search );
			[ [ 'q', q ], [ 'type', type ], [ 'decade', decade ] ].forEach( function ( pair ) {
				if ( pair[ 1 ] ) {
					params.set( pair[ 0 ], pair[ 1 ] );
				} else {
					params.delete( pair[ 0 ] );
				}
			} );
			var query = params.toString();
			window.history.replaceState( null, '', window.location.pathname + ( query ? '?' + query : '' ) + window.location.hash );
		}

		function readUrl() {
			if ( ! window.URLSearchParams ) {
				return;
			}
			var params = new URLSearchParams( window.location.search );
			qInput.value = params.get( 'q' ) || '';
			setSelect( typeSelect, params.get( 'type' ) );
			setSelect( decadeSelect, params.get( 'decade' ) );
		}

		// Ignore a value that is not one of the options.
		function setSelect( select, value ) {
			if ( value && select.querySelector( 'option[value="' + value.replace( /["\\]/g, '' ) + '"]' ) ) {
				select.value = value;
			}
		}

		function clearAll() {
			qInput.value = '';
			typeSelect.value = '';
			decadeSelect.value = '';
			apply();
			qInput.focus();
		}

		/* Sort: reverse the decades, the entries in each decade, and the jump bar. */
		function sortLabel() {
			return 'desc' === root.getAttribute( 'data-order' ) ? toolbar.getAttribute( 'data-text-newest' ) : toolbar.getAttribute( 'data-text-oldest' );
		}

		function reverseChildren( parent ) {
			all( parent, ':scope > *' ).reverse().forEach( function ( child ) {
				parent.appendChild( child );
			} );
		}

		function toggleSort() {
			root.setAttribute( 'data-order', 'desc' === root.getAttribute( 'data-order' ) ? 'asc' : 'desc' );
			reverseChildren( timeline );
			all( timeline, '.flhist-list' ).forEach( reverseChildren );
			var barList = root.querySelector( '.flhist-jump__list' );
			if ( barList ) {
				reverseChildren( barList );
			}
			sortButton.textContent = sortLabel();
			status.textContent = message( entries.filter( function ( entry ) { return ! entry.hidden; } ).length ) + ', ' + sortLabel().toLowerCase();
			if ( jump ) {
				jump.refresh();
			}
		}

		/* Events */
		qInput.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( apply, 150 );
		} );
		typeSelect.addEventListener( 'change', apply );
		decadeSelect.addEventListener( 'change', apply );
		clears.forEach( function ( button ) {
			button.addEventListener( 'click', clearAll );
		} );
		if ( sortButton ) {
			sortButton.addEventListener( 'click', toggleSort );
		}
		// Enter in the search box must not submit the form.
		toolbar.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
		} );

		readUrl();
		if ( qInput.value || typeSelect.value || decadeSelect.value ) {
			apply();
		}
	}

	function init( root ) {
		var jump = initJumpBar( root );
		initFilters( root, jump );
		root.flhist = { jump: jump };
	}

	function ready() {
		all( document, '[data-flhist]' ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
}() );
