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
define( 'AUTH_KEY',          '*o=_hU=I)IR 25x+cVV#na2MQj=j$ o9o8.Fu[fg*]Z)fe/#yB^=rZ8_!tNPLVHj' );
define( 'SECURE_AUTH_KEY',   'xkMr+Fe6L3&Ml$~_}oZ8?KIdZw9ih(p:#Kz3?QQ9W%d`{}HzRf9Kxt4**@^]&`sn' );
define( 'LOGGED_IN_KEY',     '/sv)*Qyu)c[ rA$^v2Z+;{Wu>]F4;!axyJ<l1M/<=EbKI2N8u}iTC_j$8bP@IxF>' );
define( 'NONCE_KEY',         'AQ;U~DYnNvrM*kMHKFeO>WtwI i_|:Hd{!x0cR?EZ8!b5B^wZ,E$q%#NUZNd0n$~' );
define( 'AUTH_SALT',         'Vj4V1K;UHsb=oHHj%zDCEp6-OdHer-f9D v>Y@a1X^B(WvJC*A/@{|!ca46-DM0b' );
define( 'SECURE_AUTH_SALT',  '$Yo9{H8>d]upHuie`!AvD?R1TZ;$m_ctu_=_o%u+tFeloU18Wk;g}J1LcE~5]c3z' );
define( 'LOGGED_IN_SALT',    'gSi,xRbu9&wScrkfe4L$le[^]!:Idslx(}=}s+$r$OsFu_>w1]!K^)CF]B,r<cDK' );
define( 'NONCE_SALT',        'mTM,R_+:+LS(5I~,=J>o:yf>5+G,u8(]|XHZJfv^NvU$4C=3kVmy#<AvgG/<IFL,' );
define( 'WP_CACHE_KEY_SALT', 'zn3-:Hc(m]tJ6I99T7x**d#+B]Hb!NwYT_(UloS`H4Ns8M]|.pLEZDj(QSr_vo6@' );


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
