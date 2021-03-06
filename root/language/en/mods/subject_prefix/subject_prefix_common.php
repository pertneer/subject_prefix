<?php
/**
 *
 * subject_prefix_common [English]
 *
 * @package language
 * @copyright (c) 2010 Erik Frèrejean ( erikfrerejean@phpbb.com ) http://www.erikfrerejean.nl
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

/**
 * Localise Prefixes.
 * To localise a prefix change the name of a prefix to an UPPER CASE identifier
 * and add an entry to the following array. Say you have a prefix with the name
 * "LOCALISED_PREFIX" which would be translated to "Localised prefix" the array would
 * look like:
 * <code>
 * $lang = array_merge($lang, array(
 * 	'SP_LOCALISED_PREFIX'	=> 'Localised prefix',
 * ));
 * </code>
 * Notice that the name is prefixed with "SP_" when added to this array!
 */
$lang = array_merge($lang, array(

));

/**
 * Global lang stuff
 */
$lang = array_merge($lang, array(
	'ACP_SUBJECT_PREFIX'				=> 'Subject Prefix',
	'ACP_SUBJECT_PREFIX_ADD'			=> 'Create subject prefix',
	'ACP_SUBJECT_PREFIX_EXPLAIN'		=> 'This page can be used to manage prefixes on a forum basis, by default all forums are collapsed to prevent this page from growing to big. Click on a forum name to display the prefixes that you\'ve set for this forum and edit them accordingly, you can also change the order in <em>(drag-drop)</em> in which they will be displayed.',
	'ACP_SUBJECT_PREFIX_ADD_EXPLAIN'	=> 'On this page you can create new prefixes, or edit existing prefixes',

	'NO_PREFIXES'		=> 'You haven\'t created any subject prefixes yet',
	'NO_PREFIX_COLOUR'	=> 'You must provide a valid prefix colour',
	'NO_PREFIX_TITLE'	=> 'You must provide a Prefix title',

	'MCP_SUBJECT_PREFIX'	=> 'Subject prefix moderator control',

	'PREFIX_COLOUR'					=> 'Prefix colour',
	'PREFIX_COLOUR_EXPLAIN'			=> 'Define which colour will be used for this prefix.',
	'PREFIX_FORUMS'					=> 'Prefix forums',
	'PREFIX_FORUMS_EXPLAIN'			=> 'Select the forums in which this prefix can be used.',
	'PREFIX_INSERT_FAIL'			=> 'Couldn\'t insert the prefix',
	'PREFIX_NOT_ALLOWED'			=> 'The selected prefix isn\'t allowed in this forum',
	'PREFIX_NOT_CHANGED'			=> 'The subject prefix hasn\'t been changed',
	'PREFIX_ORDER_UPDATED'			=> 'Subject Prefix order successfully updated',
	'PREFIX_SUCCESSFULLY_ADDED'		=> 'The prefix has been created successfully!',
	'PREFIX_SUCCESSFULLY_EDITED'	=> 'The prefix has been updated successfully!',
	'PREFIX_TITLE'					=> 'Prefix title',
	'PREFIX_UPDATED_SUCCESS'		=> 'The subject prefix was successfully updated',
	'PREFIX_UPDATE_FAILED'			=> 'Couldn\'t update the subject prefix',

	'SUBJECT_PREFIX'			=> 'Subject prefix',
	'SUBJECT_PREFIX_ADD_EDIT'	=> array(
		0 => 'Create a new Subject Prefix',
		1 => 'Edit existing Subject Prefix',
	),
	'SUBJECT_PREFIX_QUICK_MOD'	=> 'Subject Prefix quick change',
	'SELECT_A_PREFIX'			=> 'Select a prefix',
));