<?php

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'INSR_SAVED_POST_TEXT' => 'Insert Saved Post',
	'INSR_SAVED_PM_TEXT'   => 'Insert Saved PM',
));
