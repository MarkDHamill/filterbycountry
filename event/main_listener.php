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
	protected $db;
	protected $config_text;
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
	 * @param                          						$phpbb_root_path 	string				Relative path to phpBB root
	 * @param                          						$php_ext         	string				PHP file suffix
	 * @param \phpbb\config\config     						$config          	The config
	 * @param \phpbb\log\log           						$log             	Log object
	 * @param \phpbb\user              						$user            	User object
	 * @param \phpbb\config\db_text							$config_text		The config text
	 * @param \phpbbservices\filterbycountry\core\common 	$helper				Extension's helper object
	 * @param \phpbb\db\driver\factory 						$db 				The database factory object
	 * @param $table_prefix 								string				Prefix for phpbb's database tables
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

		// If the database doesn't exist (first time), create it. Note: if the database cannot be created, the
		// function returns false. In this case, rather than disrupt the board we simply exit the function. The
		// extension's functionality is essentially disabled until the underlying problem is fixed.
		if (!$this->helper->download_maxmind())
		{
			return;
		}

		// Get a list of country codes of interest and place in an array for easy processing
		$country_codes = explode(',', $this->config_text->get('phpbbservices_filterbycountry_country_codes'));

		if (count($country_codes) == 0)
		{
			// User is always allowed in if no countries were selected by admin. Otherwise, the board is effectively disabled.
			// We won't bother to save the access in the log the extension is effectively disabled.
			return;
		}

		// Allow or restrict country codes?
		$allow = (bool) $this->config['phpbbservices_filterbycountry_allow'] ? 1 : 0;    // If false, restrict
		$ip_not_found_allow = (bool) $this->config['phpbbservices_filterbycountry_ip_not_found_allow'] ? 1 : 0;    // If false, restrict

		include($this->phpbb_root_path . 'vendor/autoload.php');

		// Hook in the MaxMind country code database.
		$reader = new Reader($this->phpbb_root_path . 'store/phpbbservices/filterbycountry/GeoLite2-Country.mmdb');

		$user_ip = $this->request->server('REMOTE_ADDR');    // Fetch the user's actual IP address.
		//$user_ip = '128.101.101.101';	// For testing, United States IP
		$exception = false;    // Triggered if there is no IP match
		$error = false;        // Assume the best
		try
		{
			$mmdb_record = $reader->country($user_ip);      // Fetch record from MaxMind's database. If not there, catch logic is executed.
			$country_code = $mmdb_record->country->isoCode; // Contains 2-digit ISO country code, in uppercase
			$country = $mmdb_record->country->name;        	// Text name of the country corresponding to the country code
		}
		catch (\Exception $e)
		{
			switch ($e->getCode())
			{
				case 'AddressNotFoundException':           	// IP not found in the Maxmind Country database
					$exception = true;
					$country_code = '??';
					$country = $this->language->lang('FBC_UNKNOWN');
				break;
				default:
					$error = true;                          // Something highly unexpected happened
				break;
			}
		}

		if ($error)
		{
			// Add a note to the error log; hopefully an admin will notice.
			$this->log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_MAXMIND_ERROR');

			// Some sort of major error has occurred using the MaxMind database. Database may be corrupt. Such
			// an error will appear on the screen for all filtered users.
			@trigger_error($this->language->lang('ACP_FBC_MAXMIND_ERROR', E_USER_WARNING));
		}

		if (!$exception)
		{
			if ($allow)
			{
				// If allow is true, country code of IP must be in list of approved country codes to have access to the board.
				$allow_ip = in_array($country_code, $country_codes);
			}
			else
			{
				// If allow is false, country code of IP must NOT be in list of approved country codes to get access to the board.
				$allow_ip = !(in_array($country_code, $country_codes));
			}
		}
		else
		{
			// Since the IP address was not found in the MaxMind database, allow IP if these sorts of errors are allowed access,
			// which is an ACP setting. Also allow localhost.
			$allow_ip = (bool) ($ip_not_found_allow || $user_ip == '127.0.0.1');
		}

		// Log the access to the phpbb_fbc_stats table, if so configured
		if ($this->config['phpbbservices_filterbycountry_keep_statistics'])
		{
			$this->save_access($country_code, $allow_ip);
		}

		$url = $this->request->server('REQUEST_URI');
		if ($this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
		{
			// In this condition, you can access the board if you are an active registered user and are already
			// logged in. Inactive users and bots are not allowed in. You have to be already logged in to be
			// annotated as a founder or normal user.
			if (in_array($this->user->data['user_type'], array(USER_FOUNDER, USER_NORMAL)))
			{
				return;
			}

			// If not logged in, you are at least allowed to access the login page when this setting enabled
			if (stristr($url, "ucp.$this->phpEx?mode=login"))
			{
				return;
			}
		}

		//$allow_ip = false;	// Uncomment for easier testing

		// This triggers the error letting the user know access is denied. They see forum headers and footers, and the error message.
		// Any links clicked on should simply continue to deny access.
		if (!$allow_ip)
		{

			// Log the unwanted access, if desired
			if ($this->config['phpbbservices_filterbycountry_log_access_errors'])
			{
				$this->log->add(LOG_ADMIN, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_BAD_ACCESS', false, array($this->user->data['username'], $user_ip, $country));
			}

			// Not allowed to see board content, so present warning message. Provide a login link if allowed.
			if ($this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
			{
				@trigger_error($this->language->lang('FBC_DENY_ACCESS_LOGIN', $user_ip, $country, $this->phpbb_root_path . "ucp.$this->phpEx?mode=login"), E_USER_WARNING);
			}
			else
			{
				@trigger_error($this->language->lang('FBC_DENY_ACCESS', $user_ip, $country), E_USER_WARNING);
			}

		}

	}

	private function save_access($country_code, $allow_ip)
	{

		// Avoid inserting a row in the phpbb_fbc_stats table if the primary key for the table already exists,
		// as it will trigger an error.
		$now = time();
		$sql = 'SELECT allowed, not_allowed 
				FROM ' . $this->table_prefix . "fbc_stats 
				WHERE country_code = '" . $country_code . "' AND timestamp = " . $now;
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
			$sql = 'UPDATE ' . $this->table_prefix . 'fbc_stats 
					SET allowed = ' . $allowed_value . ', not_allowed = ' . $not_allowed_value . " 
					WHERE country_code = '" . $country_code . "' AND timestamp = " . $now;
		}
		else
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . "fbc_stats (country_code, timestamp, allowed, not_allowed) 
					VALUES ('" . $country_code . "', " . $now . ', ' . $allowed_value . ', ' . $not_allowed_value . ')';
		}
		$this->db->sql_query($sql);

	}


}
