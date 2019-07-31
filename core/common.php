<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019, Mark D. Hamill, https://www.phpbbservices.com
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
	protected $language;
	protected $phpbb_log;
	protected $phpbb_root_path;
	protected $user;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\language\language 	$language 			Language object
	 * @param string					$phpbb_root_path	Relative path to phpBB root
	 * @param \phpbb\config\config 		$config 			The config
	 * @param \phpbb\log\log 			$phpbb_log 			phpBB log object
	 * @param \phpbb\user				$user				User object
	 *
	 */

	public function __construct(\phpbb\language\language $language, $phpbb_root_path, \phpbb\config\config $config, \phpbb\log\log $phpbb_log, \phpbb\user $user)
	{
		$this->config = $config;
		$this->language = $language;
		$this->phpbb_log = $phpbb_log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->user	= $user;
	}

	public function download_maxmind($update_database = false)
	{

		// Makes the store/phpbbservices/filterbycountry directory if needed, then fetches and uncompresses the MaxMind
		// database if it does not exist. Any errors are written to the error log.
		//
		// Parameters:
		//   $update_database - if true, database is destroyed and recreated, done if called by a cron
		//
		// Steps:
		//
		// 1. Create directories if needed or if directed
		// 2. Checks for the MaxMind country database. If it exists in the right place, exit successfully.
		// 3. Otherwise fetches the database from maxmind.com using curl, which is in a .tar.gz file
		// 4. If successful, extracts the .tar file
		// 5. The tar file is then untarred, which creates a directory containing the needed .mmdb file
		// 5. Moves the .mmdb file to the parent database
		// 6. Removes all other files and directories in this directory

		// Some useful paths
		$maxmind_url = 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz';	// Location of country database on the web
		$extension_store_directory = $this->phpbb_root_path . 'store/phpbbservices/filterbycountry';
		$database_gz_file_path = $extension_store_directory . '/GeoLite2-Country.tar.gz';
		$database_tar_file_path = $extension_store_directory . '/GeoLite2-Country.tar';
		$database_mmdb_file_path = $extension_store_directory . '/GeoLite2-Country.mmdb';

		// Create the directories needed, if they don't exist
		if ($update_database)
		{
			// Blow the current database away, along with the folder filterbycountry
			$success = $this->rrmdir($extension_store_directory);
			if (!$success)
			{
				// Report error
				$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_DELETE_ERROR', false, array($extension_store_directory));
				return false;
			}
		}

		if (!is_dir($extension_store_directory))
		{
			// We need to create the store/phpbbservices/filterbycountry directory.
			$success = @mkdir($extension_store_directory, 0777, true);
			if (!$success)
			{
				// Report error
				$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_CREATE_DIRECTORY_ERROR', false, array($extension_store_directory));
				return false;
			}
		}

		// Do we have read permissions to the extension's store directory?
		if (!is_readable($extension_store_directory ))
		{
			// Report error
			$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_READ_FILE_ERROR', false, array($extension_store_directory));
			return false;
		}

		// Do we have write permissions to the extension's store directory?
		if (!is_writeable($extension_store_directory ))
		{
			// Report error
			$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_WRITE_FILE_ERROR', false, array($extension_store_directory));
			return false;
		}

		// Check to see if the MaxMind country database is already in the right place in the file system.
		if (file_exists($database_mmdb_file_path))
		{
			if (is_readable($database_mmdb_file_path))
			{
				// The database exists and is hopefully current so we can exit the function
				return true;
			}
			else
			{
				// No read permissions to the database, so show an error
				$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_READ_FILE_ERROR', false, array($database_gz_file_path));
				return false;
			}
		}

		// Since a copy of the database is not downloaded, fetch the database from maxmind.com using curl, which downloads a .tar.gz file
		$fp = fopen($database_gz_file_path, 'w+');
		if (!$fp)
		{
			$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_FOPEN_ERROR', false, array($database_gz_file_path));
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $maxmind_url);		// Fetch from here
		curl_setopt($ch, CURLOPT_HEADER, 0);		// MaxMind server doesn't need HTTP headers
		curl_setopt($ch, CURLOPT_FILE, $fp);				// Write file here

		$success = curl_exec($ch);		// Get the database and write to a file
		if (!$success)
		{
			$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_FOPEN_ERROR', false, array($database_gz_file_path));
			return false;
		}

		// Get the HTTP status code
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (!($status_code == 200 || $status_code == 304))	// 304 = unchanged
		{
			$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_FOPEN_ERROR', false, array($database_gz_file_path));
			return false;
		}

		curl_close($ch);
		fclose($fp);

		// Now, untar the tarball. It will create a directory in store/phpbbservices/filterbycountry. The directory
		// name includes the date the tarball was created.
		$p = new \PharData($database_gz_file_path);
		$p->extractTo($extension_store_directory);

		// Find the directory with the extract of the tarball. There should only be this one new directory in
		// store/phpbbservices/filterbycountry.
		$found_tarball_dir = false;
		if ($dh = opendir($extension_store_directory))
		{
			while (($file = readdir($dh)) !== false)
			{
				if ($file != "." && $file != ".." && is_dir($extension_store_directory . '/' . $file) && stristr($file, 'GeoLite2-Country'))
				{
					$tarball_dir = $extension_store_directory . '/' . $file;
					$found_tarball_dir = true;
					break;
				}
			}
			closedir($dh);
		}

		if (!$found_tarball_dir)
		{
			$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_TARBALL_MOVE_ERROR', false, array($tarball_dir .'/GeoLite2-Country.mmdb'));
			return false;
		}

		// Move the .mmdb file to the store/phpbbservices/filterbycountry directory
		rename( $tarball_dir .'/GeoLite2-Country.mmdb', $extension_store_directory .'/GeoLite2-Country.mmdb');

		// Clean up, remove all files and directories in store/phpbbservices/filterbycountry except for the .mmdb one.
		if ($dh = opendir($extension_store_directory))
		{
			while (($file = readdir($dh)) !== false)
			{
				if ($file != "." && $file != ".." )
				{
					if ($file != 'GeoLite2-Country.mmdb')
					{
						if (is_dir($extension_store_directory . '/' . $file))
						{
							$success = $this->rrmdir($extension_store_directory . '/' . $file);
						}
						else
						{
							$success = unlink($extension_store_directory . '/' . $file);
						}
						if (!$success)
						{
							// Report error
							$this->phpbb_log->add(LOG_CRITICAL, $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_FBC_DELETE_ERROR', false, array($extension_store_directory . '/' . $file));
							return false;
						}
					}
				}
			}
			closedir($dh);
		}

		// Note the date and time the database was last updated
		$this->config->set('phpbbservices_filterbycountry_cron_task_last_gc', time());
		return true;

	}

	public function get_country_name ($country_code)
	{

		// Gets the name of the country in the user's language. What's returned by MaxMind is the country name in English.

		$dom = new \DOMDocument();
		$dom->loadHTML('<?xml encoding="utf-8" ?>' . $this->language->lang('ACP_FBC_OPTIONS')); // Encoding fix by EA117
		$xml_countries = $dom->getElementsByTagName('option');

		// Find the country by parsing the language variable that contains the HTML option tags.
		foreach ($xml_countries as $xml_country)
		{
			if ($xml_country->getAttribute('value') == $country_code)
			{
				return $xml_country->nodeValue;	// Returns the country's name in the user's language
			}
			next($xml_countries);
		}
		return $this->language->lang('FBC_UNKNOWN');

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
			else
			{
				return false;
			}
		}
		return true;

	}

}
