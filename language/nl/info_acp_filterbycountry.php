<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019, Mark D. Hamill, https://www.phpbbservices.com
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
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'ACP_FBC'					=> 'Filter op land',
	'ACP_FBC_STATS'				=> 'Statistieken',
	'ACP_FBC_STATS_TITLE'		=> 'Filter op land statistieken',
	'ACP_FBC_STATS_TITLE_EXPLAIN'	=> 'Deze pagina bevat een rapport met pagina aanvragen die zijn toegestaan of geweigerd op land sinds statistieken zijn ingeschakeld voor de extensie. Gebruik de pijlen omhoog en omlaag om de kolom in oplopende of aflopende volgorde te sorteren. <strong>Als er geen paginaverzoeken voor een land zijn, wordt dit niet weergegeven. Statistieken zijn beschikbaar sinds %s </strong>',
	'ACP_FBC_TITLE'				=> 'Filteren op landinstellingen',
	'ACP_FBC_TITLE_EXPLAIN'		=> 'Met deze extensie kunt u het verkeer naar uw forum filteren op land. U staat alleen verkeer toe vanuit de geselecteerde landen of verbiedt verkeer uit de geselecteerde landen. De MaxMind database <a href="https://dev.maxmind.com/geoip/geoip2/geolite2/">GeoLite2 Free</a> wordt gebruikt om te bepalen in welk land een gebruiker afkomstig is. Dit doet het door het land van herkomst af te leiden van het IP adres van de gebruiker. Deze database wordt wekelijks bijgewerkt op dinsdag. Deze extensie probeert de database wekelijks voor u automatisch bij te werken.',
	'ACP_FBC_TITLE_SHORT'		=> 'Instellingen',

	'LOG_ACP_FBC_BAD_ACCESS'				=> '<strong>"%1s" is de forumtoegang geweigerd voor IP %2s omdat land "%3s" geen toegang heeft tot het forum.',
	'LOG_ACP_FBC_CREATE_DIRECTORY_ERROR'	=> '<strong>Kon de map %1$s niet maken. Dit kan te wijten zijn aan onvoldoende machtigingen. De bestandsrechten voor de map moeten worden ingesteld op openbaar beschrijfbaar (777 op Unix gebaseerde systemen).</strong>',
	'LOG_ACP_FBC_DEBUG'						=> '<strong>%1s</strong>',
	'LOG_ACP_FBC_DELETE_ERROR'				=> '<strong>Kan %1$s niet verwijderen. Dit kan te wijten zijn aan onvoldoende rechten. Volledige openbare schrijfrechten zijn vereist.</strong>',
	'LOG_ACP_FBC_EXTRACT_ERROR'				=> '<strong>Kan %1$s naar %2$s niet extraheren. Een uitzondering van “%3$s” is opgetreden.</strong>',	
	'LOG_ACP_FBC_FILTERBYCOUNTRY_SETTINGS'	=> '<strong>Filter op landinstellingen bijgewerkt</strong>',
	'LOG_ACP_FBC_FOPEN_ERROR'				=> '<strong>Kon het bestand niet openen: %1$s</strong>',
	'LOG_ACP_FBC_GZIP_OPEN_ERROR'			=> '<strong>Kon gzip bestand niet openen: %1$s</strong>',
	'LOG_ACP_FBC_MAXMIND_ERROR'				=> '<strong>Een oproep naar de MaxMind landcode database leidde tot een fout. De database is waarschijnlijk corrupt.</strong>',
	'LOG_ACP_FBC_READ_FILE_ERROR'			=> '<strong>Geen leesrechten voor bestand: %1$s</strong>',
	'LOG_ACP_FBC_TARBALL_MOVE_ERROR'		=> '<strong>Kon bestand niet verplaatsen: %1$s</strong>',
	'LOG_ACP_FBC_WRITE_FILE_ERROR'			=> '<strong>Geen schrijfrechten voor bestand: %1$s</strong>',	
));