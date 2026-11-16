/*
 * Past Chairs History Timeline
 * Plain JavaScript. No jQuery, no build step.
 * Step 5: decade jump bar. Search and filters (step 6) and the dialog (step 8) are added below.
 */
( function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/**
	 * Decade jump bar: highlights the decade in view, scrolls to a decade on click,
	 * and hides decades that have no visible entries.
	 */
	function initJumpBar( root ) {
		var bar = root.querySelector( '[data-flhist-jump]' );
		if ( ! bar ) {
			return null;
		}
		var items = Array.prototype.slice.call( bar.querySelectorAll( '.flhist-jump__item' ) );
		var sections = Array.prototype.slice.call( root.querySelectorAll( '.flhist-decade' ) );
		var visible = {};
		var current = null;

		// Keep the bar height available to CSS so headings clear it when scrolled to.
		function measure() {
			root.style.setProperty( '--flhist-bar-height', bar.offsetHeight + 'px' );
		}

		function setCurrent( decade ) {
			if ( decade === current ) {
				return;
			}
			current = decade;
			items.forEach( function ( item ) {
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
			for ( var i = 0; i < sections.length; i++ ) {
				var decade = sections[ i ].getAttribute( 'data-decade' );
				if ( visible[ decade ] && ! sections[ i ].hidden ) {
					setCurrent( decade );
					return;
				}
			}
		}

		// The band near the top of the screen decides which decade is current.
		if ( 'IntersectionObserver' in window ) {
			var observer = new IntersectionObserver(
				function ( records ) {
					records.forEach( function ( record ) {
						visible[ record.target.getAttribute( 'data-decade' ) ] = record.isIntersecting;
					} );
					pickCurrent();
				},
				{ rootMargin: '-25% 0px -65% 0px' }
			);
			sections.forEach( function ( section ) {
				observer.observe( section );
			} );
		}

		bar.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a' );
			if ( ! link ) {
				return;
			}
			var heading = document.getElementById( link.getAttribute( 'href' ).slice( 1 ) );
			if ( ! heading ) {
				return;
			}
			event.preventDefault();
			heading.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' } );
			// Move focus to the heading so keyboard and screen reader users land there.
			var title = heading.querySelector( '.flhist-decade__title' );
			if ( title ) {
				title.setAttribute( 'tabindex', '-1' );
				title.focus( { preventScroll: true } );
			}
		} );

		measure();
		window.addEventListener( 'resize', measure );

		return {
			/** Hide bar links for decades with no visible entries. Call after filtering. */
			refresh: function () {
				sections.forEach( function ( section, i ) {
					var empty = ! section.querySelector( '.flhist-entry:not([hidden])' );
					section.hidden = empty;
					items[ i ].hidden = empty;
				} );
				measure();
				pickCurrent();
			},
		};
	}

	function init( root ) {
		var jump = initJumpBar( root );
		// Step 6 calls jump.refresh() after each filter change.
		root.flhist = { jump: jump };
	}

	function ready() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-flhist]' ), init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
}() );
