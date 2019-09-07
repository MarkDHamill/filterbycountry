<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbservices\filterbycountry\event;

/**
 * @ignore
 */

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use GeoIp2\Database\Reader;
use phpbbservices\filterbycountry\constants\constants;

/**
 * Filter by country Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return array(
			'core.user_setup' 		=> 'load_language_on_setup',
			'core.user_setup_after'	=> 'filter_by_country',
		);
	}

	protected $config;
	protected $config_text;
	protected $db;
	protected $helper;
	protected $language;
	protected $log;
	protected $phpbb_root_path;
	protected $phpEx;
	protected $request;
	protected $table_prefix;
	protected $user;

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language 						$language        	Language object
	 * @param \phpbb\request\request   						$request         	The request object
	 * @param string                   						$phpbb_root_path 	Relative path to phpBB root
	 * @param string                   						$php_ext         	PHP file suffix
	 * @param \phpbb\config\config     						$config          	The config
	 * @param \phpbb\log\log           						$log             	Log object
	 * @param \phpbb\user              						$user            	User object
	 * @param \phpbb\config\db_text							$config_text		The config text
	 * @param \phpbbservices\filterbycountry\core\common 	$helper				Extension's helper object
	 * @param \phpbb\db\driver\factory 						$db 				The database factory object
	 * @param string										$table_prefix 		Prefix for phpbb's database tables
	 *
	 */

	public function __construct(\phpbb\language\language $language, \phpbb\request\request $request, $phpbb_root_path, $php_ext, \phpbb\config\config $config, \phpbb\log\log $log, \phpbb\user $user, \phpbb\config\db_text $config_text, \phpbbservices\filterbycountry\core\common $helper, \phpbb\db\driver\factory $db, $table_prefix)
	{

		$this->config = $config;
		$this->config_text = $config_text;
		$this->db = $db;
		$this->helper = $helper;
		$this->language = $language;
		$this->log = $log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $php_ext;
		$this->request = $request;
		$this->table_prefix = $table_prefix;
		$this->user = $user;

	}

	/**
	 * Event to load language files and modify user data on every page
	 *
	 * Note: To load language file with this event, see description
	 * of lang_set_ext variable.
	 *
	 * @event core.user_setup
	 * @var	array	user_data			Array with user's data row
	 * @var	string	user_lang_name		Basename of the user's langauge
	 * @var	string	user_date_format	User's date/time format
	 * @var	string	user_timezone		User's timezone, should be one of
	 *							http://www.php.net/manual/en/timezones.php
	 * @var	mixed	lang_set			String or array of language files
	 * @var	array	lang_set_ext		Array containing entries of format
	 * 					array(
	 * 						'ext_name' => (string) [extension name],
	 * 						'lang_set' => (string|array) [language files],
	 * 					)
	 * 					For performance reasons, only load translations
	 * 					that are absolutely needed globally using this
	 * 					event. Use local events otherwise.
	 * @var	mixed	style_id			Style we are going to display
	 * @since 3.1.0-a1
	 */
	public function load_language_on_setup($event)
	{

		// Load the language files for the extension
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'phpbbservices/filterbycountry',
			'lang_set' => array('info_acp_filterbycountry', 'common'),
		);
		$event['lang_set_ext'] = $lang_set_ext;

	}

	/**
	 * Execute code at the end of user setup
	 *
	 * @event core.user_setup_after
	 * @since 3.1.6-RC1
	 */

	public function filter_by_country($event)
	{

		// Get the country code based on the user's IP. Based on it, determine whether its traffic should be allowed or
		// denied.

		// Country code checking is ignored inside the Administration Control Panel
		if (defined('ADMIN_START'))
		{
			return;
		}

		$database_mmdb_file_path = $this->phpbb_root_path . 'store/phpbbservices/filterbycountry/GeoLite2-Country.mmdb';
		if (!file_exists($database_mmdb_file_path))
		{
			// If the database doesn't exist (first time), create it. Note: if the database cannot be created, the
			// function returns false. In this case, rather than disrupt the board we simply exit the function. The
			// extension's functionality is essentially disabled until the underlying problem is fixed.
			if (!$this->helper->download_maxmind())
			{
				return;
			}
		}

		// VPN services allowed? If an IP is not found in the database, it is assumed to be a VPN IP.
		$vpn_allowed = ($this->config['phpbbservices_filterbycountry_ip_not_found_allow'] == 1) ? true : false;

		// Get ignore bots setting and determine if it should be applied
		$ignore_bots = (($this->user->data['user_type'] == USER_IGNORE) && ($this->config['phpbbservices_filterbycountry_ignore_bots'] == 1)) ? true : false;

		// Get a list of country codes of interest and place in an array for easy processing
		$country_codes = explode(',', $this->config_text->get('phpbbservices_filterbycountry_country_codes'));
		$empty_array = count(array_values($country_codes)) == 1 && $country_codes[0] == '';

		// Allow (1), restrict (0) or ignore(2) country codes?
		$allow = (int) $this->config['phpbbservices_filterbycountry_allow'];

		if (!$vpn_allowed && ($empty_array || $allow == constants::ACP_FBC_VPN_ONLY))
		{
			// User is always allowed in if no countries were selected by admin or the extension ignores countries AND
			// the VPN feature is not wanted. Otherwise, the board is effectively disabled. We won't bother to save the
			// access in the log since the extension is effectively disabled.
			return;
		}

		include($this->phpbb_root_path . 'vendor/autoload.php');

		// Hook in the MaxMind country code database.
		$reader = new Reader($this->phpbb_root_path . 'store/phpbbservices/filterbycountry/GeoLite2-Country.mmdb');

		$user_ip = $this->request->server('REMOTE_ADDR');    // Fetch the user's actual IP address.
		//$user_ip = '128.101.101.101';	// For testing, United States IP
		//$user_ip = '81.246.234.100'; // For testing, Belgian IP

		$exception = false;    // Triggered if there is no IP match
		$error = false;        // Assume the best
		try
		{
			$mmdb_record = $reader->country($user_ip);      // Fetch record from MaxMind's database. If not there, catch logic is executed.
			$country_code = $mmdb_record->country->isoCode; // Contains 2-digit ISO country code, in uppercase
			if (trim($country_code) == '')
			{
				// Force blank country codes to be treated as exceptions
				$country_code = constants::ACP_FBC_COUNTRY_NOT_FOUND;
				$exception = true;
			}
		}
		catch (\Exception $e)
		{
			switch ($e->getCode())
			{
				case 'AddressNotFoundException':           	// IP not found in the Maxmind Country database
					$exception = true;
					$country_code = constants::ACP_FBC_COUNTRY_NOT_FOUND;
				break;
				default:
					$error = true;                          // Something highly unexpected happened
				break;
			}
		}

		if ($error)
		{
			// In the case of a serious error like a corrupt database, we need to log the event. However, we don't want
			// to take down the forum.  All traffic is thus allowed if this occurs.
			$this->log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_MAXMIND_ERROR');
			return;
		}

		if (!$exception)
		{

			// The IP was found in the Maxmind database

			switch($allow)
			{
				case 0:	// Allow IP only if not in the list of countries specified (restrict)
					$allow_ip = !(in_array($country_code, $country_codes));
				break;

				case 1:	// Allow IP only if in list of countries specified (allow)
				default:
					$allow_ip = in_array($country_code, $country_codes);
				break;

				case 2:	// IP not allowed in because it is in the database, so it's not a VPN IP (ignore)
					$allow_ip = false;
				break;
			}

			if ($allow_ip && $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
			{
				// In this condition, you can access the board if you are an actively registered user and are already
				// logged in, as evidenced by the user_type, which won't be set for normal users and founders unless
				// you are already logged in. Inactive users and bots are not allowed in. You have to be already logged
				// in to be annotated as a founder or normal user.

				if (in_array($this->user->data['user_type'], array(USER_FOUNDER, USER_NORMAL)))
				{
					$this->save_access_wrapper($user_ip, $country_code, $allow_ip, $ignore_bots);
					return;
				}

				// If not logged in, you are at least allowed to access the login page when this setting enabled
				$url = $this->request->server('REQUEST_URI');
				if (stristr($url, "ucp.$this->phpEx?mode=login"))
				{
					$this->save_access_wrapper($user_ip, $country_code, $allow_ip, $ignore_bots);
					return;
				}
			}

		}
		else
		{
			// Since the IP address was not found in the MaxMind database, allow IP if VPN access is allowed. Also
			// allow localhost.
			$allow_ip = (bool) ($vpn_allowed || $user_ip == '127.0.0.1');
		}

		// This triggers the error letting the user know access is denied. They see forum headers and footers, and the error message.
		// Any links clicked on should simply continue to deny access.
		if (!$allow_ip)
		{

			$country = $this->helper->get_country_name($country_code);	// Text name of the country in the user's language.

			// Log the unwanted access, if desired, but only if the IP has not already been tracked.
			$this->save_access_wrapper($user_ip, $country_code, $allow_ip, $ignore_bots);

			// Not allowed to see board content, so present warning message. Provide a login link if allowed.
			if ($vpn_allowed && ($empty_array || $allow == constants::ACP_FBC_VPN_ONLY))
			{
				@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS_VPN', $user_ip, $country), E_USER_WARNING);
			}
			else if ($this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
			{
				@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS_LOGIN', $user_ip, $country, $this->phpbb_root_path . "ucp.$this->phpEx?mode=login"), E_USER_WARNING);
			}
			else
			{
				@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS', $user_ip, $country), E_USER_WARNING);
			}

		}
		else
		{
			// Log the access to the phpbb_fbc_stats table, if so configured. We only log access once.
			$this->save_access_wrapper($user_ip, $country_code, $allow_ip, $ignore_bots);
		}

	}


	private function save_access_wrapper($user_ip, $country_code, $allow_ip, $ignore_bots)
	{

		// This function basically just calls save_access() but only if the IP has not already been tracked for this moment.
		// Its purpose is to ensure that a given IP has been logged only once for a particular moment.
		//
		// Parameters:
		//		$user_ip = IPV4 address of user
		//		$country_code = 2-digit country code returned by MaxMind
		//		$allow_ip = flag whether IP is allowed or not
		//		$ignore_bots = indicates if this is a known bot and the bot should be ignored in the statistics

		if ($ignore_bots)
		{
			// Don't capture any bot statistics if this is enabled. This doesn't mean the bot can't read the page,
			// only that statistics won't be kept for the bot.
			return;
		}

		static $ip_allow_tracked = array();		// Tracks IP accesses written to the phpbb_fbc_stats table, so we only log an IP once for this moment. This approach supports multiuser access.
		static $ip_not_allow_tracked = array();	// Tracks IP invalid accesses logged, so we only log an IP once for this moment. This approach supports multiuser access.

		// Log the access to the phpbb_fbc_stats table, if so configured. We only log access once.
		if ($this->config['phpbbservices_filterbycountry_keep_statistics'])
		{
			if ($allow_ip)
			{
				if (!in_array($user_ip, $ip_allow_tracked))
				{
					$ip_allow_tracked[] = $user_ip;    // In case of multiuser access, want to log access once only for each IP
					$this->save_access($country_code, $allow_ip);
				}
			}
			else
			{
				if (!in_array($user_ip, $ip_not_allow_tracked))
				{
					$ip_not_allow_tracked[] = $user_ip;    // In case of multiuser access, want to log access once only for each IP
					$this->save_access($country_code, $allow_ip);
				}
			}
		}
		return;
	}

	private function save_access($country_code, $allow_ip)
	{

		// Avoid inserting a row in the phpbb_fbc_stats table if the primary key for the table already exists,
		// as it will trigger an error.

		// We need to use a database transaction to ensure counts don't inadvertently change.
		$this->db->sql_transaction('begin');

		$now = time();
		$sql_ary = array(
			'SELECT'	=> 'allowed, not_allowed',
			'FROM'		=> array(
				$this->table_prefix . constants::ACP_FBC_STATS_TABLE	=> 'fbc',
			),
			'WHERE'		=> "country_code = '" . $this->db->sql_escape($country_code) . "' AND timestamp = " . $now,
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);

		if (empty($rowset))
		{
			// Typical case
			$row_found = false;
			$allowed_value = 0;
			$not_allowed_value = 0;
		}
		else
		{
			// Retrieve current count
			$row_found = true;
			$allowed_value = (int) $rowset[0]['allowed'];
			$not_allowed_value = (int) $rowset[0]['not_allowed'];
		}
		$this->db->sql_freeresult($result);

		// Increment the allowed and not_allowed column values depending on whether the IP is allowed or not.
		if ($allow_ip)
		{
			$allowed_value++;
		}
		else
		{
			$not_allowed_value++;
		}

		// Update the database row if it exists, otherwise insert it
		if ($row_found)
		{
			$sql_ary = array(
				'allowed'		=> (int) $allowed_value,
				'not_allowed'	=> (int) $not_allowed_value,
			);
			$sql = 'UPDATE ' . $this->table_prefix . constants::ACP_FBC_STATS_TABLE . ' 
				SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . "
				WHERE country_code = '" . $this->db->sql_escape($country_code) . "' AND timestamp = " . $now;
		}
		else
		{
			$sql_ary = array(
				'country_code'	=> $this->db->sql_escape($country_code),
				'timestamp'		=> $now,
				'allowed'		=> (int) $allowed_value,
				'not_allowed'	=> (int) $not_allowed_value,
			);
			$sql = 'INSERT INTO ' . $this->table_prefix . constants::ACP_FBC_STATS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		}
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');

	}

}
