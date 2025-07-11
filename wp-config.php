<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u344231864_clickjumbo_db' );

/** Database username */
define( 'DB_USER', 'u344231864_clickjumbo' );

/** Database password */
define( 'DB_PASSWORD', 'Clickjumbo@2025' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'Bj- ^x}V+ =_b!3_T_] HruwMf^j.eC^(vDDu.e.#Lwr@Ha*d+8C;5*KhLL~W z5' );
define( 'SECURE_AUTH_KEY',  'Pf]zQpAj2`i]#$~3][zgI_Tp+[WX))S;QG5npcz{YBzN+MnhG4T-O/v3B14ZVhof' );
define( 'LOGGED_IN_KEY',    'CC`bj1}_+&_0dhRv1&)I[NWy^F&>dRL}39vS:(H12b0CCx8@z]/)QVes&Dzhkm0y' );
define( 'NONCE_KEY',        'V!rp _J6zW=T2k<!QDW`(G8K^2caxX}8&}x]LnJy2P];TMc+<vd(qm=]Wj;*raw-' );
define( 'AUTH_SALT',        'abQN*6WEx]W1LlKxdSd}I3rY nw3/TFmH,VUxaQY9tV&s)}-V<H,bqm)^1^K)rS^' );
define( 'SECURE_AUTH_SALT', 'FXf0 ,^a=ky}5N&a.Av}+$jH(+?r>9<??=jiSYGVdW*e4DY5c?.cXxX8zKp AW%t' );
define( 'LOGGED_IN_SALT',   '-{Bb&*u?ZKx=t$ERt %G3$HjZ%PSwIEz1G}M5>s4KOttxnSam-Bx?iBuU]inNfh<' );
define( 'NONCE_SALT',       ',1G~*K>pJt`[ClrmooHC!o,jo5$r&G1Cpxnsg{bU|G9,$c3b{OSg$bEG$HuA$<Yy' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', true );
define('WP_DEBUG_DISPLAY', true);

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
