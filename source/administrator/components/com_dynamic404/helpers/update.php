<?php
/*
 * Joomla! component Dynamic404
 *
 * @author Yireo (info@yireo.com)
 * @copyright Copyright 2016
 * @license GNU Public License
 * @link https://www.yireo.com
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Dynamic404 update helper
 */
class Dynamic404Update
{
	/**
	 * Run update queries
	 */
	static public function runUpdateQueries()
	{
		$sqlfiles = array(
			JPATH_COMPONENT . '/sql/install.mysql.utf8.sql',
			JPATH_COMPONENT . '/sql/update.sql'
        );

		foreach ($sqlfiles as $sqlfile)
		{
			if (file_exists($sqlfile) && is_readable($sqlfile))
			{
				self::runUpdateQueriesFromFile($sqlfile);
			}
		}
	}

	/**
	 * Run update queries from file
	 */
	static public function runUpdateQueriesFromFile($sqlfile)
	{
		$db = JFactory::getDbo();
		$buffer = file_get_contents($sqlfile);

		if (method_exists('JDatabaseDriver', 'splitSql'))
		{
			$queries = JDatabaseDriver::splitSql($buffer);
		}
		elseif (method_exists('JDatabase', 'splitSql'))
		{
			$queries = JDatabase::splitSql($buffer);
		}
		else
		{
			return false;
		}

		foreach ($queries as $query)
		{
			$query = trim($query);

			if ($query != '' && $query{0} != '#')
			{
				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (RuntimeException $e)
				{
					continue;
				}
			}
		}
	}
}
