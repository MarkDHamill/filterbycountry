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

class release_1_0_9 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\filterbycountry\migrations\release_1_0_7',
			'\phpbb\db\migration\data\v330\v330',
		);
	}

	public function update_data()
	{

		// Add new config variable for storing license key and its validity
		return array(
			array('config.add', array('phpbbservices_filterbycountry_license_key', '')),
			array('config.add', array('phpbbservices_filterbycountry_license_key_valid', 1)),
		);
	}

}
