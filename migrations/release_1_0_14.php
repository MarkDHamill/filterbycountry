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

class release_1_0_14 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\filterbycountry\migrations\release_1_0_9',
			'\phpbb\db\migration\data\v330\v330',
		);
	}

	public function update_schema()
	{
		return array(
			'add_columns'        => array(
				$this->table_prefix . 'fbc_stats'    => array(
					'outside'		=> array('TINT:4', 0),
				),
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('phpbbservices_filterbycountry_seconds', 1)),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'        => array(
				$this->table_prefix . 'fbc_stats'	=> array('outside'),
			),
		);
	}

}
