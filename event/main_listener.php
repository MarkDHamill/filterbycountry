<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
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
	protected $fbc_stats_table;
	protected $filesystem;
	protected $helper;
	protected $language;
	protected $log;
	protected $phpbb_root_path;
	protected $phpEx;
	protected $request;
	protected $user;
	protected $user_ips;

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
	 * @param \phpbb\filesystem 							$filesystem			The filesystem object
	 * @param \phpbbservices\filterbycountry\				$fbc_stats_table	Extension's statistics table
	 *
	 */

	public function __construct(\phpbb\language\language $language, \phpbb\request\request $request, $phpbb_root_path, $php_ext, \phpbb\config\config $config, \phpbb\log\log $log, \phpbb\user $user, \phpbb\config\db_text $config_text, \phpbbservices\filterbycountry\core\common $helper, \phpbb\db\driver\factory $db, \phpbb\filesystem\filesystem $filesystem, $fbc_stats_table)
	{

		$this->config = $config;
		$this->config_text = $config_text;
		$this->db = $db;
		$this->fbc_stats_table	= $fbc_stats_table;
		$this->filesystem = $filesystem;
		$this->helper = $helper;
		$this->language = $language;
		$this->log = $log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $php_ext;
		$this->request = $request;
		$this->user = $user;
		$this->user_ips = array();				// Contains a list of valid IPs for this page request, based on IPs in HTTP headers

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

		// Country code checking is ignored (and meaningless) inside the Administration Control Panel
		if (defined('ADMIN_START'))
		{
			return;
		}

		// Get a list of country codes of interest and place in an array for easy processing
		$country_codes = explode(',', $this->config_text->get('phpbbservices_filterbycountry_country_codes'));
		if (count($country_codes) === 0)
		{
			// If the administrator hasn't selected any countries to allow or restrict, effectively the extension is disabled.
			return;
		}

		// If the license key has not been entered or is not valid, the MaxMind database integration won't work. The
		// database may not have been downloaded yet. So in this event, the extension is not yet configured properly, in
		// which case we want to exit this function, allowing all traffic until this is true.
		if ($this->config['phpbbservices_filterbycountry_license_key_valid'] == 0 || strlen(trim($this->config['phpbbservices_filterbycountry_license_key'])) !== 16)
		{
			return;
		}

		$database_mmdb_file_path = $this->phpbb_root_path . 'store/phpbbservices/filterbycountry/GeoLite2-Country.mmdb';
		if (!$this->filesystem->exists($database_mmdb_file_path))
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
		$ignore_bots = (bool) (($this->user->data['user_type'] == USER_IGNORE) && ($this->config['phpbbservices_filterbycountry_ignore_bots'] == 1));

		// Allow (1) or restrict (0) country codes?
		$allow = (bool) $this->config['phpbbservices_filterbycountry_allow'];

		// Keep statistics?
		$keep_statistics = (bool) $this->config['phpbbservices_filterbycountry_keep_statistics'];

		// In test mode, the test IP set in the ACP is used. Actual HTTP headers are ignored.
		$test_mode = (bool) (trim($this->config['phpbbservices_filterbycountry_test_ip']) !== '');

		// Create an array of candidate IPs for testing
		$ips_to_test = array();
		if ($test_mode)
		{
			// In test mode, test only the specified test IP
			$ips_to_test[] = trim($this->config['phpbbservices_filterbycountry_test_ip']);
			$success = $this->test_ips($ips_to_test);
		}
		else
		{
			// These HTTP headers contain possible originating IP addresses. REMOTE_ADDR is the one most typically used
			// and should always be present. Others may be used by CDNs or other special situations.
			$http_headers =
				array(
					'REMOTE_ADDR', 'HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
					'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_COMING_FROM', 'HTTP_PROXY_CONNECTION',
					'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_COMING_FROM', 'HTTP_VIA',	'X_FORWARDED_FOR'
				);

			// Examine all potentially relevant HTTP headers and create an array of all originating IP addresses in these headers.
			// In most cases only REMOTE_ADDR is present and contains the relevant IP.
			foreach ($http_headers as $http_header)
			{
				$ips_to_test = explode(',', $this->request->server($http_header, ''));

				if ($ips_to_test[0] !== '')    // Array is not empty
				{
					$success = $this->test_ips($ips_to_test);
					if (!$success)
					{
						break;	// A major error has occurred, probably a bad MaxMind database
					}
				}
			}
		}

		if (!$success)
		{
			// In the case of a serious error like a corrupt database, we need to log the event. However, we don't want
			// to disable all access to the board.  All traffic is thus allowed if this occurs by exiting this function.
			$this->log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_MAXMIND_ERROR');
			return;
		}

		$index = count($this->user_ips);

		if (count($this->user_ips) == 0)
		{
			// No IPs were found! This is strange. Let's create a pseudo-IP instead of 0.0.0.0 and assign it to no country.
			$this->user_ips[$index]['ip'] = '0.0.0.0';
			$this->user_ips[$index]['country_code'] = constants::ACP_FBC_COUNTRY_NOT_FOUND;
			$this->user_ips[$index]['country_name'] = $this->helper->get_country_name(constants::ACP_FBC_COUNTRY_NOT_FOUND);    // Add country name to array, for reporting.
		}

		$allow_request = true;

		// We have one or more IPs in an array that represent potential countries where the user is coming from. We will
		// operate in a paranoid mode: if allowed countries is set and any of the countries is not in the list of approved
		// countries, we reject the request. If not allowed countries is set and any of the countries is on this list,
		// we reject the request.

		foreach ($this->user_ips as $user_ip)
		{

			if ($allow)	// Allow mode, so IP is llowed only if its country is among the countries desired. Localhost allowed for testing.
			{
				$allow_request = $allow_request && (in_array($user_ip['country_code'], $country_codes) || $user_ip['ip'] == '127.0.0.1');
			}
			else		// Restrict mode, so IP is allowed if its country is not among the countries desired. Localhost allowed for testing.
			{
				$allow_request = $allow_request && (!in_array($user_ip['country_code'], $country_codes) || $user_ip['ip'] == '127.0.0.1');
			}

		}

		if (!$allow_request && $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
		{
			// In this condition, you can access the board if you are an actively registered user and are already
			// logged in, as evidenced by the user_type, which won't be set for normal users and founders unless
			// you are already logged in. Inactive users and bots are not allowed in. You have to be already logged
			// in to be annotated as a founder or normal user.

			if ($keep_statistics && in_array($this->user->data['user_type'], array(USER_FOUNDER, USER_NORMAL)))
			{
				$this->save_access(true, $ignore_bots);
				return;
			}

			// If not logged in, you are at least allowed to access the login page when this setting enabled.
			if ($keep_statistics && stripos($this->user->page['page'], 'ucp.' . $this->phpEx) === 0 && $this->request->variable('mode', '') == 'login')
			{
				$this->save_access(false, $ignore_bots);
				return;
			}
		}

		// This triggers the error letting the user know access is denied. They see forum headers and footers, and the error message.
		// Any links clicked on should simply continue to deny access, with the possible exception of the login link.
		if ($keep_statistics)
		{
			$this->save_access($allow_request, $ignore_bots);
		}
		if (!$allow_request)
		{
			// Not allowed to see board content, so present warning message. Provide a login link if allowed.
			if ((bool) $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
			{
				@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS_LOGIN', $this->get_disallowed_countries($this->user_ips), $this->phpbb_root_path . "ucp.$this->phpEx?mode=login"), E_USER_WARNING);
			}
			else
			{
				@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS', $this->get_disallowed_countries($this->user_ips)), E_USER_WARNING);
			}
		}

	}

	private function save_access($allow_request, $ignore_bots)
	{

		// Logs accesses (allowed or restricted) based on content in $this->user_ips array
		// Parameters:
		//		$allow_request = flag whether IP is allowed or not
		//		$ignore_bots = ignore bots settings (true or false)

		if ($ignore_bots && $this->user->data['user_type'] === USER_IGNORE)
		{
			// Don't capture any bot statistics if this is enabled. This doesn't mean the bot cannot read the page,
			// only that statistics won't be kept for the bot.
			return;
		}

		static $already_called;
		if (!isset($already_called))
		{
			$already_called = false;
		}

		// We want to ensure this function is only called once by this process, to avoid multiple statistics being recorded
		if ($already_called)
		{
			return;
		}

		$now = time();

		// We need to use a database transaction to ensure counts don't inadvertently change.
		$this->db->sql_transaction('begin');

		foreach ($this->user_ips as $user_ip)
		{
			$country_code = $user_ip['country_code'];

			$sql_ary = array(
				'SELECT'	=> 'allowed, not_allowed',
				'FROM'		=> array(
					$this->fbc_stats_table	=> 'fbc',
				),
				'WHERE'		=> "country_code = '" . $this->db->sql_escape($country_code) . "' AND timestamp = " . $now,
			);
			$sql = $this->db->sql_build_query('SELECT', $sql_ary);
			$result = $this->db->sql_query($sql);
			$rowset = $this->db->sql_fetchrowset($result);

			if (empty($rowset))
			{
				// Typical case
				$update = false;
				$allowed_value = 0;
				$not_allowed_value = 0;
			}
			else
			{
				// Retrieve current count
				$update = true;
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

			// Update the database row if it exists, otherwise queue it for inserting
			if ($update)
			{
				$update_sql_ary = array(
					'allowed'		=> (int) $allowed_value,
					'not_allowed'	=> (int) $not_allowed_value,
				);
				$sql = 'UPDATE ' . $this->fbc_stats_table . ' 
					SET ' . $this->db->sql_build_array('UPDATE', $update_sql_ary) . "
					WHERE country_code = '" . $this->db->sql_escape($country_code) . "' AND timestamp = " . $now;
			}
			else
			{
				$insert_sql_ary[] = array(
					'country_code'	=> $this->db->sql_escape($country_code),
					'timestamp'		=> $now,
					'allowed'		=> (int) $allowed_value,
					'not_allowed'	=> (int) $not_allowed_value,
				);
			}
			$this->db->sql_query($sql);

		}

		// Do all insert statements at once for efficiency. Unless there are multiple IPs in various HTTP headers,
		// there should be no more than one row inserted.
		if (count($insert_sql_ary) > 0)
		{
			$this->db->sql_multi_insert($this->fbc_stats_table, $insert_sql_ary);
		}

		$this->db->sql_transaction('commit');

		// Log the request if logging is enabled. Only restricted attempts are logged.
		if ($this->config['phpbbservices_filterbycountry_log_access_errors'] && !$allow_request)
		{
			$this->log->add(LOG_ADMIN, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_BAD_ACCESS', false, array($this->user->data['username'], $this->get_disallowed_ips($this->user_ips), $this->get_disallowed_countries($this->user_ips)));
		}

		// Note that this function was already called, so don't call it again.
		$already_called = true;

		return;

	}

	private function get_disallowed_countries(&$user_ips)
	{

		// Returns a comma delimited list of country names that were disallowed for the various IPs associated with the request.
		$unapproved_countries = array();
		foreach ($user_ips as $user_ip)
		{
			if ($user_ip['allowed'] == 0)
			{
				$unapproved_countries[] = $user_ip['country_name'];
			}
		}
		return implode(', ', array_unique($unapproved_countries));

	}

	private function get_disallowed_ips(&$user_ips)
	{

		// Returns a comma delimited list of IPs that were disallowed for the various IPs associated with the request.
		$unapproved_ips = array();
		foreach ($user_ips as $user_ip)
		{
			if ($user_ip['allowed'] == 0)
			{
				$unapproved_ips[] = $user_ip['ip'];
			}
		}
		return implode(', ', array_unique($unapproved_ips));

	}

	private function test_ips($possible_ips)
	{

		// This function parses an array of possible IPs, possible because there could be some items in the array
		// that are not even IPs, but text. Any valid IPs are placed in the $this->user_ips array. The Maxmind
		// database is used to determine the country associated with an IP.

		// Hook in the MaxMind country code database interface.
		$reader = new Reader($this->phpbb_root_path . 'store/phpbbservices/filterbycountry/GeoLite2-Country.mmdb');

		if ($possible_ips[0] !== '')    // Array is not empty
		{
			$index = 0;

			foreach ($possible_ips as $possible_ip)
			{

				if (filter_var($possible_ip, FILTER_VALIDATE_IP))	// Invalid IPs are ignored. Many of these odd HTTP headers won't place valid IPs in them.
				{
					// Valid IPV4 or IPV6 IP
					try
					{
						$mmdb_record = $reader->country($possible_ip);      // Fetch record from MaxMind's database. If not there, catch logic is executed.
						$country_code = $mmdb_record->country->isoCode; 	// Contains 2-digit ISO country code, in uppercase
						if (trim($country_code) == '')
						{
							// Force blank country codes to be treated as exceptions
							$this->user_ips[$index]['ip'] = $possible_ip;
							$this->user_ips[$index]['country_code'] = constants::ACP_FBC_COUNTRY_NOT_FOUND;
							$this->user_ips[$index]['country_name'] = $this->helper->get_country_name(constants::ACP_FBC_COUNTRY_NOT_FOUND);    // Add country name to array, for reporting.
						}
						else
						{
							$this->user_ips[$index]['ip'] = $possible_ip;
							$this->user_ips[$index]['country_code'] = $country_code;
							$this->user_ips[$index]['country_name'] = $this->helper->get_country_name($country_code);    // Add country name to array, for reporting.
						}
					}
					catch (\Exception $e)
					{
						if ($e->getCode() == 'AddressNotFoundException')
						{
							$this->user_ips[$index]['ip'] = $possible_ip;
							$this->user_ips[$index]['country_code'] = constants::ACP_FBC_COUNTRY_NOT_FOUND;
							$this->user_ips[$index]['country_name'] = $this->helper->get_country_name(constants::ACP_FBC_COUNTRY_NOT_FOUND);    // Indicate country not found for the IP
						}
						else
						{
							// Something highly unexpected happened, so return false to force an abort
							return false;
						}
					}
					$index++;
				}
			}

		}

		return true;

	}

}
