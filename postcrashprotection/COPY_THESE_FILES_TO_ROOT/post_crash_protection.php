<?php
/**
*
* @package Post Crash Protection v.0.0.2
* @version $Id: post_crash_protection.php 7534 2010-06-04 23:12:19Z 4seven $
* @copyright (c) 4seven / 2011 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();

if ($user->data['user_id'] !== ANONYMOUS){

$text = utf8_normalize_nfc(request_var('text', '', true));

if (!empty($text)){
$text = $db->sql_escape($text);
		$sql_last_up = 'UPDATE ' . USERS_TABLE . "
						SET last_post_text = '$text'
						WHERE user_id = " . (int) $user->data['user_id'];
		$db->sql_query($sql_last_up);

		$db->sql_freeresult($sql_last_up);

    }
}
?>