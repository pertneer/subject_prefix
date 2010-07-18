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
 * Subject Prefix module class
 * @package acp
 */
class acp_subject_prefix
{
	public $u_action;

	public function main($id, $mode)
	{
		global $phpbb_admin_path;	// Anoyingly can't use a constant due to phpBB :/

		// Set some stuff we *really* need
		$this->tpl_name = 'acp_subject_prefix';
		$this->page_title = 'ACP_SUBJECT_PREFIX';
		add_form_key('acp_subject_prefix');
		$action	= request_var('action', '');

		switch ($mode)
		{
			case 'edit' :
			break;

			case 'add'  :
				// Handle
				if (isset($_POST['submit']))
				{
					// Get the data
					$colour		= request_var('prefix_colour', '');
					$forum_ids	= request_var('prefix_forums_id', array(0 => 0));
					$title		= request_var('prefix_title', '', true);
					$order_max	= array();
					// Figure out the highest order numbers per selected forum
					$sql = 'SELECT MAX(prefix_order) AS order_max, forum_id
						FROM ' . SUBJECT_PREFIX_FORUMS_TABLE . '
						WHERE ' . sp_phpbb::$db->sql_in_set('forum_id', $forum_ids) . '
							GROUP BY forum_id';
					$result = sp_phpbb::$db->sql_query($sql);
					while ($row = sp_phpbb::$db->sql_fetchrow($result))
					{
						$order_max[$row['forum_id']] = $row['order_max'];
					}
					sp_phpbb::$db->sql_freeresult($result);

					// Create the prefix
					$prefix_data = array(
						'prefix_id'		=> null,
						'prefix_title'	=> $title,
						'prefix_colour'	=> (empty($colour)) ? '000000' : $colour,
					);
					sp_phpbb::$db->sql_query('INSERT INTO ' . SUBJECT_PREFIX_TABLE . ' ' . sp_phpbb::$db->sql_build_array('INSERT', $prefix_data));

					// Get the ID
					$prefix_id = sp_phpbb::$db->sql_nextid();

					// Insert this ID on all selected forums
					$prefix_forum_data = array();
					foreach ($forum_ids as $id)
					{
						$prefix_forum_data[] = array(
							'prefix_id'		=> $prefix_id,
							'forum_id'		=> $id,
							'prefix_order'	=> (isset($order_max[$id])) ? $order_max[$id] + 1 : 1,
						);
					}
					sp_phpbb::$db->sql_multi_insert(SUBJECT_PREFIX_FORUMS_TABLE, $prefix_forum_data);
					trigger_error('PREFIX_SUCCESSFULLY_ADDED' . adm_back_link($this->u_action));
				}

				// Display page
				sp_phpbb::$template->assign_vars(array(
					'L_SUBJECT_PREFIX_ADD_EDIT'	=> sp_phpbb::$user->lang('SUBJECT_PREFIX_ADD_EDIT', ($mode == 'add') ? 0 : 1),
					'PREFIX_FORUMS_OPTIONS'		=> make_forum_select(),
					'S_EDIT'					=> true,
					'U_SWATCH'					=> append_sid($phpbb_admin_path . 'swatch.' . PHP_EXT, array('form' => 'acp_subject_prefix', 'name' => 'prefix_colour')),
				));
			break;

			case 'main' :
				// Quick actions
				if (method_exists($this, 'qa_' . $action))
				{
					call_user_func(array($this, 'qa_' . $action));
				}

				$data = $forums = array();
				sp_phpbb::$cache->obtain_prefix_forum_tree($data, $forums);
				ksort($data);

				if (is_array($data) && is_array($forums))
				{
					// Output the list
					foreach ($data as $forum_id => $prefixes)
					{
						// The forum block
						sp_phpbb::$template->assign_block_vars('forumrow', array(
							'FORUMNAME'	=> $forums[$forum_id],
							'FORUM_ID'	=> $forum_id,
						));

						// The prefixes
						foreach ($prefixes as $prefix)
						{
							sp_phpbb::$template->assign_block_vars('forumrow.prefixrow', array(
								'PREFIX_ID'		=> $prefix['prefix_id'],
								'PREFIX_NAME'	=> $prefix['prefix_title'],
								'PREFIX_COLOUR'	=> $prefix['prefix_colour'],

								// Actions
								'U_DELETE'		=> (sp_phpbb::$auth->acl_get('a_subject_prefix_create')) ? $this->u_action . '&amp;action=delete&amp;pid=' . $prefix['prefix_id'] : false,
								'U_MOVE_DOWN'	=> $this->u_action . '&amp;action=move&amp;direction=down&amp;prefix_order=' . $prefix['prefix_order'] . '&amp;f=' . $forum_id,
								'U_MOVE_UP'		=> $this->u_action . '&amp;action=move&amp;direction=up&amp;prefix_order=' . $prefix['prefix_order'] . '&amp;f=' . $forum_id,
							));
						}
					}
				}

				// Some common stuff
				sp_phpbb::$template->assign_vars(array(
					'U_SUBJECT_PREFIX_AJAX_REQUEST'	=> append_sid(PHPBB_ROOT_PATH . 'sp_ajax.' . PHP_EXT),
				));
			break;
		}
	}

	/**
	 * Delete a prefix
	 * @return void
	 */
	private function qa_delete()
	{
		$pid = request_var('pid', 0);
		sp_phpbb::$db->sql_query('DELETE FROM ' . SUBJECT_PREFIX_TABLE . ' WHERE prefix_id = ' . $pid);
		sp_phpbb::$db->sql_query('DELETE FROM ' . SUBJECT_PREFIX_FORUMS_TABLE . ' WHERE prefix_id = ' . $pid);
		sp_cache::subject_prefix_quick_clear();
	}

	/**
	 * Reorder the prefixes
	 * @return void
	 */
	private function qa_move()
	{
		$direction	 = ($_GET['direction'] == 'down') ? 'down' : 'up';
		$field_order = request_var('prefix_order', 0);
		$fid		 = request_var('f', 0);
		$order_total = $field_order * 2 + (($direction == 'up') ? -1 : 1);

		$sql = 'UPDATE ' . SUBJECT_PREFIX_FORUMS_TABLE . "
			SET prefix_order = $order_total - prefix_order
			WHERE prefix_order IN ($field_order, " . (($direction == 'up') ? $field_order - 1 : $field_order + 1) . ')
				AND forum_id = ' . $fid;
		sp_phpbb::$db->sql_query($sql);
		sp_cache::subject_prefix_quick_clear();
	}
}
