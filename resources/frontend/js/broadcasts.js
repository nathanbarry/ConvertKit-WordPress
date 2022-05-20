/**
 * Frontend functionality for subscribers and tags.
 *
 * @since   1.9.6
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Register events
 */
jQuery( document ).ready(

	function( $ ) {

		$( document ).on(
			'click',
			'ul.convertkit-broadcasts-pagination a',
			function( e ) {

				e.preventDefault();

				// Get block container and build object of data-* attributes.
				var blockContainer = $( this ).closest( 'div.convertkit-broadcasts' ),
					atts = {
						date_format: $( blockContainer ).data( 'date-format' ),
						limit: $( blockContainer ).data( 'limit' ),
						paginate: $( blockContainer ).data( 'paginate' ),
						paginate_label_prev: $( blockContainer ).data( 'paginate-label-prev' ),
						paginate_label_next: $( blockContainer ).data( 'paginate-label-next' ),

						page: $( this ).data( 'page' ), // Page is supplied as a data- attribute on the link clicked, not the container.
						nonce: $( this ).data( 'nonce' ) // Nonce is supplied as a data- attribute on the link clicked, not the container.
					};

				convertKitBroadcastsRender( blockContainer, atts );

			}
		); 

	}

);

/**
 * Sends an AJAX request to request HTML based on the supplied block attributes,
 * when pagination is used on a Broadcast block.
 *
 * @since 	1.9.7.6
 * 
 * @param 	object 	blockContainer 	DOM object of the block to refresh the HTML in.
 * @param 	object 	atts 			Block attributes
 */
function convertKitBroadcastsRender( blockContainer, atts ) {

	( function( $ ) {

		// Append action.
		atts.action = convertkit_broadcasts.action;

		if ( convertkit_broadcasts.debug ) {
			console.log( 'convertKitBroadcastsRender()' );
			console.log( atts );
		}

		// Show loading indicator.
		// @TODO.

		$.ajax(
			{
				url:        convertkit_broadcasts.ajax_url,
				type:       'POST',
				async:      true,
				data:      	atts,
				error: function( a, b, c ) {
					// @TODO Handle data.
					console.log( a );
					console.log( b );
					console.log( c );
				},
				success: function( result ) {
					if ( convertkit_broadcasts.debug ) {
						console.log( result );
					}

					// Replace block container's HTML with response data.
					$( blockContainer ).html( result.data );
				}
			}
		);

	} )( jQuery );

}
