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

class release_1_0_7 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\filterbycountry\migrations\install_schema',
			'\phpbb\db\migration\data\v330\v330',
		);
	}

	public function update_data()
	{

		// Add new config variable for ignoring bots
		return array(
			array('config.add', array('phpbbservices_filterbycountry_ignore_bots', 0)),
			array('custom', array(array($this, 'fix_unknown_countries'))),
		);

	}

	public function fix_unknown_countries()
	{

		// Replace country code ?? (unknown) with wo (World) to avoid potential Windows file matching issue.
		$this->db->sql_query('UPDATE ' . $this->table_prefix . "fbc_stats
			SET country_code = 'WO'
			WHERE country_code = '??'");

	}

}
