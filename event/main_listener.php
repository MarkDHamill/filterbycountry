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

		// If the license key has not been entered or is not valid, the MaxMind database integration won't work. The
		// database may not have been downloaded yet. So in this event, the extension is not yet configured properly, in
		// which case we want to exit this function, allowing all traffic until this is true.
		if ($this->config['phpbbservices_filterbycountry_license_key_valid'] == 0 || strlen(trim($this->config['phpbbservices_filterbycountry_license_key'])) !== 16)
		{
			return;
		}

		// Get a list of country codes of interest and place in an array for easy processing
		$country_codes = explode(',', $this->config_text->get('phpbbservices_filterbycountry_country_codes'));
		if (count($country_codes) === 0)
		{
			// If the administrator hasn't selected any countries to allow or restrict, the extension is effectively disabled.
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

		// Allow a specific set of countries (1) or restrict only certain countries (0)?
		$allow_countries = (bool) $this->config['phpbbservices_filterbycountry_allow'];

		// Keep statistics?
		$keep_statistics = (bool) $this->config['phpbbservices_filterbycountry_keep_statistics'];

		// In test mode, the test IP set in the ACP is used. IPs in HTTP headers are ignored.
		$test_mode = (bool) (trim($this->config['phpbbservices_filterbycountry_test_ip']) !== '');

		// Create an array of candidate IPs for testing. Any valid IPs are placed in the $this->user_ips array along
		// with the associated country code and country name.
		$ips_to_test = array();
		if ($test_mode)
		{
			$ips_to_test[] = trim($this->config['phpbbservices_filterbycountry_test_ip']);
			$this->test_ips($ips_to_test);
		}
		else
		{
			// These HTTP headers contain possible originating IP addresses. REMOTE_ADDR is the one most typically used
			// and should always be present. Others may be used by CDNs or in other special situations.
			$http_headers =
				array(
					'REMOTE_ADDR', 'HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
					'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_COMING_FROM', 'HTTP_PROXY_CONNECTION',
					'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_COMING_FROM', 'HTTP_VIA',	'X_FORWARDED_FOR'
				);

			// Examine all potentially relevant HTTP headers and create an array of all IP addresses found in these headers.
			// In most cases only REMOTE_ADDR is present and contains the relevant IP.
			foreach ($http_headers as $http_header)
			{
				$ips_to_test = explode(',', $this->request->server($http_header, ''));

				if ($ips_to_test[0] !== '')    // Array is not empty
				{
					$success = $this->test_ips($ips_to_test);
					if (!$success)
					{
						// A major error has occurred, probably a bad MaxMind database. Record the issue. All traffic is
						// thus allowed until the underlying technical issue is fixed to avoid bringing everything down.
						$this->log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_MAXMIND_ERROR');
						return;
					}
				}
			}
		}

		$valid_ips_count = count($this->user_ips);	// Number of valid IP addresses found

		if ($valid_ips_count == 0)
		{
			// No valid IPs were found! This is strange. Let's create a pseudo-IP instead of 0.0.0.0 and assign it to no country.
			$this->user_ips[$valid_ips_count]['ip'] = '0.0.0.0';
			$this->user_ips[$valid_ips_count]['country_code'] = constants::ACP_FBC_COUNTRY_NOT_FOUND;
			$this->user_ips[$valid_ips_count]['country_name'] = $this->helper->get_country_name(constants::ACP_FBC_COUNTRY_NOT_FOUND);    // Add country name to array, for reporting.
		}

		// The following matrix is used to decide the permission to apply.
		//
		// Decision matrix:
		//
		// $request_type    Loop $request_type is
		// was 		    |	Allow (0)		Restrict (1)	Outside (2)
		// --------------------------------------------------------------
		// Allow (0)	|	Allow (0)		Restrict (1)	Outside (2)  <-- new request type
		// Restrict (1)	|	Restrict (1)	Restrict (1)	Outside (2)  <-- new request type
		// Outside (2)	|	Outside	(2)		Outside (2)		Outside (2)  <-- new request type

		$request_type = constants::ACP_FBC_REQUEST_ALLOW;	// Allow by default, since it has the lowest value and only a more restrictive permission can be applied.

		foreach ($this->user_ips as $user_ip)
		{
			$country_code = $user_ip['country_code'];
			$ip = $user_ip['ip'];

			$this_request_type = $this->test_country($allow_countries, $country_code, $ip, $country_codes);

			// The resulting correct request type for IPs examined so far is simply the highest constant number value
			$request_type = max($request_type, $this_request_type);
		}

		if ($request_type == constants::ACP_FBC_REQUEST_RESTRICT && $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
		{
			// In this condition, you can access the board if you are an actively registered user and are already
			// logged in, as evidenced by the user_type, which won't be set for normal users and founders unless
			// you are already logged in. Inactive users and bots are not allowed in. You have to be already logged
			// in to be annotated as a founder or normal user.

			if ($keep_statistics && in_array($this->user->data['user_type'], array(USER_FOUNDER, USER_NORMAL)))
			{
				$this->save_access($request_type, $ignore_bots, $allow_countries, $country_codes);
				return;
			}

			// If not logged in, you are at least allowed to access the login page when this setting enabled.
			if ($keep_statistics && stripos($this->user->page['page'], 'ucp.' . $this->phpEx) === 0 && $this->request->variable('mode', '') == 'login')
			{
				$this->save_access($request_type, $ignore_bots, $allow_countries, $country_codes);
				return;
			}
		}

		if ($keep_statistics)
		{
			// Log this access
			$this->save_access($request_type, $ignore_bots, $allow_countries, $country_codes);
		}

		if ($request_type == constants::ACP_FBC_REQUEST_OUTSIDE || $request_type == constants::ACP_FBC_REQUEST_ALLOW)
		{
			// In this condition, you can access the board if you are an actively registered user and are already
			// logged in, or the request is allowed.
			return;
		}

		// Not allowed to see board content, so present warning message. Provide a login link if allowed.
		if ((bool) $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'])
		{
			@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS_LOGIN', $this->get_disallowed_countries($this->user_ips), $this->phpbb_root_path . "ucp.$this->phpEx?mode=login"), E_USER_WARNING);
		}
		else
		{
			if (trim($this->config['phpbbservices_filterbycountry_redirect_uri']) !== '')
			{
				redirect($this->config['phpbbservices_filterbycountry_redirect_uri']);
			}
			@trigger_error($this->language->lang('ACP_FBC_DENY_ACCESS', $this->get_disallowed_countries($this->user_ips)), E_USER_WARNING);
		}

	}

	private function test_country($allow_countries, $country_code, $ip, $country_codes)
	{

		// Returns the request type (allow, restrict, outside)
		//
		// $allow_countries - allow or restrict value from the configuration variable
		// $country_code - the 2 digit ISO country code to test
		// $ip - IP address
		// $country_codes - an array of allowed or restricted country codes, from parsing the config text variable

		// This controls the logic that is used when requests should be rejected unless the user is logged in and access
		// is permitted in this case.
		$apply_outside = ((bool) $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins']) &&
			in_array($this->user->data['user_type'], array(USER_FOUNDER, USER_NORMAL));

		if ($allow_countries)	// Only allow in from selected countries
		{
			if (in_array($country_code, $country_codes))
			{
				$this_request_type = constants::ACP_FBC_REQUEST_ALLOW;	// In one of the selected countries, so allow
			}
			else
			{
				// If not from one of the selected countries, possibly allow in if they are already logged in
				$this_request_type = ($apply_outside) ? constants::ACP_FBC_REQUEST_OUTSIDE : constants::ACP_FBC_REQUEST_RESTRICT;
			}
		}
		else	// Only allow in if NOT from selected countries
		{
			if (in_array($country_code, $country_codes))
			{
				// If from one of the selected countries, possibly allow in if they are already logged in
				$this_request_type = ($apply_outside) ? constants::ACP_FBC_REQUEST_OUTSIDE : constants::ACP_FBC_REQUEST_RESTRICT;
			}
			else
			{
				$this_request_type = constants::ACP_FBC_REQUEST_ALLOW;	// Not in one of the countries to not let in.
			}
		}

		return $this_request_type;

	}

	private function save_access($request_type, $ignore_bots, $allow_countries, $country_codes)
	{

		// Records type of access (allow, restrict or outside) in the phpbb_fbc_stats table
		// Parameters:
		//		$request_type = 0 (allow), 1 (restrict) or 2 (outside)
		//		$ignore_bots = ignore bots settings (true or false)
		//		$allow_countries = allow/restrict setting on the settings page
		//		$country_codes = array of allowed or restricted country codes from the settings page

		if (($this->request->is_ajax()) || ($ignore_bots && $this->user->data['user_type'] === USER_IGNORE))
		{
			// Ajax requests should never be counted. Bots should be ignored too if that setting applies.
			return;
		}

		$now = time();
		$used_countries = array();

		$seconds = (int) $this->config['phpbbservices_filterbycountry_seconds'];

		// We need to use a database transaction to ensure counts don't inadvertently change and become inconsistent.
		$this->db->sql_transaction('begin');

		for ($ptr=0; $ptr < count($this->user_ips); $ptr++)
		{

			// We want to prevent the same country being counted twice for the process. This can happen if the same
			// IP address is found in multiple HTTP headers, leading to a SQL error because you are trying to insert
			// a row that's already there.
			$country_code = $this->user_ips[$ptr]['country_code'];
			if (in_array($country_code, $used_countries))
			{
				continue;
			}

			if ($seconds > 0)
			{
				// If this country was recently saved (within last x seconds) we don't want to store it again as these
				// appear to be bogus statistics.
				$sql_ary = array(
					'SELECT' => '*',
					'FROM'   => array(
						$this->fbc_stats_table => 'fbc',
					),
					'WHERE'  => "country_code = '" . $this->db->sql_escape($country_code) . "' AND timestamp >= " . ($now - $seconds),
				);

				$sql = $this->db->sql_build_query('SELECT', $sql_ary);
				$result = $this->db->sql_query($sql);
				$rowset = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);
				if (count($rowset) > 0)
				{
					continue;    // Don't record this statistic. The last one was recorded at too close an interval.
				}
			}

			$sql_ary = array(
				'SELECT'	=> 'allowed, not_allowed, outside',
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
				$outside_value = 0;
			}
			else
			{
				// Retrieve current count
				$update = true;
				$allowed_value = (int) $rowset[0]['allowed'];
				$not_allowed_value = (int) $rowset[0]['not_allowed'];
				$outside_value = (int) $rowset[0]['outside'];
			}
			$this->db->sql_freeresult($result);

			// If there are IPs from multiple countries, we want to store the correct statistic for the applied country.
			// For example, if we allow in only the Russian Federation but there is also an Australia IP, the net result
			// is to disallow Australia IP, but we want to correctly note that the Russian Federation IP is allowed.

			if (count($this->user_ips) > 0)
			{
				if ($allow_countries && in_array($country_code, $country_codes))	// allow
				{
					$allowed_value++;
					$this->user_ips[$ptr]['disallowed'] = 0;
				}
				else if (!$allow_countries && !in_array($country_code, $country_codes))	// restrict
				{
					$allowed_value++;
					$this->user_ips[$ptr]['disallowed'] = 0;
				}
				else
				{
					// Increment the allowed, not_allowed and outside column values
					if ($request_type === constants::ACP_FBC_REQUEST_ALLOW)
					{
						$allowed_value++;
						$this->user_ips[$ptr]['disallowed'] = 0;
					}
					else if ($request_type === constants::ACP_FBC_REQUEST_RESTRICT)
					{
						$not_allowed_value++;
						$this->user_ips[$ptr]['disallowed'] = 1;
					}
					else if ($request_type === constants::ACP_FBC_REQUEST_OUTSIDE)
					{
						$outside_value++;
						$this->user_ips[$ptr]['disallowed'] = 0;
					}
				}
			}
			else
			{
				// Increment the allowed, not_allowed and outside column values
				if ($request_type === constants::ACP_FBC_REQUEST_ALLOW)
				{
					$allowed_value++;
					$this->user_ips[$ptr]['disallowed'] = 0;
				}
				else if ($request_type === constants::ACP_FBC_REQUEST_RESTRICT)
				{
					$not_allowed_value++;
					$this->user_ips[$ptr]['disallowed'] = 1;
				}
				else if ($request_type === constants::ACP_FBC_REQUEST_OUTSIDE)
				{
					$outside_value++;
					$this->user_ips[$ptr]['disallowed'] = 0;
				}
			}

			// Update the database row if it exists, otherwise queue it for inserting
			$insert_sql_ary = array();
			if ($update)
			{
				$update_sql_ary = array(
					'allowed'		=> (int) $allowed_value,
					'not_allowed'	=> (int) $not_allowed_value,
					'outside'		=> (int) $outside_value,
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
					'outside'		=> (int) $outside_value,
				);
			}
			$this->db->sql_query($sql);
			$used_countries[] = $this->user_ips[$ptr]['country_code'];
			next($this->user_ips);

		}

		// Do all insert statements at once for efficiency. Unless there are multiple IPs in various HTTP headers,
		// there should be no more than one row inserted.
		if (isset($insert_sql_ary) && count($insert_sql_ary) > 0)
		{
			if (count($insert_sql_ary) > 1)
			{
				$insert_sql_ary = $this->fix_duplicate_key_inserts($insert_sql_ary);
			}
			$this->db->sql_multi_insert($this->fbc_stats_table, $insert_sql_ary);
		}

		$this->db->sql_transaction('commit');

		// Log the request if logging is enabled. Only restricted attempts are logged.
		if ($this->config['phpbbservices_filterbycountry_log_access_errors'] && $request_type === constants::ACP_FBC_REQUEST_RESTRICT && count($this->user_ips) > 0)
		{
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_BAD_ACCESS', false, array($this->user->data['username'], $this->get_disallowed_ips($this->user_ips), $this->get_disallowed_countries($this->user_ips)));
		}

		return;

	}

	private function fix_duplicate_key_inserts($insert_ary)
	{

		// This function prevents a potential duplicate key error occurring on a SQL insert into the phpbb_fbc_stats table
		// by aggregating allowed, not allowed and outside counts into a single row as necessary.

		$cleaned_ary = array();
		foreach ($insert_ary as $insert_row)
		{
			$found_match = false;
			$index = 0;
			foreach ($cleaned_ary as $cleaned_row)
			{
				if ($insert_row['country_code'] == $cleaned_row['country_code'] &&
					$insert_row['timestamp'] == $cleaned_row['timestamp'])
				{
					$found_match = true;
					$cleaned_ary[$index]['allowed'] += $insert_row['allowed'];
					$cleaned_ary[$index]['not_allowed'] += $insert_row['not_allowed'];
					$cleaned_ary[$index]['outside']+= $insert_row['outside'];
					$index++;
				}
			}
			if (!$found_match)
			{
				$cleaned_ary[] = array(
					'country_code'	=> $insert_row['country_code'],
					'timestamp'		=> $insert_row['timestamp'],
					'allowed'		=> $insert_row['allowed'],
					'not_allowed'	=> $insert_row['not_allowed'],
					'outside'		=> $insert_row['outside']
				);
			}
		}
		return $cleaned_ary;

	}

	private function get_disallowed_countries(&$user_ips)
	{

		// Returns a comma delimited list of country names that were disallowed for the various IPs associated with the request.
		$unapproved_countries = array();
		foreach ($user_ips as $user_ip)
		{
			if ($user_ip['disallowed'] == 1)
			{
				$unapproved_countries[] = $user_ip['country_name'];
			}
		}
		if (count($unapproved_countries) == 0)
		{
			$unapproved_countries[] = $this->helper->get_country_name(constants::ACP_FBC_COUNTRY_NOT_FOUND);
		}
		return implode($this->language->lang('COMMA_SEPARATOR'), array_unique($unapproved_countries));

	}

	private function get_disallowed_ips(&$user_ips)
	{

		// Returns a comma delimited list of IPs that were disallowed for the various IPs associated with the request.
		$unapproved_ips = array();
		foreach ($user_ips as $user_ip)
		{
			if ($user_ip['disallowed'] == 1)
			{
				$unapproved_ips[] = $user_ip['ip'];
			}
		}
		return implode($this->language->lang('COMMA_SEPARATOR'), array_unique($unapproved_ips));

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
			$validated_ips = array();

			foreach ($possible_ips as $possible_ip)
			{

				if (filter_var($possible_ip, FILTER_VALIDATE_IP))	// Invalid IPs are ignored. Many of these odd HTTP headers won't place valid IPs in them.
				{
					// Reject IP if already in array. This can happen if various HTTP headers carry the same IP.
					if (in_array($possible_ip, $validated_ips))
					{
						continue; // Do next
					}
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
					$validated_ips[] = $possible_ip;
					$index++;
				}
			}

		}

		return true;

	}

}
