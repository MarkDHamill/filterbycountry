<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbservices\filterbycountry\controller;

/**
 * Filter by country ACP controller.
 */
class acp_controller
{

	protected $config;
	protected $config_text;
	protected $db;
	protected $helper;
	protected $language;
	protected $log;
	protected $request;
	protected $table_prefix;
	protected $template;
	protected $user;
	protected $u_action;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config							$config			Config object
	 * @param \phpbb\language\language						$language		Language object
	 * @param \phpbb\log\log								$log			Log object
	 * @param \phpbb\request\request						$request		Request object
	 * @param \phpbb\template\template						$template		Template object
	 * @param \phpbb\user									$user			User object
	 * @param \phpbb\config\db_text							$config_text	The config text
	 * @param \phpbbservices\filterbycountry\core\common 	$helper			Extension's helper object
	 * @param \phpbb\db\driver\factory 						$db 			The database factory object
	 * @param $table_prefix 								string				Prefix for phpbb's database tables
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, \phpbb\log\log $log, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\config\db_text $config_text, \phpbbservices\filterbycountry\core\common $helper, \phpbb\db\driver\factory $db, $table_prefix)
	{

		$this->config	= $config;
		$this->config_text = $config_text;
		$this->db 		= $db;
		$this->helper 	= $helper;
		$this->language	= $language;
		$this->log		= $log;
		$this->request	= $request;
		$this->table_prefix = $table_prefix;
		$this->template	= $template;
		$this->user		= $user;

	}

	/**
	 * Display the options a user can configure for this extension.
	 *
	 * @return void
	 */
	public function display_options()
	{

		// Add our common language file
		$this->language->add_lang('common', 'phpbbservices/filterbycountry');

		// Create a form key for preventing CSRF attacks
		add_form_key('phpbbservices_filterbycountry_acp');

		// Create an array to collect errors that will be output to the user
		$errors = array();

		// Get the mode, indirectly from the URL
		$mode = '';
		$url = $this->request->server('REQUEST_URI');
		if (stristr($url, "mode=settings"))
		{
			$mode = 'settings';
		}
		else if (stristr($url, "mode=stats"))
		{
			$mode = 'stats';
		}

		// Is the form being submitted to us?
		if ($this->request->is_set_post('submit'))
		{
			// Test if the submitted form is valid
			if (!check_form_key('phpbbservices_filterbycountry_acp'))
			{
				$errors[] = $this->language->lang('FORM_INVALID');
			}

			// If no errors, process the form data
			if (empty($errors))
			{
				if ($mode == 'settings')
				{
					// Save the setting for selected countries to be either allowed or restricted
					$this->config->set('phpbbservices_filterbycountry_allow', $this->request->variable('phpbbservices_filterbycountry_allow', 0));

					// Save log setting on whether to allow out of country logins
					$this->config->set('phpbbservices_filterbycountry_allow_out_of_country_logins', $this->request->variable('phpbbservices_filterbycountry_allow_out_of_country_logins', 0));

					// Save the setting for whether IPs without a known country should be either allowed or restricted
					$this->config->set('phpbbservices_filterbycountry_ip_not_found_allow', $this->request->variable('phpbbservices_filterbycountry_ip_not_found_allow', 0));

					// Save log setting on whether to log access errors
					$this->config->set('phpbbservices_filterbycountry_log_access_errors', $this->request->variable('phpbbservices_filterbycountry_log_access_errors', 0));

					// Save the keep statistics setting
					$save_statistics = $this->request->variable('phpbbservices_filterbycountry_keep_statistics', 0);
					$this->config->set('phpbbservices_filterbycountry_keep_statistics', $save_statistics);

					if ($save_statistics)
					{
						// Set the statistics start date to the current time
						$this->config->set('phpbbservices_filterbycountry_statistics_start_date', time());
					}
					else
					{
						// Remove all statistics if $save_statistics is false
						$sql = 'DELETE FROM ' . $this->table_prefix . 'fbc_stats';
						$this->db->sql_query($sql);

						// Also, reset the statistics start date
						$this->config->set('phpbbservices_filterbycountry_statistics_start_date', 0);
					}

					// Save any selected country codes to the database. To save space they will be saved as a string in the phpbb_config_text table. Since there are hundreds of
					// country codes, the phpbb_config_text table is used since we may need more than 254 characters stored.
					$country_codes = $this->request->variable('phpbbservices_filterbycountry_country_codes', array('' => ''));
					$country_codes_str = (count($country_codes) > 0) ? implode(',', $country_codes) : '';
					$this->config_text->set('phpbbservices_filterbycountry_country_codes', $country_codes_str);

					// Add option settings change action to the admin log
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FILTERBYCOUNTRY_SETTINGS');

					// Option settings have been updated and logged
					// Confirm this to the user and provide link back to previous page
					trigger_error($this->language->lang('ACP_FBC_SETTING_SAVED') . adm_back_link($this->u_action));
				}
			}
		}

		$s_errors = !empty($errors);

		// First test if the GeoLite2-Country-Country.mmdb database exists in /store/phpbbservices/filterbycountry directory.
		// If it doesn't, the function will create the directory and populate it
		$this->helper->download_maxmind();

		// Set output variables for display in the template
		if ($mode == 'settings')
		{
			$this->template->assign_vars(array(
				'COUNTRY_CODES' 					=> $this->config_text->get('phpbbservices_filterbycountry_country_codes'),
				'ERROR_MSG'     					=> $s_errors ? implode('<br />', $errors) : '',
				'FBC_ALLOW_OUT_OF_COUNTRY_LOGINS'	=> (bool) $this->config['phpbbservices_filterbycountry_allow_out_of_country_logins'],
				'FBC_ALLOW_RESTRICT'				=> (bool) $this->config['phpbbservices_filterbycountry_allow'],
				'FBC_IP_NOT_FOUND_ALLOW_RESTRICT'	=> (bool) $this->config['phpbbservices_filterbycountry_ip_not_found_allow'],
				'FBC_KEEP_STATISTICS'				=> (bool) $this->config['phpbbservices_filterbycountry_keep_statistics'],
				'FBC_LOG_ACCESS_ERRORS'				=> (bool) $this->config['phpbbservices_filterbycountry_log_access_errors'],

				'S_ERROR'				=> $s_errors,
				'S_INCLUDE_FBC_JS'		=> true,
				'S_SETTINGS'			=> true,

				'U_ACTION' 		=> $this->u_action,
			));
		}
		else if ($mode == 'stats')
		{
			if ((bool) $this->config['phpbbservices_filterbycountry_keep_statistics'])
			{

				// Get distinct country codes in the table. We only want to fetch statistics for
				// countries that have actually garnered hits.
				$distinct_countries = array();
				$sql = 'SELECT DISTINCT country_code 
					FROM ' . $this->table_prefix . 'fbc_stats 
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
					// To present a report by country, we need to put the information in tabular form. Since country names
					// could vary based on language, we'll parse the language string ACP_FBC_OPTIONS for country codes and
					// country names.

					$dom = new \DOMDocument();
					$dom->loadHTML($this->language->lang('ACP_FBC_OPTIONS'));
					$xml_countries = $dom->getElementsByTagName('option');

					// Add unknown at the top of the countries array
					$countries = array();
					$countries['??'] = $this->language->lang('FBC_UNKNOWN');

					foreach ($xml_countries as $xml_country)
					{
						// Place into an array, with the value of the option the array element key
						$countries[$xml_country->getAttribute('value')] = $xml_country->nodeValue;
						next($countries);
					}

					// Now add all countries to the report table that exist in the table.
					foreach ($distinct_countries as $distinct_country)
					{

						// Get allowed and not allowed page requests for country
						$sql = 'SELECT sum(allowed) AS allowed_count, sum(not_allowed) AS not_allowed_count
							FROM ' . $this->table_prefix . "fbc_stats 
							WHERE country_code = '" . $distinct_country . "'";

						$result = $this->db->sql_query($sql);
						$row = $this->db->sql_fetchrow($result);

						$allowed_count = (int) $row['allowed_count'];
						$not_allowed_count = (int) $row['not_allowed_count'];
						$this->db->sql_freeresult($result);

						// Create a row in the report table
						$this->template->assign_block_vars('countries', array(
							'TEXT'        	=> $countries[$distinct_country],
							'ALLOWED'		=> $allowed_count,
							'RESTRICTED'	=> $not_allowed_count,
						));
					}

					// Other template variables used by this page
					$this->template->assign_vars(array(
						'L_ACP_FBC_TITLE'					=> $this->language->lang('ACP_FBC_STATS_TITLE'),
						'L_ACP_FBC_TITLE_EXPLAIN'			=> $this->language->lang('ACP_FBC_STATS_TITLE_EXPLAIN', date($this->user->data['user_dateformat'], $this->config['phpbbservices_filterbycountry_statistics_start_date'])),
						'S_INCLUDE_FBC_CSS'					=> true,
						'S_SETTINGS'						=> false,
					));
				}
				else
				{
					trigger_error($this->language->lang('ACP_FBC_NO_STATISTICS_YET'));
				}

			}
			else
			{
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
}
