/**
 *		js module:
 *			rotate_html.js
 *
 *		desc:
 *			Rotates evenly through multiple CTA and 800 numbers in header.
 *
 *		requires:
 *			jQuery, json2
 */
/*
jQuery( document ).ready( function( $ ) {	
	var nonce = 'arh_ajax.nonce';
	category = $( '.arh_rotate_html' ).data( 'category' );

	$.ajax({
	    url: arh_ajax.url,
	    dataType:'html',
	    data: ( {action:'arh_rotate_html', category:category, nonce:nonce} ),
	    success: function( html ) {
	    	$( '.arh_rotate_html' ).html( html );
	    	$( '.arh_rotate_html' ).css( 'visibility', 'visible' );
	    	var $html = $( '.arh_rotate_html span a' );
	    	var phone_num = $( '.arh_rotate_html span a' ).html();
	    	PH.phone_number_button.replace_phone_number( $html, phone_num );
	    },
	    error: function( jqXHR, textStatus, errorThrown ) {
	    	$( '.arh_rotate_html' ).html( errorThrown );
	    }
	});
});
*/