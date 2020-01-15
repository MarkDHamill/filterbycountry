<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbservices\filterbycountry\acp;

/**
 * Filter by country ACP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\phpbbservices\filterbycountry\acp\main_module',
			'title'		=> 'ACP_FBC',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_FBC_TITLE_SHORT',
					'auth'	=> 'ext_phpbbservices/filterbycountry && acl_a_board',
					'cat'	=> array('ACP_FBC')
				),
				'stats'	=> array(
					'title'	=> 'ACP_FBC_STATS',
					'auth'	=> 'ext_phpbbservices/filterbycountry && acl_a_board',
					'cat'	=> array('ACP_FBC')
				),
			),
		);
	}
}
