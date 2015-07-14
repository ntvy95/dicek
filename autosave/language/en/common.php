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
	'INSR_AUTOSAVED_POST_TEXT' => 'Insert Auto Saved Post',
	'INSR_AUTOSAVED_PM_TEXT'   => 'Insert Auto Saved PM',
));
