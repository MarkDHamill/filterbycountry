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

class release_1_0_18 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\filterbycountry\migrations\release_1_0_14',
			'\phpbb\db\migration\data\v330\v330',
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('phpbbservices_filterbycountry_redirect_uri', '')),
		);
	}

}

