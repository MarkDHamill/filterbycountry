<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbservices\filterbycountry\migrations;

class install_acp_module extends \phpbb\db\migration\container_aware_migration
{

	public function effectively_installed()
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND module_langname = 'ACP_FBC_TITLE'";
		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);
		return $module_id !== false;
	}

	public static function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('phpbbservices_filterbycountry_allow', 1)),
			array('config.add', array('phpbbservices_filterbycountry_allow_out_of_country_logins', 1)),
			array('config.add', array('phpbbservices_filterbycountry_cron_task_last_gc', 0)),
			array('config.add', array('phpbbservices_filterbycountry_ip_not_found_allow', 1)),
			array('config.add', array('phpbbservices_filterbycountry_keep_statistics', 0)),
			array('config.add', array('phpbbservices_filterbycountry_statistics_start_date', 0)),
			array('config.add', array('phpbbservices_filterbycountry_log_access_errors', 0)),
			array('config.add', array('phpbbservices_filterbycountry_test_ip', '')),
			array('config_text.add', array('phpbbservices_filterbycountry_country_codes', '')),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_FBC'
			)),
			array('module.add', array(
				'acp',
				'ACP_FBC',
				array(
					'module_basename'	=> '\phpbbservices\filterbycountry\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
			array('module.add', array(
				'acp',
				'ACP_FBC',
				array(
					'module_basename'	=> '\phpbbservices\filterbycountry\acp\main_module',
					'modes'				=> array('stats'),
				),
			)),
		);
	}

	public function revert_data()
	{
		return array(array('custom', array(array($this, 'remove_files'))));
	}

	public function remove_files()
	{

		// Clean up. Remove the extension's filterbycountry directory and the Maxmind database inside it, which likely exists.

		$filesystem = $this->container->get('filesystem');

		$path = $this->phpbb_root_path . 'store/phpbbservices/filterbycountry';

		if ($filesystem->exists($path))
		{
			$filesystem->remove($path);
		}

	}

}
