<?php
/**
 *
 * @package Subject Prefix
 * @copyright (c) 2010 Erik Frèrejean ( erikfrerejean@phpbb.com ) http://www.erikfrerejean.nl
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */
namespace subjectprefix;

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * The main Subject Prefix class
 */
abstract class sp_core
{
	static public function init()
	{
		// Define the database tables
		global $table_prefix;
		define('SUBJECT_PREFIX_TABLE', $table_prefix . 'subject_prefixes');

		// We're going to need this data anyways, better to have the cache class fetch it now
		sp_phpbb::$cache->obtain_subject_prefixes();

		echo'<pre>';var_dump(sp_phpbb::$cache);exit;
	}
}
