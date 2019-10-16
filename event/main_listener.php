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
	 * Execute code at the end of user setup
	 *
	 * @event core.user_setup_after
	 * @since 3.1.6-RC1
	 */

	public function filter_by_country($event)
	{

		// Get the country code(s) based on the user's IP. Based on it, determine whether its traffic should be allowed or
		// denied.

		// Country code checking is ignored inside the Administration Control Panel
		if (defined('ADMIN_START'))
		{
			return;
		}

		// Load the language files for the extension. We only need language files (outside of the ACP) inside this
		// function.
		$this->language->add_lang('common','phpbbservices/filterbycountry');

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

		// Get ignore bots setting and determine if it should be applied
		$ignore_bots = (($this->user->data['user_type'] == USER_IGNORE) && ($this->config['phpbbservices_filterbycountry_ignore_bots'] == 1)) ? true : false;

		// Get a list of country codes of interest and place in an array for easy processing
		$country_codes = explode(',', $this->config_text->get('phpbbservices_filterbycountry_country_codes'));

		// Allow (1) or restrict (0) country codes?
		$allow = (bool) $this->config['phpbbservices_filterbycountry_allow'];

		// Hook in the MaxMind country code database.
		include($this->phpbb_root_path . 'vendor/autoload.php');
		$reader = new Reader($this->phpbb_root_path . 'store/phpbbservices/filterbycountry/GeoLite2-Country.mmdb');

		// These HTTP headers contain possible originating IP addresses. REMOTE_ADDR is the one most typically used
		// and should always be present. Others may be used by CDNs or other special situations.
		$ip_keys =
			array(
				'HTTP_CF_CONNECTING_IP',  'HTTP_CLIENT_IP',            'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',       'HTTP_X_CLUSTER_CLIENT_IP',  'HTTP_X_REAL_IP',
				'HTTP_X_COMING_FROM',     'HTTP_PROXY_CONNECTION',     'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',         'HTTP_COMING_FROM',          'HTTP_VIA',
				'REMOTE_ADDR',            'X_FORWARDED_FOR'
			);

		$user_ips = array();	// Found originating IPs in the HTTP headers go in this array.

		// Change $test_mode to true if you want to use pseudo IPs for testing. Comment out the IPs you don't want to use.
		// In test mode, only the IPs in the $test_mode array are parsed. Actual HTTP headers are ignored.
		$test_mode = false;
		if ($test_mode)
		{
			$test_ips[] = '128.101.101.101';	// For testing, United States IP
			//$test_ips[] = '81.246.234.100';	// For testing, Belgian IP
			//$test_ips[] = '23.226.133.164';	// For testing, known Nord VPN USA IP
			//$test_ips[] = '111.111.111.111';	// For testing, should evaluate to JP (Japan)
			//$test_ips[] = '222.222.222.222';	// For testing, should evaluate to CN (China)
			//$test_ips[] = '33.33.33.33';		// For testing, should evaluate to US (United States)
			//$test_ips[] = '44.44.44.44';		// For testing, should evaluate to US (United States)
		}

		$error = false;        // Triggered if there is a MaxMind database issue, like it's corrupted.
		$index = count($user_ips);

		// Examine all relevant HTTP headers and create an array of all originating IP addresses in these headers.
		foreach ($ip_keys as $ip_key)
		{

			$ip_array = ($test_mode) ? $test_ips : explode(',', $this->request->server($ip_key, ''));
			if ($ip_array[0] !== '')    // Array is not empty
			{
				foreach ($ip_array as $ip)
				{

					if (filter_var($ip, FILTER_VALIDATE_IP))
					{
						// Valid IPV4 or IPV6 IP
						$user_ips[$index]['ip'] = $ip;
						try
						{
							$mmdb_record = $reader->country($ip);      // Fetch record from MaxMind's database. If not there, catch logic is executed.
							$country_code = $mmdb_record->country->isoCode; // Contains 2-digit ISO country code, in uppercase
							if (trim($country_code) == '')
							{
								// Force blank country codes to be treated as exceptions
								$user_ips[$index]['country_code'] = constants::ACP_FBC_COUNTRY_NOT_FOUND;
								$user_ips[$index]['country_name'] = $this->helper->get_country_name(constants::ACP_FBC_COUNTRY_NOT_FOUND);    // Add country name to array, for reporting.
							}
							else
							{
								$user_ips[$index]['country_code'] = $country_code;
								$user_ips[$index]['country_name'] = $this->helper->get_country_name($country_code);    // Add country name to array, for reporting.
							}
						}
						catch (\Exception $e)
						{
							switch ($e->getCode())
							{
								case 'AddressNotFoundException':            // IP not found in the Maxmind Country database
									$user_ips[$index]['country_code'] = constants::ACP_FBC_COUNTRY_NOT_FOUND;
									$user_ips[$index]['country_name'] = $this->helper->get_country_name(constants::ACP_FBC_COUNTRY_NOT_FOUND);    // Add country name to array, for reporting.
								break;
								default:
									$error = true;                          // Something highly unexpected happened
								break 3;
							}
						}
						$index++;
					}
					else
					{
						// IP is not valid
						if (!$test_mode)
						{
							$this->log->add(LOG_ADMIN, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_BAD_IP', false, array($ip, $ip_key, $this->user->data['username']));
						}
						else
						{
							$this->log->add(LOG_ADMIN, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_BAD_IP', false, array($ip, 'HTTP_TEST_HEADER', $this->user->data['username']));
						}
					}

				}

				if ($test_mode)
				{
					break;
				}
			}

		}

		if ($error)
		{
			// In the case of a serious error like a corrupt database, we need to log the event. However, we don't want
			// to take down the board.  All traffic is thus allowed if this occurs.
			$this->log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_MAXMIND_ERROR');
			return;
		}

		if ($index == 0)
		{
			// No IPs were found! This is strange. Let's create a pseudo-IP instead of 0.0.0.0 and assign it to no country.
			$user_ips[$index]['ip'] = '0.0.0.0';
			$user_ips[$index]['country_code'] = constants::ACP_FBC_COUNTRY_NOT_FOUND;
			$user_ips[$index]['country_name'] = $this->helper->get_country_name(constants::ACP_FBC_COUNTRY_NOT_FOUND);    // Add country name to array, for reporting.
		}

		$allow_request = true;

		// We have one or more IPs in an array that represent where the user is coming from. We will operate in a paranoid
		// mode: if allowed countries is set and any of the countries is not in the list of approved countries, we reject
		// the request. If not allowed countries is set and any of the countries is on this list, we reject the request.
		// We will use a bitwise AND: if any IP is not allowed, $allow_request will flip from true to false, rejecting the
		// request. Note that localhost (127.0.0.1) is allowed to facilitate development and testing.

		$index = 0;
		foreach ($user_ips as $user_ip)
		{

			switch ($allow)
			{
				case 0:    // Restrict mode, so allow IP only if NOT in the list of countries specified (restrict).
					$allow_this_ip = !(in_array($user_ip['country_code'], $country_codes));
					$allow_request = ($user_ip == '127.0.0.1') ? $allow_request & true : $allow_request & $allow_this_ip;	// Bitwise AND
					$user_ips[$index]['allowed'] = ($allow_this_ip) ? 1 : 0;
				break;

				case 1:    // Allow mode, so allow IP only if in list of countries specified (allow).
				default:
					$allow_this_ip = in_array($user_ip['country_code'], $country_codes);
					$allow_request = ($user_ip == '127.0.0.1') ? $allow_request & true : $allow_request & $allow_this_ip;	// Bitwise AND
					$user_ips[$index]['allowed'] = ($allow_this_ip) ? 1 : 0;
				break;
			}
			$index++;

		}

		if (!$allow_request && $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
		{
			// In this condition, you can access the board if you are an actively registered user and are already
			// logged in, as evidenced by the user_type, which won't be set for normal users and founders unless
			// you are already logged in. Inactive users and bots are not allowed in. You have to be already logged
			// in to be annotated as a founder or normal user.

			if (in_array($this->user->data['user_type'], array(USER_FOUNDER, USER_NORMAL)))
			{
				$this->save_access_wrapper($user_ips, $allow_request, $ignore_bots);
				return;
			}

			// If not logged in, you are at least allowed to access the login page when this setting enabled
			if (stripos($this->user->page['page'], 'ucp.' . $this->phpEx) === 0 && $this->request->variable('mode', '') == 'login')
			{
				$this->save_access_wrapper($user_ips, $allow_request, $ignore_bots);
				return;
			}
		}

		// This triggers the error letting the user know access is denied. They see forum headers and footers, and the error message.
		// Any links clicked on should simply continue to deny access.
		$this->save_access_wrapper($user_ips, $allow_request, $ignore_bots);
		if (!$allow_request)
		{
			// Not allowed to see board content, so present warning message. Provide a login link if allowed.
			if ($this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
			{
				@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS_LOGIN', $this->get_disallowed_countries($user_ips), $this->phpbb_root_path . "ucp.$this->phpEx?mode=login"), E_USER_WARNING);
			}
			else
			{
				@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS', $this->get_disallowed_countries($user_ips)), E_USER_WARNING);
			}
		}

	}

	private function save_access_wrapper(&$user_ips, $allow_request, $ignore_bots)
	{

		// This function basically just calls save_access(), but only if the IP has not already been tracked for this moment.
		// Its purpose is to ensure that a given IP has been logged only once for a particular moment.
		//
		// Parameters:
		//		$user_ips = An array of originating IPs found in this request
		//		$allow_request = flag whether IP is allowed or not
		//		$ignore_bots = indicates if this is a known bot and the bot should be ignored in the statistics

		if ($ignore_bots)
		{
			// Don't capture any bot statistics if this is enabled. This doesn't mean the bot can't read the page,
			// only that statistics won't be kept for the bot.
			return;
		}

		static $ip_allow_tracked = array();		// Tracks IP valid accesses written to the phpbb_fbc_stats table, so we only log an IP once for this moment. This approach supports multiuser access.
		static $ip_not_allow_tracked = array();	// Tracks IP invalid accesses logged, so we only log an IP once for this moment. This approach supports multiuser access.

		// Log the access to the phpbb_fbc_stats table, if so configured. We only log access once.
		if ($this->config['phpbbservices_filterbycountry_keep_statistics'])
		{
			if ($allow_request)
			{
				if (!in_array($user_ips, $ip_allow_tracked))
				{
					$ip_allow_tracked[] = $user_ips;    // In case of multiuser access, want to log access once only for each IP
					$this->save_access($user_ips, $allow_request);
				}
			}
			else
			{
				if (!in_array($user_ips, $ip_not_allow_tracked))
				{
					$ip_not_allow_tracked[] = $user_ips;    // In case of multiuser access, want to log access once only for each IP
					$this->save_access($user_ips, $allow_request);
				}
			}
		}

		return;

	}

	private function save_access($user_ips, $allow_request)
	{

		// Avoid inserting a row in the phpbb_fbc_stats table if the primary key for the table already exists,
		// as it will trigger an error.
		//
		// Parameters:
		//		$user_ips = An array of originating IPs found in this request
		//		$allow_request = flag whether IP is allowed or not

		$now = time();

		foreach ($user_ips as $user_ip)
		{
			$country_code = $user_ip['country_code'];

			// We need to use a database transaction to ensure counts don't inadvertently change.
			$this->db->sql_transaction('begin');

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
			if ($allow_request)
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

		// Log the request if logging is enabled. Only restricted attempts are logged.
		if ($this->config['phpbbservices_filterbycountry_log_access_errors'] && !$allow_request)
		{
			$this->log->add(LOG_ADMIN, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_BAD_ACCESS', false, array($this->user->data['username'], $this->get_disallowed_ips($user_ips), $this->get_disallowed_countries($user_ips)));
		}

		return;

	}

	private function get_disallowed_countries($user_ips)
	{

		// Returns a comma delimited list of country names that were disallowed for the various IPs associated with the request.
		foreach ($user_ips as $user_ip)
		{
			if ($user_ip['allowed'] == 0)
			{
				$unapproved_countries[] = $user_ip['country_name'];
			}
		}
		return implode(', ', array_unique($unapproved_countries));

	}

	private function get_disallowed_ips($user_ips)
	{

		// Returns a comma delimited list of IPs that were disallowed for the various IPs associated with the request.
		foreach ($user_ips as $user_ip)
		{
			if ($user_ip['allowed'] == 0)
			{
				$unapproved_ips[] = $user_ip['ip'];
			}
		}
		return implode(', ', array_unique($unapproved_ips));

	}

}
