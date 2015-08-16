<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       http://zach-adams.com
 * @since      1.0.0
 *
 * @package    Media_Library_Refresher
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

//This plugin does not store data on the database, so it's just here to contain this ASCII art of a kitten:
//
//          |             .
//          |           __|___       ^'.           .-
//          |  .          |         / \ '..----..'`/ \
//          |  |  . ,''   |         '  '   /      \  |
//          |  |  | |     |         \ , `''         /
//      ,   .  |  |  `.   |          /  ()    ()    `
//       \  |  |  ;    |  |         /      _         \
//        '-'  `'` ,_,'   |  /     '      \_/         '
//          .             '-'       `.    ,_        .`'.
//  |     __|___.                     `-._/ \,   _.'\   `.
//  |       | __|___                    _(__/        `...'^.
//  |  ,'   |   |    o             |   /    `\          ,--.'
//  ;,'   o |   |    |        .-,  |  |       `.  /    |     ".
//  | \   | |   |    | | .-. /  |  |  \_| \_,   ''     '       `
//  |  \  | |   |    | |/  | |  |  |    `-'\            `._     \
//  |   \ | |  /|    | |   | ;  |  |    '   '.             ' --.'
//  |    `| '-' |  / ` |   |  `-|  |   /   '  `'--'       ,.    |
//        `     '-'    |   `-'  |  o  /    |            .'      /
//  ____________________________|_____________________________mx
//                              |
//                           \  |
//                            '-'