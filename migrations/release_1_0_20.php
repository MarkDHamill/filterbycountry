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

class release_1_0_20 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\filterbycountry\migrations\release_1_0_18',
		);
	}

	public function revert_data()
	{
		return array(array('custom', array(array($this, 'remove_files'))));
	}

	public function remove_files()
	{

		// Remove the extension's directory and any files inside it.
		global $phpbb_container;

		$filesystem = $phpbb_container->get('filesystem');
		$filepath = $this->phpbb_root_path . 'store/phpbbservices/filterbycountry';
		if ($filesystem->exists($filepath))
		{
			$filesystem->remove($filepath);
		}

	}


}