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

use phpbbservices\filterbycountry\constants\constants;

class install_schema extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v320\v320',
			'\phpbbservices\filterbycountry\migrations\install_acp_module',
		);
	}

	public function update_schema()
	{

		return array(

			'add_tables'    => array(
				$this->table_prefix . constants::ACP_FBC_STATS_TABLE        => array(
					'COLUMNS'       	=> array(
						'country_code' 	=> array('VCHAR:2', ''),
						'timestamp' 	=> array('TIMESTAMP', 0),
						'allowed'		=> array('TINT:4', 0),
						'not_allowed'	=> array('TINT:4', 0),
					),
					'PRIMARY_KEY'       => array('country_code', 'timestamp'),
					'KEYS' => array(
						'fbc_ts_cc'     => array('INDEX', array('timestamp', 'country_code')),
					),
                ),
			)

		);

	}

	public function revert_schema()
	{
		return array(

			'drop_tables'    => array(
				$this->table_prefix . 'fbc_stats',
			)

		);

	}

	public function revert_data()
	{
		// Clean up. Remove the extension's filterbycountry directory and the Maxmind database inside it, which likely exist.
		$this->rrmdir('./../store/phpbbservices/filterbycountry');
		return array();
	}

	private function rrmdir($dir)
	{

		// Recursively removes files in a directory
		if (is_dir($dir))
		{
			$inodes = scandir($dir);
			if (is_array($inodes))
			{
				foreach ($inodes as $inode)
				{
					if ($inode != "." && $inode != "..")
					{
						if (is_dir($dir . "/" . $inode))
						{
							$success = rrmdir($dir . "/" . $inode);
						}
						else
						{
							$success = unlink($dir . "/" . $inode);
						}
						if (!$success)
						{
							return false;
						}
					}
				}
				rmdir($dir);
			}
		}

	}

}
