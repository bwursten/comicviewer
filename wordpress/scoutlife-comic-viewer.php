<?php
/**
 * Plugin Name:  Scout Life Comic Viewer
 * Description:  Adds a [comic] shortcode that embeds the guided comic panel viewer.
 * Version:      1.0.0
 * Author:       Scout Life
 * License:      GPL-2.0-or-later
 *
 * Install as a must-use plugin: drop this file in wp-content/mu-plugins/.
 * It activates automatically and cannot be switched off by accident in wp-admin.
 *
 * Usage in a post or page:
 *
 *   [comic page="peewee-sep2026"]
 *   [comic page="peewee-sep2026" title="Pee Wee Harris Looks at Music"]
 *   [comic page="sia-sep2026" ratio="4/5" width="640"]
 *
 * The `page` attribute is the JSON filename without its extension, resolved
 * against SLCV_COMICS_PATH below.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Where the viewer lives, root-relative so staging and production both work.
 * Change these two lines if you move the files.
 */
if ( ! defined( 'SLCV_VIEWER_URL' ) ) {
	define( 'SLCV_VIEWER_URL', '/comics/viewer.html' );
}
if ( ! defined( 'SLCV_COMICS_PATH' ) ) {
	define( 'SLCV_COMICS_PATH', 'pages' ); // relative to viewer.html
}

/** Default aspect ratio of the embed box, width/height. */
if ( ! defined( 'SLCV_DEFAULT_RATIO' ) ) {
	define( 'SLCV_DEFAULT_RATIO', '5/6' );
}

/** Default maximum width in pixels. */
if ( ! defined( 'SLCV_DEFAULT_WIDTH' ) ) {
	define( 'SLCV_DEFAULT_WIDTH', 820 );
}

add_shortcode( 'comic', 'slcv_render_comic' );

/**
 * Render the comic viewer embed.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML, or an empty string if the shortcode is misconfigured.
 */
function slcv_render_comic( $atts ) {

	$a = shortcode_atts(
		array(
			'page'    => '',                       // required: JSON basename
			'title'   => '',                       // iframe title, for screen readers
			'ratio'   => SLCV_DEFAULT_RATIO,       // "5/6", "4/5", "1/1" …
			'width'   => SLCV_DEFAULT_WIDTH,       // max width in px
			'caption' => '',                       // optional caption under the embed
			'align'   => 'center',                 // center | left | right | wide
		),
		$atts,
		'comic'
	);

	// A page slug is the one thing we cannot guess. Restrict it to characters that
	// are safe in a path segment so a typo can never walk out of the comics folder.
	$slug = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $a['page'] );

	if ( '' === $slug ) {
		return slcv_editor_notice( 'The [comic] shortcode needs a page, for example [comic page="peewee-sep2026"].' );
	}

	// "5/6" or "5:6" or a bare decimal.
	$ratio = slcv_sanitize_ratio( $a['ratio'] );
	$width = max( 240, min( 2000, absint( $a['width'] ) ) );

	// Encode each path segment but leave the separators alone: an encoded slash
	// (%2F) in a query string is rejected outright by some WAFs and proxies.
	$segments = explode( '/', trim( SLCV_COMICS_PATH, '/' ) . '/' . $slug . '.json' );
	$src      = SLCV_VIEWER_URL . '?c=' . implode( '/', array_map( 'rawurlencode', array_filter( $segments, 'strlen' ) ) );

	$title = $a['title'] ? $a['title'] : 'Comic viewer';

	$margins = array(
		'center' => 'margin:1.5rem auto;',
		'left'   => 'margin:1.5rem auto 1.5rem 0;',
		'right'  => 'margin:1.5rem 0 1.5rem auto;',
		'wide'   => 'margin:1.5rem auto;',
	);
	$margin = isset( $margins[ $a['align'] ] ) ? $margins[ $a['align'] ] : $margins['center'];
	$max    = ( 'wide' === $a['align'] ) ? '100%' : $width . 'px';

	$figure_style = sprintf(
		'position:relative;width:100%%;max-width:%s;%s',
		$max,
		$margin
	);

	$frame_style = sprintf(
		'position:relative;width:100%%;aspect-ratio:%s;background:#12151c;border-radius:10px;overflow:hidden;',
		esc_attr( $ratio )
	);

	ob_start();
	?>
	<figure class="slcv-comic" style="<?php echo esc_attr( $figure_style ); ?>">
		<div class="slcv-comic__frame" style="<?php echo esc_attr( $frame_style ); ?>">
			<iframe
				src="<?php echo esc_url( $src ); ?>"
				title="<?php echo esc_attr( $title ); ?>"
				loading="lazy"
				allowfullscreen
				style="position:absolute;inset:0;width:100%;height:100%;border:0;">
			</iframe>
		</div>
		<?php if ( $a['caption'] ) : ?>
			<figcaption class="slcv-comic__caption" style="margin-top:.6rem;font-size:.85rem;opacity:.75;text-align:center;">
				<?php echo esc_html( $a['caption'] ); ?>
			</figcaption>
		<?php endif; ?>
	</figure>
	<?php
	return trim( ob_get_clean() );
}

/**
 * Accept "5/6", "5:6" or "0.83" and return a value safe for the CSS aspect-ratio
 * property. Falls back to the default rather than emitting anything unexpected.
 *
 * @param string $raw Raw attribute value.
 * @return string
 */
function slcv_sanitize_ratio( $raw ) {
	$raw = trim( (string) $raw );
	$raw = str_replace( ':', '/', $raw );

	if ( preg_match( '#^(\d{1,4})(?:\.\d{1,3})?\s*/\s*(\d{1,4})(?:\.\d{1,3})?$#', $raw, $m ) ) {
		if ( (float) $m[1] > 0 && (float) $m[2] > 0 ) {
			return $raw;
		}
	}
	if ( preg_match( '#^\d(?:\.\d{1,4})?$#', $raw ) && (float) $raw > 0 ) {
		return $raw;
	}
	return SLCV_DEFAULT_RATIO;
}

/**
 * Show a problem to logged-in editors only. Visitors get nothing rather than a
 * broken-looking box.
 *
 * @param string $message Plain-text message.
 * @return string
 */
function slcv_editor_notice( $message ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return '';
	}
	return '<p style="padding:.75rem 1rem;border-left:4px solid #ce1126;background:#fff4f5;color:#7a0b18;font-size:.9rem;">'
		. esc_html( $message )
		. '</p>';
}

/**
 * Make the shortcode discoverable in the block editor's slash menu.
 */
add_action( 'init', 'slcv_register_block_pattern' );
function slcv_register_block_pattern() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}
	register_block_pattern(
		'scoutlife/comic-viewer',
		array(
			'title'       => 'Comic viewer',
			'description' => 'Guided panel-by-panel comic embed.',
			'categories'  => array( 'media' ),
			'content'     => "<!-- wp:shortcode -->[comic page=\"REPLACE-ME\"]<!-- /wp:shortcode -->",
		)
	);
}
