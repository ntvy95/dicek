<?php
/**
*
* Post Crash Protection extension for the phpBB Forum Software package.
*
* @copyright (c) 2013 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace koutogima\dicek\migrations;

class v_0_0_1 extends \phpbb\db\migration\migration {
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'posts', 'dicek_range') && $this->db_tools->sql_column_exists($this->table_prefix . 'posts', 'dicek_value') ;
	}
	static public function depends_on()
	{
			return array('\phpbb\db\migration\data\v310\dev');
	}
	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'posts' => array(
					'dicek_range' => array('MTEXT_UNI', null),
					'dicek_value' => array('MTEXT_UNI', null),
				),
			),
		);
	}
	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'posts' => array(
					'dicek_range',
					'dicek_value',
				),
			),
		);
	}
}

?>