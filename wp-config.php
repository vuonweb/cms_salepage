<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'cms_salepage' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'kf/B4rS%_Q</&q^E$4SF,Qv  DEpwPZY@G1z#ss8`wYxc7=#ToJ*iDhMwb_}xWgk' );
define( 'SECURE_AUTH_KEY',  '!?gHQ*SG~w3QgRD}T^V&krpV*3)z*Xu:HYbz,ravd}jH] /I8OMxM9V!vRYaFoW+' );
define( 'LOGGED_IN_KEY',    'iFlG+5[P9Wk+`~np0OQ$Jz(zjAr789tdDlD&lXP|!`cl.> ./ij:^_ OI{&L4D}/' );
define( 'NONCE_KEY',        'a{r:GC+p;0F[`z`iL8h)^8*JV%3G(%K~[qH)oh1q~HZ11h{U$Aic33;+Fp84MFva' );
define( 'AUTH_SALT',        'qS{BDCnZI`_?|WVa?r1qI1Z6rE-u+q--EVa^d5(4fK59TN#a|#$WFRc.yh9~V;!`' );
define( 'SECURE_AUTH_SALT', '_8YEt;P$P?$sMsxkUL@v)7B:AqMqm{8Z(^~%bPVINotr?7q GaY0w/yx|?xKg7DU' );
define( 'LOGGED_IN_SALT',   'FL-dL`0>~_[CakP3k99^&0?(#(Ux6B&DKA2{/T:;XAW.(0T)ntvr;^yfPe VB|cT' );
define( 'NONCE_SALT',       '7:j)2Qv( P@T3oro|arvY&)R:7+NyHm[]]mCz@w?}SA$m3+T3IQi /K,r,P<<r%C' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'cms_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
