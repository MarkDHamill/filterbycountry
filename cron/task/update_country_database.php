<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
namespace phpbbservices\filterbycountry\cron\task;

class update_country_database extends \phpbb\cron\task\base
{

	protected $config;
	protected $helper;
	protected $phpbb_log;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config 							$config		The config
	 * @param \phpbbservices\filterbycountry\core\common 	$helper		Extension's helper object
	 * @param \phpbb\log\log 								$phpbb_log	phpBB log object
	 */

	public function __construct(\phpbb\config\config $config, \phpbbservices\filterbycountry\core\common $helper, \phpbb\log\log $phpbb_log)
	{

		$this->config = $config;
		$this->helper = $helper;
		$this->phpbb_log = $phpbb_log;

	}

	/**
	 * Indicates to phpBB's cron utility if this task should be run.
	 *
	 * @return true if it should be run, false if it should not be run.
	 */

	public function should_run()
	{
		// The Maxmind country database is updated weekly on Tuesdays. For our purposes, we'll assume a fresh database exists on Wednesdays.
		// So to update the database, it must be on or after Wednesday and at least 7 days must have elapsed since the database was last updated.

		$todays_dow = date('w');	// 0 = Sunday, 6 = Saturday
		$days_difference = floor((float) (time() - (int) $this->config['phpbbservices_filterbycountry_cron_task_last_gc'] / (24 * 60 * 60)));

		return (bool) ($todays_dow >= 3 && $days_difference >= 7);
	}

	public function run()
	{

		// Updates the MaxMind country database via a phpBB cron

		// Destroy current database, then download, ungzip, untar and stage an updated database. In cron mode, errors are placed in the admin log
		// and the function will return true if it succeeds and false if an error happened.
		return $this->helper->download_maxmind(true);

	}

}