<?php
/*
Plugin Name: Medienfabrik Info
Plugun URI: https://pfeiffer-medienfabrik.de
Description: Ein Dashboard-Widget informiert die Kunden der Pfeiffer Medienfabrik GmbH & Co. KG über Sicherheitswarnungen und Neuerungen im Webbereich. Es wird eine Chat-Symbol für Support-Fragen direkt zur Medienfabrik auf der Back-End-Startseite eingebunden.
Version: 1.0.6
Author: Daniel Kaiser
Author URI: https://pfeiffer-medienfabrik.de/ist/daniel/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Medienfabrik Info is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Medienfabrik Info is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Medienfabrik Info. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/


// Stylesheets laden
wp_enqueue_style( 'miInfo-widget-style', plugin_dir_url( __FILE__ ) . 'styles/widget-styles.css' );
wp_enqueue_style( 'miInfo-options-style', plugin_dir_url( __FILE__ ) . 'styles/options-styles.css' );


// Optionsseite im Adminbereich

function miInfoPlugIn_option_page() {
	global $mi_article_count, $mi_excerpt_length, $mi_allow_refer;
	?>

	<?php
	//Formularwerte aus Datenbank laden und Variablen zuweisen

	$action = ( isset( $_POST['action'] ) ) ? $_POST['action'] : false;

	if ( ! isset( $_POST['MI_SENT'] ) ) {
		$mi_article_count  = get_option( 'mi_article_count' );
		$mi_excerpt_length = get_option( 'mi_excerpt_length' );
		$mi_allow_mfrefer  = get_option( 'mi_allow_mfrefer' );
	}


	// Speichern der im Adminbereich - Optionen eingestellten Werte
	if ( isset( $_POST['MI_SENT'] ) ) {
  	    if (
			check_admin_referer( $action ) &&
			is_admin() &&
			isset( $_POST['mi_article_count'] ) &&
			strlen( $_POST['mi_article_count'] ) != 0 &&
			is_numeric( $_POST['mi_article_count'] ) &&
			isset( $_POST['mi_excerpt_length'] ) &&
			strlen( $_POST['mi_excerpt_length'] ) != 0 &&
			is_numeric( $_POST['mi_excerpt_length'] ) &&
			isset( $_POST['mi_allow_refer'] )
		) {
			update_option( 'mi_article_count', (int) $_POST['mi_article_count'] );
			update_option( 'mi_excerpt_length', (int) $_POST['mi_excerpt_length'] );
			update_option( 'mi_allow_refer', (int) $_POST['mi_allow_refer'] );
		}
	}
	$mi_article_count  = esc_attr( get_option( 'mi_article_count' ) );
	$mi_excerpt_length = esc_attr( get_option( 'mi_excerpt_length' ) );
	$mi_allow_refer    = esc_attr( get_option( 'mi_allow_refer' ) );

	$action = 'save_options_' . wp_rand( 1000, 9999 );
	?>

    <h2>Pfeiffer Medienfabrik Info-Widget</h2>
    <h2>Options Page</h2>
    <form name="form1" method="post" action="<?php $location ?>">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
		<?php wp_nonce_field( $action ); ?>
        <label>
            Anzahl der Beiträge, die im Feed angezeigt werden sollen<br>
            (Werte zwischen 1 und 10)
        </label><br>
        <input class="mio_formfield" name="mi_article_count" type="number" value="<?php echo $mi_article_count; ?>"
               required> Beiträge<br/><br>
        <label>
            Anzahl der Zeichen die in der Inhaltsvorschau dargestellt werden.<br>
            (Werte zwischen 50 und 500)
        </label><br>
        <input class="mio_formfield" name="mi_excerpt_length" type="number" value="<?php echo $mi_excerpt_length; ?>"
               required> Zeichen<br/>
        <br>
        <label>
            Erlaube die Einbindung eines Medienfabrik Links im Widget-Footer?
        </label><br>
        <input type="radio" name="mi_allow_refer" value="1" <?php if ( $mi_allow_refer === '1' ) {
			echo 'checked';
		} ?>> Ja<br>
        <input type="radio" name="mi_allow_refer" value="0" <?php if ( $mi_allow_refer === '0' ) {
			echo 'checked';
		} ?>> Nein<br>
        <br>
        <input type="submit" name="MI_SENT" value="Speichern"/>
    </form>

	<?php
} // end miInfoPlugIn_option_page


// Adminmenu Optionen erweitern
function miInfoPlugIn_add_menu() {
//Datenbankfelder in Datenbanktabelle options einfügen wenn nicht vorhanden
	add_option( 'mi_article_count', '3' );
	add_option( 'mi_excerpt_length', '150' );
	add_option( 'mi_allow_refer', '0' );

	add_options_page( 'Pfeiffer Medienfabrik Info PlugIn', 'Pfeiffer Medienfabrik Info PlugIn', 9, __FILE__, 'miInfoPlugIn_option_page' );
} // end miInfoPlugIn_add_menu


// Registrieren der WordPress-Hooks
add_action( 'admin_menu', 'miInfoPlugIn_add_menu' );


if ( is_admin() ) {
	add_action( 'wp_dashboard_setup', 'add_my_mi_dashboard_widget' );
}

function add_my_mi_dashboard_widget() {
	wp_add_dashboard_widget( 'my_widget_id',
		'Medienfabrik Info',
		'insert_my_mi_widget_data'
	);
} // end add_my_mi_dashboard_widget

function insert_my_mi_widget_data() {
	?>
    <div id="mi-container">
        <div id="mi-heading">
            <h2>Sicherheits&shy;informationen der<br>Pfeiffer Medienfabrik</h2>
        </div>
        <div id="mi-logo">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/PMF_Logo.png' ?>" width="100px">
        </div>
        <div id="mi-content">
			<?php
			// Get RSS Feed(s)
			include_once( ABSPATH . WPINC . '/feed.php' );
			// Get a SimplePie feed object from the specified feed source.
			$rss      = fetch_feed( 'https://pfeiffer-medienfabrik.de/kategorie/webwissen/feed/' );
			$maxitems = 0;
			if ( ! is_wp_error( $rss ) ) : // Checks that the object is created correctly
				// Figure out how many total items there are, but limit it to 5. 
				$maxitems = $rss->get_item_quantity( get_option( 'mi_article_count' ) );
				// Build an array of all the items, starting with element 0 (first element).
				$rss_items = $rss->get_items( 0, $maxitems );
			endif;
			?>

            <ul>
				<?php if ( $maxitems == 0 ) : ?>
                    <li><?php _e( 'No items', 'my-text-domain' ); ?></li>
				<?php else : ?>
					<?php // Loop through each feed item and display each item as a hyperlink. ?>
					<?php foreach ( $rss_items as $item ) : ?>
                        <hr><br>
                        <li>
                            <a class="mi-articletitle"
                               href="<?php echo esc_url( $item->get_permalink() ); ?>"
                               target="_blank" ;
                               title="<?php printf( __( 'Posted %s', 'my-text-domain' ), $item->get_date( 'j F Y | g:i a' ) ); ?>">
								<?php echo esc_html( $item->get_title() ); ?>
                            </a>
                            <p>
								<?php
								$excerpt = $item->get_content();
								$excerpt = substr( $excerpt, 0, get_option( 'mi_excerpt_length' ) );
								echo $excerpt . "...";
								?>
                            </p>
                            <p class="mi-datum">
								<?php printf( __( 'Posted %s', 'my-text-domain' ), $item->get_date( 'j F Y | g:i a' ) ); ?>
                            </p>
                            <p class="mi-mehrlesen">
                                <a href="<?php echo esc_url( $item->get_permalink() ); ?>" target="_blank">mehr
                                    lesen</a>
                            </p>
                        </li>
					<?php endforeach; ?>
				<?php endif; ?>
            </ul>
        </div><!-- mi-content -->
        <div id="mi-footer">
			<?php
			if ( get_option( 'mi_allow_refer' ) === '1' ) {
				echo '<a class="mi-footer-link" href="https://pfeiffer-medienfabrik.de" target="_blank">Pfeiffer Medienfabrik</a>';
			}
			?>
            <script>
                (function () {
                    var w = window;
                    var ic = w.Intercom;
                    if (typeof ic === "function") {
                        ic('reattach_activator');
                        ic('update', intercomSettings);
                    } else {
                        var d = document;
                        var i = function () {
                            i.c(arguments)
                        };
                        i.q = [];
                        i.c = function (args) {
                            i.q.push(args)
                        };
                        w.Intercom = i;

                        function l() {
                            var s = d.createElement('script');
                            s.type = 'text/javascript';
                            s.async = true;
                            s.src = 'https://widget.intercom.io/widget/zs5vfz3k';
                            var x = d.getElementsByTagName('script')[0];
                            x.parentNode.insertBefore(s, x);
                        }

                        if (w.attachEvent) {
                            w.attachEvent('onload', l);
                        }
                        else {
                            w.addEventListener('load', l, false);
                        }
                    }
                })()
            </script>
            <script>
                window.Intercom('boot', {
                    app_id: 'zs5vfz3k',
                });
            </script>

        </div>
    </div><!-- mi-container -->
	<?php
} // end insert_my_mi_widget_data
