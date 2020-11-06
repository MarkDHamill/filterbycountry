<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// » “ “ …
//

$lang = array_merge($lang, array(
	'ACP_FBC'					=> 'Filter by country',
	'ACP_FBC_STATS'				=> 'Statistics',
	'ACP_FBC_STATS_TITLE'		=> 'Filter by country statistics',
	'ACP_FBC_STATS_TITLE_EXPLAIN'	=> 'This page contains a report of page requests allowed or blocked by country since statistics were last enabled for the extension. Use the up and down arrows to sort the column in either an ascending or descending sequence. Statistics are available since %s only. <strong>If there were no page requests for a country, it is not shown.</strong> If you select reset statistics, all statistics will show zero and all rows will be removed from the statistics table.',
	'ACP_FBC_TITLE'				=> 'Filter by country settings',
	'ACP_FBC_TITLE_EXPLAIN'		=> 'This extension allows you to filter traffic to your board by country. This product includes GeoLite2 data created by MaxMind, available from <a href="https://www.maxmind.com" target="_blank">https://www.maxmind.com</a>. Its GeoLite2 country database is refreshed every week automatically.',
	'ACP_FBC_TITLE_SHORT'		=> 'Settings',

	'LOG_ACP_FBC_BAD_ACCESS'				=> '<strong>Filter by country: %1$s was denied board access from IP(s) %2$s because access from country/countries “%3$s“ is not allowed.',
	'LOG_ACP_FBC_CREATE_DIRECTORY_ERROR'	=> '<strong>Unable to create the folder %1$s. This may be due to insufficient permissions. The file permissions on the folder should be set to publicly writeable (777 on Unix-based systems).</strong>',
	'LOG_ACP_FBC_DEBUG'						=> '<strong>%1$s</strong>',
	'LOG_ACP_FBC_DELETE_ERROR'				=> '<strong>Unable to delete %1$s. This may be due to insufficient permissions. Full public write permissions are needed.</strong>',
	'LOG_ACP_FBC_EXTRACT_ERROR'				=> '<strong>Unable to extract %1$s to %2$s. An “%3$s” exception was thrown.</strong>',
	'LOG_ACP_FBC_FILTERBYCOUNTRY_SETTINGS'	=> '<strong>Filter by country settings updated</strong>',
	'LOG_ACP_FBC_FOPEN_ERROR'				=> '<strong>Could not download file: %1$s. The MaxMind service may be down temporarily.</strong>',
	'LOG_ACP_FBC_GZIP_OPEN_ERROR'			=> '<strong>Could not open gzip file: %1$s</strong>',
	'LOG_ACP_FBC_HTTP_ERROR'				=> '<strong>Could not download file: %1$s. An unexpected HTTP error code %1$s occurred.</strong>',
	'LOG_ACP_FBC_MAXMIND_ERROR'				=> '<strong>A call to the MaxMind country code database triggered an error. The database is most likely corrupt.</strong>',
	'LOG_ACP_FBC_READ_FILE_ERROR'			=> '<strong>No read permissions for file: %1$s</strong>',
	'LOG_ACP_FBC_TARBALL_MOVE_ERROR'		=> '<strong>Could not move file: %1$s</strong>',
	'LOG_ACP_FBC_WRITE_FILE_ERROR'			=> '<strong>No write permissions for file: %1$s</strong>',
));