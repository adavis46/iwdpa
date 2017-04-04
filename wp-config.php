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
define('DB_NAME', 'iwdpa');

/** MySQL database username */
define('DB_USER', 'iwdpaadmin');

/** MySQL database password */
define('DB_PASSWORD', 'fad*8ewabR7puqe!');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '>*>] ek;!!Jd]#Qgu3[dX?8<7*#M5mq{dU?=]jJHeOncM=1o6UH;hBG`&W?@U7ID');
define('SECURE_AUTH_KEY',  'aHn%Dku}/x{WnnJ?p#nJe,Nkg;UaV#@4O(<ObZJ@dieg}`ADq3[L)JjN%TLGA>I?');
define('LOGGED_IN_KEY',    'tE0sR9Kdi[yU5DT^!ar/tcqt?X%7o9vx$`:21iN+WS!I]xeFA/rnKU=Qal(sdclv');
define('NONCE_KEY',        'ey-:9y/j2GzN~ |hrWm7,OnT_vHRQ+WU4AA::JVx}`!_Bj@S+/%SYzYG6P}fNJ1=');
define('AUTH_SALT',        'O`Hy=09XzS1.+c%mVblm<Ub=3XEgDI@y!.$wl%`u,*{vtL[JW8UG]gDcJcwO?SUV');
define('SECURE_AUTH_SALT', '>AQ4,w!L=FD6X!5^9Jf6S_Bg3[pZ^J~9nE|2?)Ust:aE^*n]W5PCobqT?hUNF$R)');
define('LOGGED_IN_SALT',   'pS3ol%]m&O53`Z}lSZPI$:;qV<F;],[K~~voWszfy1V@ LH]p*-N(C?K^7xIp[fJ');
define('NONCE_SALT',       'x<x.$.E~;%5B<&Z,.finz[9x_<@g<Zy8U#V<e4qcpNOqg`v:$TNwE2D]BOSqPKW-');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

define('FS_METHOD','direct');