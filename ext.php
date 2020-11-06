<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
namespace phpbbservices\filterbycountry;
/**
 * @ignore
 */
class ext extends \phpbb\extension\base
{

	public function is_enableable()
	{

		global $phpbb_root_path;

		$config = $this->container->get('config');

		if (
			phpbb_version_compare($config['version'], '3.3.0', '>=') &&
			phpbb_version_compare($config['version'], '4.0', '<') &&
			ini_get('allow_url_fopen') &&
			extension_loaded('curl') &&
			extension_loaded('dom') &&
			extension_loaded('Phar') &&
			is_writable ( $phpbb_root_path . '/store' )
		)
		{
			// Conditions met to install extension
			return true;
		}
		else
		{
			$language = $this->container->get('language');
			$language->add_lang(array('common'), 'phpbbservices/filterbycountry');
			$message_type = E_USER_WARNING;
			$message = $language->lang('ACP_FBC_REQUIREMENTS');
			trigger_error($message, $message_type);
			return false;
		};

	}

}