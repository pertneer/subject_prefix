<?php
/**
 *
 * @package Subject Prefix
 * @copyright (c) 2010 Erik Frèrejean ( erikfrerejean@phpbb.com ) http://www.erikfrerejean.nl
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * Class that contains all hooked methods
 */
abstract class sp_hook
{
	/**
	 * @var Array Array containing all hook data.
	 */
	static private $_hooks = array(
		array(
			'phpbb_user_session_handler',
			'subject_prefix_init',
		),
		array(
			array('template', 'display'),
			'add_subject_prefix_to_page',
		),
		array(
			array('template', 'display'),
			'subject_prefix_template_hook',
		),
	);

	/**
	 * Register all subject prefix hooks
	 * @param	phpbb_hook	$phpbb_hook	The phpBB hook object
	 * @return	void
	 */
	static public function register(&$phpbb_hook)
	{
		foreach (self::$_hooks as $hook)
		{
			$phpbb_hook->register($hook[0], 'sp_hook::' . $hook[1]);
		}
	}

	/**
	 * A hook that is used to initialise the Subject Prefix core
	 * @param	phpbb_hook	$phpbb_hook	The phpBB hook object
	 * @return	void
	 */
	static public function subject_prefix_init(&$hook)
	{
		global $phpbb_root_path, $phpEx;

		// Load all the classes
		$classes = array('sp_phpbb', 'sp_cache', 'sp_core');
		foreach ($classes as $class)
		{
			if (!class_exists($class))
			{
				require "{$phpbb_root_path}includes/mods/subject_prefix/{$class}.{$phpEx}";
			}
		}
	}

	/**
	 * A hook that adds the subject prefixes to phpBB pages without modifying the page itself
	 * @param	phpbb_hook	$phpbb_hook	The phpBB hook object
	 * @return	void
	 * @todo	Change this method to a more flexible one. The current method is really
	 * 			static and there is a lot of code duplication around here.
	 */
	static public function add_subject_prefix_to_page(&$hook)
	{
		// Only on regular pages
		if (!empty(sp_phpbb::$user->page['page_dir']))
		{
			return;
		}

		// This is kinda nasty, viewforum.php?f=. should be handled as index.php
		$_callback = '';
		if (sp_phpbb::$user->page['page_name'] == 'viewforum.' . PHP_EXT && isset(sp_phpbb::$template->_tpldata['forumrow']))
		{
			$_callback = 'index';
		}

		// Get the page basename to call a method
		$_callback = (!empty($_callback)) ? $_callback : basename(sp_phpbb::$user->page['page_name'], '.' . PHP_EXT);
		if (method_exists('sp_hook', 'add_to_' . $_callback))
		{
			call_user_func('sp_hook::add_to_' . $_callback);
		}
	}

	/**
	 * Add the prefix to the index page
	 * @return void
	 */
	static private function add_to_index()
	{
		if (empty(sp_phpbb::$template->_tpldata['forumrow']))
		{
			return;
		}

		// This MOD also supports Joas his "last post topic title MOD".
		if (isset(sp_phpbb::$config['altt_active']) && sp_phpbb::$config['altt_active'])
		{
			$blockvar	= 'ALTT_LINK_NAME_SHORT';
			$testvar	= 'ALTT_LINK_NAME_SHORT';
		}
		else
		{
			$blockvar	= 'LAST_POST_SUBJECT';
			$testvar	= 'U_LAST_POST';
		}

		// To fetch the subject prefixes we'll need the last post ids
		$last_post_ids = array();
		foreach (sp_phpbb::$template->_tpldata['forumrow'] as $row => $data)
		{
			// Need the last post link
			if (empty($data[$testvar]))
			{
				continue;
			}

			$last_post_ids[$row] = substr(strrchr($data['U_LAST_POST'], 'p'), 1);
		}

		// Nothing to see here please walk on and mind your own business.
		if (empty($last_post_ids))
		{
			return;
		}

		// Get the prefixes
		$sql = 'SELECT topic_last_post_id, subject_prefix_id
			FROM ' . TOPICS_TABLE . '
			WHERE ' . sp_phpbb::$db->sql_in_set('topic_last_post_id', $last_post_ids);
		$result	= sp_phpbb::$db->sql_query($sql);
		$last_post_ids = array_flip($last_post_ids);
		while ($row = sp_phpbb::$db->sql_fetchrow($result))
		{
			$last_post_subject  = sp_phpbb::$template->_tpldata['forumrow'][$last_post_ids[$row['topic_last_post_id']]][$blockvar];
			$last_post_prefix  = sp_core::generate_prefix_string($row['subject_prefix_id']);

			// Alter the array
			sp_phpbb::$template->alter_block_array('forumrow', array(
				$blockvar => ($last_post_prefix === false) ? $last_post_subject : $last_post_prefix . '&nbsp;' . $last_post_subject,
			), $key = $last_post_ids[$row['topic_last_post_id']], 'change');
		}
		sp_phpbb::$db->sql_freeresult($result);

		// Forums with a subforum are handled kinda funky, componsate for it.
		if (!empty(sp_phpbb::$template->_tpldata['topicrow']))
		{
			self::add_to_viewforum();
		}
	}

	/**
	 * Add the prefix to the memberlist
	 * @return void
	 */
	static private function add_to_memberlist()
	{
		// Most active topic
		if (!empty(sp_phpbb::$template->_tpldata['.'][0]['ACTIVE_TOPIC']))
		{
			// Strip the topic id from link
			$topic_id = substr(strrchr(sp_phpbb::$template->_tpldata['.'][0]['U_ACTIVE_TOPIC'], '='), 1);

			// Get the subject prefix
			$sql = 'SELECT subject_prefix_id
				FROM ' . TOPICS_TABLE . '
				WHERE topic_id = ' . (int) $topic_id;
			$result	= sp_phpbb::$db->sql_query($sql);
			$pid	= sp_phpbb::$db->sql_fetchfield('subject_prefix_id', false, $result);
			sp_phpbb::$db->sql_freeresult($result);

			// Send to the template
			$active_title = sp_core::generate_prefix_string($pid) . ' ' . sp_phpbb::$template->_tpldata['.'][0]['ACTIVE_TOPIC'];
			sp_phpbb::$template->assign_var('ACTIVE_TOPIC', $active_title);
		}
	}

	/**
	 * Add the prefix to search
	 * @return void
	 */
	static private function add_to_search()
	{
		if (empty(sp_phpbb::$template->_tpldata['searchresults']))
		{
			return;
		}

		$sr = request_var('sr', '');

		// Collect the post ids
		$row_id = array();
		foreach (sp_phpbb::$template->_tpldata['searchresults'] as $row => $data)
		{
			if ($sr == 'topics')
			{
				if (empty($data['U_VIEW_TOPIC']))
				{
					continue;
				}

				$matches = array();
				preg_match('#t=(?<topic_id>[0-9]+)#', sp_phpbb::$template->_tpldata['searchresults'][$row]['U_VIEW_TOPIC'], $matches);

				$row_id[$row] = $matches['topic_id'];
			}
			else
			{
				if (empty($data['POST_ID']))
				{
					continue;
				}

				$row_id[$row] = $data['POST_ID'];
			}
		}

		// No?
		if (empty($row_id))
		{
			return;
		}

		// Fetch the prefixes
		$sql = $key = '';
		switch ($sr)
		{
			case 'topics' :
				$sql = 'SELECT topic_id, subject_prefix_id
					FROM ' . TOPICS_TABLE . '
					WHERE ' . sp_phpbb::$db->sql_in_set('topic_id', $row_id);
				$key = 'topic_id';
			break;

			default :
				$sql = 'SELECT p.post_id, t.topic_id, t.subject_prefix_id
					FROM (' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t)
					WHERE ' . sp_phpbb::$db->sql_in_set('p.post_id', $row_id) . '
						AND t.topic_id = p.topic_id';
				$key = 'post_id';
				$field = '';
		}
		$result = sp_phpbb::$db->sql_query($sql);
		$row_id = array_flip($row_id);
		while ($row = sp_phpbb::$db->sql_fetchrow($result))
		{
			$topic_title = sp_core::generate_prefix_string($row['subject_prefix_id']) . ' ' . sp_phpbb::$template->_tpldata['searchresults'][$row_id[$row[$key]]]['TOPIC_TITLE'];

			// Update the template
			sp_phpbb::$template->alter_block_array('searchresults', array(
				'TOPIC_TITLE' => $topic_title,
			), $row_id[$row[$key]], 'change');
		}
		sp_phpbb::$db->sql_freeresult($result);
	}

	/**
	 * Add the prefix to UCP
	 * @return void
	 */
	static private function add_to_ucp()
	{
		// Bookmarks and subscriptions
		if (!empty(sp_phpbb::$template->_tpldata['topicrow']))
		{
			$topic_ids_rows = array();
			foreach (sp_phpbb::$template->_tpldata['topicrow'] as $row => $data)
			{
				$topic_ids_rows[$row] = $data['TOPIC_ID'];
			}

			$sql = 'SELECT topic_id, subject_prefix_id
				FROM ' . TOPICS_TABLE . '
				WHERE ' . sp_phpbb::$db->sql_in_set('topic_id', $topic_ids_rows) . '
					AND subject_prefix_id > 0';
			$result = sp_phpbb::$db->sql_query($sql);
			$topic_ids_rows = array_flip($topic_ids_rows);
			while ($row = sp_phpbb::$db->sql_fetchrow($result))
			{
				$topic_title = sp_core::generate_prefix_string($row['subject_prefix_id']) . ' ' . sp_phpbb::$template->_tpldata['topicrow'][$topic_ids_rows[$row['topic_id']]]['TOPIC_TITLE'];

				// Alter the array
				sp_phpbb::$template->alter_block_array('topicrow', array(
					'TOPIC_TITLE' => $topic_title,
				), $key = $topic_ids_rows[$row['topic_id']], 'change');
			}
			sp_phpbb::$db->sql_freeresult($result);
		}

		// Most active topic
		if (!empty(sp_phpbb::$template->_tpldata['.'][0]['ACTIVE_TOPIC']))
		{
			// Strip the topic id from link
			$topic_id = substr(strrchr(sp_phpbb::$template->_tpldata['.'][0]['U_ACTIVE_TOPIC'], '='), 1);

			// Get the subject prefix
			$sql = 'SELECT subject_prefix_id
				FROM ' . TOPICS_TABLE . '
				WHERE topic_id = ' . (int) $topic_id;
			$result	= sp_phpbb::$db->sql_query($sql);
			$pid	= sp_phpbb::$db->sql_fetchfield('subject_prefix_id', false, $result);
			sp_phpbb::$db->sql_freeresult($result);

			// Send to the template
			$active_title = sp_core::generate_prefix_string($pid) . ' ' . sp_phpbb::$template->_tpldata['.'][0]['ACTIVE_TOPIC'];
			sp_phpbb::$template->assign_var('ACTIVE_TOPIC', $active_title);
		}
	}

	/**
	 * Add the prefix to viewforum
	 * @return void
	 */
	static private function add_to_viewforum()
	{
		// As the topic data is unset once its used we'll have to introduce an query to
		// fetch the prefixes
		if (empty(sp_phpbb::$template->_tpldata['topicrow']))
		{
			return;
		}

		$topic_ids_rows = array();
		foreach (sp_phpbb::$template->_tpldata['topicrow'] as $row => $data)
		{
			$topic_ids_rows[$row] = $data['TOPIC_ID'];
		}

		// No topics IDs
		if (empty($topic_ids_rows))
		{
			return;
		}

		$sql = 'SELECT topic_id, subject_prefix_id
			FROM ' . TOPICS_TABLE . '
			WHERE ' . sp_phpbb::$db->sql_in_set('topic_id', $topic_ids_rows) . '
				AND subject_prefix_id > 0';
		$result = sp_phpbb::$db->sql_query($sql);
		$topic_ids_rows = array_flip($topic_ids_rows);
		while ($row = sp_phpbb::$db->sql_fetchrow($result))
		{
			$topic_title = sp_core::generate_prefix_string($row['subject_prefix_id']) . ' ' . sp_phpbb::$template->_tpldata['topicrow'][$topic_ids_rows[$row['topic_id']]]['TOPIC_TITLE'];

			// Alter the array
			sp_phpbb::$template->alter_block_array('topicrow', array(
				'TOPIC_TITLE' => $topic_title,
			), $key = $topic_ids_rows[$row['topic_id']], 'change');
		}
		sp_phpbb::$db->sql_freeresult($result);
	}

	/**
	 * Add the prefix to viewtopic
	 * @return void
	 */
	static private function add_to_viewtopic()
	{
		global $forum_id, $topic_id;
		global $viewtopic_url, $topic_data;

		// Add to the page title
		if (!empty(sp_phpbb::$template->_tpldata['.'][0]['PAGE_TITLE']))
		{
			$page_title		= sp_phpbb::$template->_tpldata['.'][0]['PAGE_TITLE'];
			$page_prefix	= sp_core::generate_prefix_string($topic_data['subject_prefix_id'], false);
			if (sp_core::PHPBB3_SEO_TITLE_MOD === true)
			{
				$page_title	= ($page_prefix === false) ? $page_title : $page_prefix . ' ' . $page_title;
			}
			else
			{
				$page_title = (strpos($page_title, '-') !== false) ? substr_replace($page_title, ' ' . $page_prefix, strpos($page_title, '-') + 1, 0) : $page_title;
			}
			sp_phpbb::$template->assign_var('PAGE_TITLE', $page_title);
		}

		// Add to the topic title
		if (!empty(sp_phpbb::$template->_tpldata['.'][0]['TOPIC_TITLE']))
		{
			if(strpos(sp_phpbb::$template->_tpldata['.'][0]['TOPIC_TITLE'], sp_core::generate_prefix_string($topic_data['subject_prefix_id'])) === false)
			{
				$topic_title	= sp_phpbb::$template->_tpldata['.'][0]['TOPIC_TITLE'];
				$topic_prefix	= sp_core::generate_prefix_string($topic_data['subject_prefix_id']);
				sp_phpbb::$template->assign_var('FEED_TOPIC_TITLE', $topic_title);		// A small fix for topic feeds (#11)
				$topic_title = ($topic_prefix === false) ? $topic_title : $topic_prefix . ' ' . $topic_title;
				sp_phpbb::$template->assign_var('TOPIC_TITLE', $topic_title);
			}
		}

		// The quick MOD box
		if (sp_phpbb::$auth->acl_get('m_subject_prefix', $forum_id))
		{
			sp_phpbb::$template->assign_vars(array(
				'S_SUBJECT_PREFIX_QUICK_MOD'		=> sp_core::generate_prefix_options($forum_id, $topic_data['subject_prefix_id']),
				'S_SUBJECT_PREFIX_QUICK_MOD_ACTION'	=> append_sid(PHPBB_ROOT_PATH . 'mcp.' . PHP_EXT, array('i' => 'subject_prefix', 'mode' => 'quick_edit', 'f' => $forum_id, 't' => $topic_id, 'redirect' => urlencode(str_replace('&amp;', '&', $viewtopic_url))), true, sp_phpbb::$user->session_id),
			));
		}
	}

	/**
	 * A hook that is used to change the behavior of phpBB just before the templates
	 * are displayed.
	 * @param	phpbb_hook	$phpbb_hook	The phpBB hook object
	 * @return	void
	 * @todo	Clean up, kinda messy this :/
	 */
	static public function subject_prefix_template_hook(&$hook)
	{
		switch (sp_phpbb::$user->page['page_name'])
		{
			// Add the prefix dropdown to the posting page
			case 'posting.' . PHP_EXT :
				global $forum_id, $post_id, $topic_id;
				global $mode, $preview;

				// Must habs perms
				if (sp_phpbb::$auth->acl_get('!f_subject_prefix', $forum_id))
				{
					return;
				}

				$pid = request_var('subjectprefix', 0);

				// When editing we only pass this point when the *first* post is edited
				$selected = false;
				$sql = 'SELECT subject_prefix_id
					FROM ' . TOPICS_TABLE . "
					WHERE topic_id = $topic_id
						AND topic_first_post_id = $post_id";
				$result		= sp_phpbb::$db->sql_query($sql);
				$selected	= sp_phpbb::$db->sql_fetchfield('subject_prefix_id', false, $result);
				sp_phpbb::$db->sql_freeresult($result);

				// If submitted, change the selected prefix here
				if (isset($_POST['post']))
				{
					global $data;

					switch ($mode)
					{
						case 'edit' :
							if ($selected === false)
							{
								return;
							}

						// No Break;

						case 'post' :
							// Validate that this prefix can be used here
							$tree = $forums = array();
							sp_phpbb::$cache->obtain_prefix_forum_tree($tree, $forums);
							if (!isset($tree[$forum_id][$pid]) && $pid > 0)
							{
								trigger_error('PREFIX_NOT_ALLOWED');
							}

							// Only have to add the prefix
							$sql = 'UPDATE ' . TOPICS_TABLE . '
								SET subject_prefix_id = ' . $pid . '
								WHERE topic_id = ' . $data['topic_id'];
							sp_phpbb::$db->sql_query($sql);

							// Done :)
							return;
						break;
					}
				}
				// Display the dropbox
				else
				{
					// Set the correct prefix when previewing
					if (!empty($preview))
					{
						$selected = $pid;
					}

					switch ($mode)
					{
						case 'edit' :
							if ($selected === false)
							{
								// Nope
								return;
							}

						// No Break;

						case 'post';
							sp_phpbb::$template->assign_vars(array(
								'S_SUBJECT_PREFIX_OPTIONS'	=> sp_core::generate_prefix_options($forum_id, $selected),
							));
						break;
					}
				}
			break;
		}
	}
}

// Register
sp_hook::register($phpbb_hook);