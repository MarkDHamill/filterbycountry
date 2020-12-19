<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbservices\filterbycountry\controller;

use phpbbservices\filterbycountry\constants\constants;

/**
 * Filter by country ACP controller.
 */
class acp_controller
{

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
	protected $template;
	protected $user;
	protected $u_action;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config							$config				Config object
	 * @param \phpbb\language\language						$language			Language object
	 * @param \phpbb\log\log								$log				Log object
	 * @param \phpbb\request\request						$request			Request object
	 * @param \phpbb\template\template						$template			Template object
	 * @param \phpbb\user									$user				User object
	 * @param \phpbb\config\db_text							$config_text		The config text object
	 * @param \phpbbservices\filterbycountry\core\common 	$helper				Extension's helper object
	 * @param \phpbb\db\driver\factory 						$db 				The database factory object
	 * @param string										$phpbb_root_path	Relative path to phpBB root
	 * @param string                   						$php_ext         	PHP file suffix
	 * @param \phpbb\filesystem 							$filesystem			The filesystem object
	 * @param \phpbbservices\filterbycountry\				$fbc_stats_table	Extension's statistics table
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, \phpbb\log\log $log, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\config\db_text $config_text, \phpbbservices\filterbycountry\core\common $helper, \phpbb\db\driver\factory $db, $phpbb_root_path, $php_ext, \phpbb\filesystem\filesystem $filesystem, $fbc_stats_table)
	{

		$this->config	= $config;
		$this->config_text = $config_text;
		$this->db 		= $db;
		$this->fbc_stats_table	= $fbc_stats_table;
		$this->filesystem = $filesystem;
		$this->helper 	= $helper;
		$this->language	= $language;
		$this->log		= $log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx 	= $php_ext;
		$this->request	= $request;
		$this->template	= $template;
		$this->user		= $user;

	}

	/**
	 * Display the options a user can configure for this extension.
	 *
	 * @return void
	 */
	public function display_options($mode)
	{

		$this->language->add_lang('common', 'phpbbservices/filterbycountry');

		// Create a form key for preventing CSRF attacks
		add_form_key('phpbbservices_filterbycountry_acp');

		// Create an array to collect errors that will be output to the user
		$errors = array();

		// Is the form being submitted to us?
		if ($this->request->is_set_post('submit'))
		{

			// Test if the submitted form is valid
			if (!check_form_key('phpbbservices_filterbycountry_acp'))
			{
				$errors[] = $this->language->lang('FORM_INVALID');
			}

			// Show error if test IP is not valid IPV4 or IPV6
			$test_ip = $this->request->variable('phpbbservices_filterbycountry_test_ip','');
			if (trim($test_ip) !== '' && !filter_var($test_ip, FILTER_VALIDATE_IP))
			{
				$errors[] = $this->language->lang('ACP_FBC_TEST_IP_BAD');
			}

			// If no errors, process the form data
			if (empty($errors))
			{
				if ($mode == 'settings')
				{
					// Save the setting for the license key
					$this->config->set('phpbbservices_filterbycountry_license_key', $this->request->variable('phpbbservices_filterbycountry_license_key', ''));

					// Save the setting for selected countries to be either allowed or restricted
					$this->config->set('phpbbservices_filterbycountry_allow', $this->request->variable('phpbbservices_filterbycountry_allow', 0));

					// Save log setting on whether to allow out of country logins
					$this->config->set('phpbbservices_filterbycountry_allow_out_of_country_logins', $this->request->variable('phpbbservices_filterbycountry_allow_out_of_country_logins', 0));

					// Save the setting for whether IPs without a known country should be either allowed or restricted
					$this->config->set('phpbbservices_filterbycountry_ip_not_found_allow', $this->request->variable('phpbbservices_filterbycountry_ip_not_found_allow', 0));

					// Save the setting on whether to log access errors
					$this->config->set('phpbbservices_filterbycountry_log_access_errors', $this->request->variable('phpbbservices_filterbycountry_log_access_errors', 0));

					// Save the keep statistics setting
					$save_statistics = $this->request->variable('phpbbservices_filterbycountry_keep_statistics', 0);
					$this->config->set('phpbbservices_filterbycountry_keep_statistics', $save_statistics);

					// Save the ignore bots setting
					$ignore_bots = $this->request->variable('phpbbservices_filterbycountry_ignore_bots', 0);
					$this->config->set('phpbbservices_filterbycountry_ignore_bots', $ignore_bots);

					// Save the seconds setting
					$seconds = $this->request->variable('phpbbservices_filterbycountry_seconds', 1);
					$this->config->set('phpbbservices_filterbycountry_seconds', $seconds);

					// Save the redirect URI setting
					$redirect_uri = $this->request->variable('phpbbservices_filterbycountry_redirect_uri', '');
					$this->config->set('phpbbservices_filterbycountry_redirect_uri', $redirect_uri);

					// Save the test IP setting
					$test_ip = $this->request->variable('phpbbservices_filterbycountry_test_ip', '');
					$this->config->set('phpbbservices_filterbycountry_test_ip', $test_ip);

					if ($save_statistics)
					{
						// Set the statistics start date to the current time
						$this->config->set('phpbbservices_filterbycountry_statistics_start_date', time());
					}
					else
					{
						// Remove all statistics if $save_statistics is false
						$this->reset_statistics();
						$this->config->set('phpbbservices_filterbycountry_statistics_start_date', 0);	// Make it as if statistics were never collected
					}

					// Save any selected country codes to the database. To save space they will be saved as a string in the phpbb_config_text table. Since there are hundreds of
					// country codes, the phpbb_config_text table is used since we may need more than 254 characters stored.
					$country_codes = $this->request->variable('phpbbservices_filterbycountry_country_codes', array('' => ''));
					$country_codes_str = (!empty($country_codes)) ? implode(',', $country_codes) : '';
					$this->config_text->set('phpbbservices_filterbycountry_country_codes', $country_codes_str);

					// Add option settings change action to the admin log
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_FILTERBYCOUNTRY_SETTINGS');

					trigger_error($this->language->lang('ACP_FBC_SETTING_SAVED') . adm_back_link($this->u_action));
				}
			}
		}

		if ($this->request->is_set_post('reset_stats'))
		{
			// Reset statistics when the Reset stats button is pressed and action is confirmed.
			$this->reset_statistics();

			// Also set the new "from" time for statistics collection
			$this->config->set('phpbbservices_filterbycountry_statistics_start_date', time());

			trigger_error($this->language->lang('ACF_FBC_STATS_RESET') . adm_back_link($this->u_action));
		}

		$s_errors = !empty($errors);

		// First, test if the GeoLite2-Country-Country.mmdb database exists in /store/phpbbservices/filterbycountry directory.
		// If it doesn't, the function will create the directory and populate it, if it can. But make sure the license key is valid
		// before doing this, otherwise an error will occur.

		if ($this->config['phpbbservices_filterbycountry_license_key_valid'] == 1 && strlen(trim($this->config['phpbbservices_filterbycountry_license_key'])) == 16)
		{
			$database_mmdb_file_path = $this->phpbb_root_path . 'store/phpbbservices/filterbycountry/GeoLite2-Country.mmdb';
			if (!$this->filesystem->exists($database_mmdb_file_path))
			{
				// If the database doesn't exist (first time), create it. Note: if the database cannot be created, the
				// function returns false. In this case, we draw attention to the issue so the underlying problem can be fixed.
				if (!$this->helper->download_maxmind())
				{
					@trigger_error($this->language->lang('ACP_FBC_CREATE_DATABASE_ERROR'), E_USER_WARNING);
				}
			}
		}

		// Set output variables for display in the template
		if ($mode == 'settings')
		{

			// Populate the settings page fields

			if ($this->config['phpbbservices_filterbycountry_license_key_valid'] == 0 || strlen(trim($this->config['phpbbservices_filterbycountry_license_key'])) !== 16)
			{
				$errors[] = $this->language->lang('ACP_FBC_INVALID_LICENSE_KEY');
				$s_errors = true;
			}

			$this->template->assign_vars(array(
				'COUNTRY_CODES' 					=> urlencode($this->config_text->get('phpbbservices_filterbycountry_country_codes')),	// Processed by the Javascript
				'ERROR_MSG'     					=> $s_errors ? implode('<br>', $errors) : '',
				'FBC_ALLOW_OUT_OF_COUNTRY_LOGINS'	=> (bool) $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'],
				'FBC_ALLOW_RESTRICT'				=> (bool) $this->config['phpbbservices_filterbycountry_allow'],
				'FBC_IGNORE_BOTS'					=> (bool) $this->config['phpbbservices_filterbycountry_ignore_bots'],
				'FBC_IP_NOT_FOUND_ALLOW_RESTRICT'	=> (bool) $this->config['phpbbservices_filterbycountry_ip_not_found_allow'],
				'FBC_KEEP_STATISTICS'				=> (bool) $this->config['phpbbservices_filterbycountry_keep_statistics'],
				'FBC_LICENSE_KEY'					=> $this->config['phpbbservices_filterbycountry_license_key'],
				'FBC_LOG_ACCESS_ERRORS'				=> (bool) $this->config['phpbbservices_filterbycountry_log_access_errors'],
				'FBC_REDIRECT_URI'					=> $this->config['phpbbservices_filterbycountry_redirect_uri'],
				'FBC_SECONDS'						=> $this->config['phpbbservices_filterbycountry_seconds'],
				'FBC_TEST_IP'						=> $this->config['phpbbservices_filterbycountry_test_ip'],

				'S_ERROR'							=> $s_errors,
				'S_INCLUDE_FBC_JS'					=> true,
				'S_SETTINGS'						=> true,

				'U_ACTION' 							=> $this->u_action,
			));

			// Populate the options list with a list of countries, in the user's language.
			foreach (constants::FBC_COUNTRY_CODES as $key => $value)
			{
				$this->template->assign_block_vars('country', array(
					'CODE'	=> $value,
				));
			}

		}
		else if ($mode == 'stats')
		{

			// Populate the statistics page fields

			if ((bool) $this->config['phpbbservices_filterbycountry_keep_statistics'])
			{

				// Get time limit control values for the page
				$range = $this->request->variable('range', constants::ACP_FBC_NO_LIMIT_VALUE);
				$date_start = trim($this->request->variable('date_start', '')); // Format: yyyy-mm-dd
				$date_end = trim($this->request->variable('date_end', '')); // Format: yyyy-mm-dd

				// Start absolute date range logic
				if ($date_start !== '' && $date_end !== '')
				{
					// Since $date_end will render a timestamp for midnight (00:00:00) let's take it to the end of the day (23:59:59)
					$date_end_ts = (int) (strtotime($date_end) + (24 * 60 * 60) - 1);
					$sql_where = ' WHERE timestamp >= ' . (int) strtotime($date_start) . ' AND timestamp <= ' . (int) $date_end_ts;
					$text_range = $this->language->lang('ACP_FBC_FROM') . $date_start . $this->language->lang('ACP_FBC_TO') . $date_end;
				}
				else if ($date_start !== '' && $date_end === '')
				{
					$sql_where = ' WHERE timestamp >= ' . (int) strtotime($date_start);
					$text_range = $this->language->lang('ACP_FBC_FROM') . $date_start;
				}
				else if ($date_start === '' && $date_end !== '')
				{
					$date_end_ts = (int) (strtotime($date_end) + (24 * 60 * 60) - 1);
					$sql_where = ' WHERE timestamp <= ' . (int) $date_end_ts;
					$text_range = $this->language->lang('ACP_FBC_TO') . $date_end;
				}
				// End absolute date range logic
				// Begin relative date range logic
				else
				{
					$now = time();
					switch ($range)
					{

						case constants::ACP_FBC_NO_LIMIT_VALUE:
						default:
							$sql_where = '';
							$text_range = $this->language->lang('ACP_FBC_NO_LIMIT');
						break;

						case constants::ACP_FBC_LAST_QUARTER_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (90 * 24 * 60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_QUARTER');
						break;

						case constants::ACP_FBC_LAST_MONTH_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (30 * 24 * 60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_MONTH');
						break;

						case constants::ACP_FBC_LAST_TWO_WEEKS_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (14 * 24 * 60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_TWO_WEEKS');
						break;

						case constants::ACP_FBC_LAST_WEEK_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (7 * 24 * 60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_WEEK');
						break;

						case constants::ACP_FBC_LAST_DAY_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (24 * 60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_DAY');
						break;

						case constants::ACP_FBC_LAST_12_HOURS_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (12 * 60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_12_HOURS');
						break;

						case constants::ACP_FBC_LAST_6_HOURS_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (6 * 60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_6_HOURS');
						break;

						case constants::ACP_FBC_LAST_3_HOURS_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (3 * 60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_3_HOURS');
						break;

						case constants::ACP_FBC_LAST_1_HOURS_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (60 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_1_HOURS');
						break;

						case constants::ACP_FBC_LAST_30_MINUTES_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (30 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_30_MINUTES');
						break;

						case constants::ACP_FBC_LAST_15_MINUTES_VALUE:
							$sql_where = ' WHERE timestamp >= ' . (int) ($now - (15 * 60));
							$text_range = $this->language->lang('ACP_FBC_LAST_15_MINUTES');
						break;

					}
				}
				// End relative date range logic

				// Get distinct country codes in the table for the time period wanted. We only want to fetch statistics for
				// countries that have actually garnered hits.
				$distinct_countries = array();
				$sql = 'SELECT DISTINCT country_code 
					FROM ' . $this->fbc_stats_table .
					$sql_where . '
					ORDER BY country_code';
				$result = $this->db->sql_query($sql);
				$rowset = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);

				foreach ($rowset as $row)
				{
					$distinct_countries[] = $row['country_code'];
				}

				if (count($distinct_countries) > 0)
				{

					// Get allowed and not allowed page requests for each country in the phpbb_fbc_stats table
					$sql = 'SELECT country_code, sum(allowed) AS allowed_count, sum(not_allowed) AS not_allowed_count,
							sum(outside) AS outside_count
						FROM ' . $this->fbc_stats_table .
						$sql_where . '
						GROUP BY country_code
						HAVING ' . $this->db->sql_in_set('country_code', $distinct_countries);
					$result = $this->db->sql_query($sql);
					$rowset = $this->db->sql_fetchrowset($result);

					// Add to $rowset a column representing the textual country name, in the user's language
					for ($i=0; $i<count($rowset); $i++)
					{
						if ($rowset[$i]['country_code'] == constants::ACP_FBC_COUNTRY_NOT_FOUND)
						{
							$rowset[$i]['country_name'] = $this->language->lang('ACP_FBC_UNKNOWN');
						}
						else
						{
							$rowset[$i]['country_name'] = $this->language->lang('ACP_FBC_COUNTRY_' . $rowset[$i]['country_code']);
						}
					}

					// The $rowset array must be ordered outside of SQL because the country name is language specific and is not stored in the database.
					$sort_by = substr($this->request->variable('sort', 'ca'),0,1);	// c = country name, a = allowed, r = restricted, o=outside
					$sort_direction = substr($this->request->variable('sort', 'ca'),1,1); // a = ascending, d = descending
					$sort_direction = ($sort_direction == 'a') ? SORT_ASC : SORT_DESC;

					switch ($sort_by)
					{
						case 'c':
						default:
							foreach ($rowset as $key => $row)
							{
								$country_name[$key] = $row['country_name'];
							}
							array_multisort($country_name, $sort_direction, SORT_STRING, $rowset);
						break;

						case 'a':
							foreach ($rowset as $key => $row)
							{
								$allowed_count[$key] = $row['allowed_count'];
								$country_name[$key] = $row['country_name'];
							}
							array_multisort($allowed_count, $sort_direction, SORT_NUMERIC, $country_name, SORT_ASC, $rowset);
						break;

						case 'r':
							foreach ($rowset as $key => $row)
							{
								$not_allowed_count[$key] = $row['not_allowed_count'];
								$country_name[$key] = $row['country_name'];
							}
							array_multisort($not_allowed_count, $sort_direction, SORT_NUMERIC, $country_name, SORT_ASC, $rowset);
						break;

						case 'o':
							foreach ($rowset as $key => $row)
							{
								$outside_count[$key] = $row['outside_count'];
								$country_name[$key] = $row['country_name'];
							}
							array_multisort($outside_count, $sort_direction, SORT_NUMERIC, $country_name, SORT_ASC, $rowset);
						break;
					}

					// Now add all distinct countries to the report
					foreach ($rowset as $row)
					{

						$allowed_count = (int) $row['allowed_count'];
						$not_allowed_count = (int) $row['not_allowed_count'];
						$outside_count = (int) $row['outside_count'];

						// Create a row in the report table
						$flag_path = $this->phpbb_root_path . 'ext/phpbbservices/filterbycountry/flags/' . strtolower($row['country_code']) . '.png';
						$this->template->assign_block_vars('country', array(
							'ALLOWED'		=> $allowed_count,
							'FLAG_PATH'		=> $flag_path,
							'NOT_ALLOWED'	=> $not_allowed_count,
							'RESTRICTED'	=> $not_allowed_count,
							'OUTSIDE'		=> $outside_count,
							'TEXT'        	=> $row['country_name'],
						));

					}
					$this->db->sql_freeresult($result);

					// Other template variables used by this page
					$this->template->assign_vars(array(

						'CURRENT_RANGE'						=> $text_range,

						'L_ACP_FBC_TITLE_EXPLAIN'			=> $this->language->lang('ACP_FBC_STATS_TITLE_EXPLAIN', date($this->user->data['user_dateformat'], $this->config['phpbbservices_filterbycountry_statistics_start_date'])),

						'S_ACP_FBC_LAST_QUARTER_VALUE'		=> constants::ACP_FBC_LAST_QUARTER_VALUE,
						'S_ACP_FBC_LAST_MONTH_VALUE'		=> constants::ACP_FBC_LAST_MONTH_VALUE,
						'S_ACP_FBC_LAST_TWO_WEEKS_VALUE'	=> constants::ACP_FBC_LAST_TWO_WEEKS_VALUE,
						'S_ACP_FBC_LAST_WEEK_VALUE'			=> constants::ACP_FBC_LAST_WEEK_VALUE,
						'S_ACP_FBC_LAST_DAY_VALUE'			=> constants::ACP_FBC_LAST_DAY_VALUE,
						'S_ACP_FBC_LAST_12_HOURS_VALUE'		=> constants::ACP_FBC_LAST_12_HOURS_VALUE,
						'S_ACP_FBC_LAST_6_HOURS_VALUE'		=> constants::ACP_FBC_LAST_6_HOURS_VALUE,
						'S_ACP_FBC_LAST_3_HOURS_VALUE'		=> constants::ACP_FBC_LAST_3_HOURS_VALUE,
						'S_ACP_FBC_LAST_1_HOURS_VALUE'		=> constants::ACP_FBC_LAST_1_HOURS_VALUE,
						'S_ACP_FBC_LAST_30_MINUTES_VALUE'	=> constants::ACP_FBC_LAST_30_MINUTES_VALUE,
						'S_ACP_FBC_LAST_15_MINUTES_VALUE'	=> constants::ACP_FBC_LAST_15_MINUTES_VALUE,
						'S_ACP_FBC_NO_LIMIT_VALUE' 			=> constants::ACP_FBC_NO_LIMIT_VALUE,
						'S_INCLUDE_FBC_CSS'					=> true,
						'S_INCLUDE_FBC_JS'					=> true,
						'S_SETTINGS'						=> false,

						'U_ACTION' 							=> $this->u_action,
						'U_FBC_COUNTRY_A_Z'					=> $this->u_action . '&amp;sort=ca',
						'U_FBC_COUNTRY_ALLOWED_ASC'			=> $this->u_action . '&amp;sort=aa',
						'U_FBC_COUNTRY_ALLOWED_DESC'		=> $this->u_action . '&amp;sort=az',
						'U_FBC_COUNTRY_OUTSIDE_ASC'			=> $this->u_action . '&amp;sort=oa',
						'U_FBC_COUNTRY_OUTSIDE_DESC'		=> $this->u_action . '&amp;sort=oz',
						'U_FBC_COUNTRY_RESTRICTED_ASC'		=> $this->u_action . '&amp;sort=ra',
						'U_FBC_COUNTRY_RESTRICTED_DESC'		=> $this->u_action . '&amp;sort=rz',
						'U_FBC_COUNTRY_Z_A'					=> $this->u_action . '&amp;sort=cz',

					));
				}
				else
				{
					if ($sql_where == '')
					{
						// If no SQL where clause, no statistics have been collected yet
						trigger_error($this->language->lang('ACP_FBC_NO_STATISTICS_YET'));
					}
					else
					{
						// If no results, there are none to report for the date range wanted
						trigger_error($this->language->lang('ACP_FBC_NO_STATISTICS_FOR_RANGE'));
					}
				}

			}
			else
			{
				// The option to collect statistics has not been enabled
				trigger_error($this->language->lang('ACP_FBC_NO_STATISTICS'));
			}
		}

	}

	/**
	 * Set custom form action.
	 *
	 * @param string	$u_action	Custom form action
	 * @return void
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}

	public function reset_statistics()
	{
		// Remove all statistics from the phpbb_fbc_stats table

		$sql = 'DELETE FROM ' . $this->fbc_stats_table;
		$this->db->sql_query($sql);
	}
}
