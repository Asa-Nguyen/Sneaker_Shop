<?php
define( 'WP_CACHE', true /* Modified by NitroPack */ );
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
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'mfw021_sneakershop' );

/** Database username */
define( 'DB_USER', 'mfw021_sneakershop' );

/** Database password */
define( 'DB_PASSWORD', 'Hutech@123' );

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
define( 'AUTH_KEY',         '[GOFF23hvH$YS8VDgC5CE=Y-&)aH*|{yaj(@z6Muaud-0t?|)8p!x.zK5BF|+d+3' );
define( 'SECURE_AUTH_KEY',  'Xw3Cx#}e4OHSTBe~tjB[4<{7cU^HwTh25#z9)k FAN9_~zizf}6>;e9-6Up41jvi' );
define( 'LOGGED_IN_KEY',    'pmMs1sC%iE#Ps9{(H:?0{`t#WCarJd3b7Lh~1fm<e{JB&Qn8q+Tsluzi`k9a2:^|' );
define( 'NONCE_KEY',        'rsch;E3v/ZVV_;3lkG-*]TJ&{~]!3-jxL8pruS(SXO`oQKmy@RX@zZ+J}@vx&tS ' );
define( 'AUTH_SALT',        'D;=(8OBA94)*&cVeX|H]Ugjx6P}g9/HQW-z|?SCFr}5Y(gF>O:@si22iezLqn.JA' );
define( 'SECURE_AUTH_SALT', '$2iHSV]8C+O|hmEZ!o=YZC `:fp!DUm1%M0#u487;;d,1;.1H%mR=cci_WWkD`8r' );
define( 'LOGGED_IN_SALT',   's}YL AK1&0ilEKFl{o_Gy8oOb:UbrnUg^e(J}:T&VxT17p)*Q3]#-Tt^_K.9`-4<' );
define( 'NONCE_SALT',       ',1h,]tWexIs-_8/!KUYn0xLN]V)t>>c,jM3%q2}+ARde];DhKVTIhJAtCgv`XjZR' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
