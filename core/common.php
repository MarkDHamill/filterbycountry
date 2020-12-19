<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbservices\filterbycountry\core;

class common
{

	/**
	 * Constructor
	 */

	protected $config;
	protected $filesystem;
	protected $language;
	protected $phpbb_log;
	protected $phpbb_root_path;
	protected $request;
	protected $user;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\language\language 	$language 			Language object
	 * @param string					$phpbb_root_path	Relative path to phpBB root
	 * @param \phpbb\config\config 		$config 			The config
	 * @param \phpbb\log\log 			$phpbb_log 			phpBB log object
	 * @param \phpbb\user				$user				User object
	 * @param \phpbb\filesystem 		$filesystem			The filesystem object
	 * @param \phpbb\request\request	$request			Request object
	 *
	 */

	public function __construct(\phpbb\language\language $language, $phpbb_root_path, \phpbb\config\config $config, \phpbb\log\log $phpbb_log, \phpbb\user $user, \phpbb\filesystem\filesystem $filesystem, \phpbb\request\request $request)
	{
		$this->config = $config;
		$this->filesystem = $filesystem;
		$this->language = $language;
		$this->phpbb_log = $phpbb_log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->request	= $request;
		$this->user	= $user;
	}

	public function download_maxmind($update_database = false)
	{

		// Makes the store/phpbbservices/filterbycountry directory if needed, then fetches and uncompresses the MaxMind
		// database if it does not exist. Any errors are written to the error log.
		//
		// Parameters:
		//   $update_database - if true, database is destroyed and recreated, done if called by a cron

		// If on the ACP settings page, we return true, otherwise the screen can't come up to enter a new or corrected license key.
		// This page does not need to use the database.
		$mode = $this->request->variable('mode', 'settings');
		if (defined('ADMIN_START') && $mode == 'settings')
		{
			return true;
		}

		// If the license key is blank or not 16 characters, the database should not be downloaded, so exit this function.
		if (strlen(trim($this->config['phpbbservices_filterbycountry_license_key'])) !== 16)
		{
			return false;
		}

		// Some useful paths
		$maxmind_db_url = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=' . trim($this->config['phpbbservices_filterbycountry_license_key']) . '&suffix=tar.gz';
		$extension_store_directory = $this->phpbb_root_path . 'store/phpbbservices/filterbycountry';
		$database_gz_file_path = $extension_store_directory . '/GeoLite2-Country.gz';

		// Create the directories needed, if they don't exist
		if ($update_database)
		{
			// Blow the current database away, along with the folder filterbycountry
			try
			{
				$this->filesystem->remove($extension_store_directory);
			}
			catch (\Exception $e)
			{
				// Report error
				$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_DELETE_ERROR', false, array($extension_store_directory));
				return false;
			}
		}

		if (!is_dir($extension_store_directory))
		{
			// We need to create the store/phpbbservices/filterbycountry directory.
			try
			{
				$this->filesystem->mkdir($extension_store_directory);
			}
			catch (\Exception $e)
			{
				// Report error
				$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_DELETE_ERROR', false, array($extension_store_directory));
				return false;
			}
		}

		// Do we have read permissions to the extension's store directory?
		if (!$this->filesystem->is_readable($extension_store_directory ))
		{
			// Report error
			$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_READ_FILE_ERROR', false, array($extension_store_directory));
			return false;
		}

		// Do we have write permissions to the extension's store directory?
		if (!$this->filesystem->is_writable($extension_store_directory ))
		{
			// Report error
			$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_WRITE_FILE_ERROR', false, array($extension_store_directory));
			return false;
		}

		// Since a copy of the database is not downloaded, fetch the database from maxmind.com using curl, which downloads a .tar.gz file as a .gz file.
		// First set up a mechanism to capture the remotely copied file on the local machine.
		$fp = fopen($database_gz_file_path, 'w+');
		if (!$fp)
		{
			$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_FOPEN_ERROR', false, array($database_gz_file_path));
			return false;
		}

		// Configure curl to work optimally fetching a MaxMind database
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $maxmind_db_url);	// Fetch using this URL
		curl_setopt($ch, CURLOPT_HEADER, 0);		// MaxMind server doesn't need HTTP headers
		curl_setopt($ch, CURLOPT_FILE, $fp);				// Write file here

		// Get the database over the internet and write it to a file
		$success = curl_exec($ch);
		if (!$success)
		{
			$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_FOPEN_ERROR', false, array($database_gz_file_path));
			return false;
		}

		// Get the HTTP status code
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// Handle unauthorized fetches of the database. MaxMind returns a HTTP 401 (unauthorized) in this event.
		if ($status_code == 401)
		{
			// The license key is bad. Mark that it is invalid.
			$this->config->set('phpbbservices_filterbycountry_license_key_valid', 0);

			// A file GeoLite2-Country.gz should be in /store/phpbbservices/filterbycountry. But it's bogus because of the 401 error and is only 20 bytes or so.
			// To keep things kosher, it should be deleted.
			$this->filesystem->remove($database_gz_file_path);

			// In this case we'll return true, but only when in the ACP settings interface. Otherwise, the screen can't come up to enter a corrected license key.
			return ($mode == 'settings') ? true : false;
		}

		// If the database was fetched successfully or hasn't changed -- that's good. Otherwise, it's bad so we need to capture this and do more error handling.
		if (!($status_code == 200 || $status_code == 304))
		{
			$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_HTTP_ERROR', false, array($status_code));
			return false;
		}

		// curl clean up
		curl_close($ch);
		fclose($fp);

		// The database should have been retrieved successfully at this point, so we can assume the license key is valid.
		$this->config->set('phpbbservices_filterbycountry_license_key_valid', 1);

		// Now, extract the database. Note that the .tar file inside the .gz file does not need to be extracted first. PharData::extractTo()
		// does both steps, but does create a subdirectory inside /store/phpbbservices/filterbycountry we don't want.
		try
		{
			$p = new \PharData($database_gz_file_path);
			$p->extractTo($extension_store_directory);
		}
		catch (\Exception $e)
		{
			// Extract failed, most likely because the .gz file is not in a valid .gz format
			$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_EXTRACT_ERROR', false, array($database_gz_file_path, $extension_store_directory, $e->getCode()));
			return false;
		}

		// Find the directory with the database. There should only be this one new directory in
		// store/phpbbservices/filterbycountry. The directory name is based on the date the database was refreshed, like
		// GeoLite2-Country_20200107. The database is inside it.
		$found_directory = false;
		$tarball_dir = '';

		if ($dh = opendir($extension_store_directory))
		{
			while (($file = readdir($dh)) !== false)
			{
				if ($file !== '.' && $file != '..' && is_dir($extension_store_directory . '/' . $file) && stristr($file, 'GeoLite2-Country'))
				{
					$tarball_dir = $extension_store_directory . '/' . $file;
					$found_directory = true;
					break;
				}
			}
			closedir($dh);
		}

		if (!$found_directory)
		{
			// No directory was found inside store/phpbbservices/filterbycountry, which is weird, so it's an error to be handled.
			$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_TARBALL_MOVE_ERROR', false, array($tarball_dir .'/GeoLite2-Country.mmdb'));
			return false;
		}

		// Move the .mmdb file to the store/phpbbservices/filterbycountry directory
		$this->filesystem->rename($tarball_dir .'/GeoLite2-Country.mmdb', $extension_store_directory .'/GeoLite2-Country.mmdb');

		// Clean up, remove all files and directories in store/phpbbservices/filterbycountry except for the .mmdb one.
		if ($dh = opendir($extension_store_directory))
		{
			while (($file = readdir($dh)) !== false)
			{
				if ($file !== '.' && $file !== '..' && $file !== 'GeoLite2-Country.mmdb')
				{
					try
					{
						$this->filesystem->remove($extension_store_directory. '/' . $file);
					}
					catch (\Exception $e)
					{
						// Report error
						$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_DELETE_ERROR', false, array($extension_store_directory . '/' . $file));
						return false;
					}
				}
			}
			closedir($dh);
		}

		// Note the date and time the database was last added or replaced
		$this->config->set('phpbbservices_filterbycountry_cron_task_last_gc', time());
		return true;

	}

	public function get_country_name ($country_code)
	{

		// Gets the name of the country in the user's language. What's returned by MaxMind is the country's name in English.
		$this->language->add_lang('common', 'phpbbservices/filterbycountry');

		$country_name = (isset($this->user->lang['ACP_FBC_COUNTRY_' . $country_code])) ? $this->user->lang['ACP_FBC_COUNTRY_' . $country_code] : $this->language->lang('ACP_FBC_UNKNOWN');

		return $country_name;

	}

}
