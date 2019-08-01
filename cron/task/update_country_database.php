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
		// If for some reason the config variable phpbbservices_filterbycountry_cron_task_last_gc is set to 0, it's back in its initial state
		// so the cron should fetch the database.

		$todays_dow = date('w');	// 0 = Sunday, 6 = Saturday
		$last_run = (int) $this->config['phpbbservices_filterbycountry_cron_task_last_gc'];
		$days_difference = floor((float) ((time() - $last_run) / (24 * 60 * 60)));

		return (bool) (($todays_dow >= 3 && $days_difference >= 7) || ($last_run == 0));
	}

	public function run()
	{

		// Updates the MaxMind country database via a phpBB cron

		// Destroy current database, then download, ungzip, untar and stage an updated database. In cron mode, errors are placed in the admin log.
		// An email may be sent to founders too.
		$this->helper->download_maxmind(true);

		// If for some reason downloading MaxMind fails, let's not lock up other crons. So let's always return true to cron.php to preclude this.
		return true;

	}

}