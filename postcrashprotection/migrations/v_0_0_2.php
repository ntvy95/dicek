<?php
/**
*
* Post Crash Protection extension for the phpBB Forum Software package.
*
* @copyright (c) 2013 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace koutogima\postcrashprotection\migrations;

class v_0_0_2 extends \phpbb\db\migration\migration {
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'last_post_text') && $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'last_post_text_pm') ;
	}
	static public function depends_on()
	{
			return array('\phpbb\db\migration\data\v310\dev');
	}
	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'users' => array(
					'last_post_text' => array('MTEXT_UNI', null),
					'last_post_text_pm' => array('MTEXT_UNI', null),
				),
			),
		);
	}
	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'users' => array(
					'last_post_text',
					'last_post_text_pm',
				),
			),
		);
	}
}

?>