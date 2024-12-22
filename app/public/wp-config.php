<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '<e-p=a5Ci@UFI/2@<MlE^/EnRU4!yTQkEB5ZCp:CBmx/4~GvwQ_lr@1V{6M|Ou+K' );
define( 'SECURE_AUTH_KEY',   '&Hzvp:_7_~v-n^~?kinzI{5aE}O{O}SK|>Z%EPbtR !T`7<Z[{r^z})S6A&(}-)5' );
define( 'LOGGED_IN_KEY',     ':QK8xjBgopfl/I9|fF%fDUswfTt1xE]>?!:&@T?[Xz|y:dbW uP6`R7Bb<;B&m7(' );
define( 'NONCE_KEY',         'KwJ<|vC@D^*5]`j?V^^7gG_7`?SeGDojE6Dae4]?hL6BSSWQ>c_Jdl#G?[4{@8h%' );
define( 'AUTH_SALT',         '$T-l%d-rX`Yj/8IaO9iHKgajzg<(ZN2WI5K^N]hHomI` pEvKc-xZP]`J^h5Kl[y' );
define( 'SECURE_AUTH_SALT',  ' z@F51@PEYX;bb$ vZTBBgO_!1YY}_NCr!&o:adLnVsOB^I@B7PKt}}v~xr_C 7M' );
define( 'LOGGED_IN_SALT',    ',u6n^^.TEw}8hb7.>~X;ZeYgF@i$l9.QDcI[c|C*{W-R`/E(2mCq+&I/[YXyM8>z' );
define( 'NONCE_SALT',        'wL_<EUMG|*g|@Y{nxAp5|90|:%vE@gUxGYJPNzKzha]4RFNQ8JR3s@bAp 6$!<be' );
define( 'WP_CACHE_KEY_SALT', 'Jy:J]8cz<[Lq.|Y;Ota>v24(*sQI3=|@mwv,FStgXTt?LPlqt|95xaMw~z}uy9cP' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
