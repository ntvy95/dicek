<?php
/**
*
* Post Crash Protection extension for the phpBB Forum Software package.
*
* @copyright (c) 2013 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace koutogima\interaction\migrations;

class v_0_0_2 extends \phpbb\db\migration\migration {
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'posts', 'interaction_ID')
		&& $this->db_tools->sql_table_exists($this->table_prefix . 'interaction') ;
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
					'interaction_ID' => array('UINT', NULL),
				),
			),
			'add_tables' => array(
				$this->table+prefix . 'interaction' => array(
					'interaction_ID' => array('UINT', NULL, 'auto_increment'),
					'interaction_name' => array('VCHAR_UNI:255', ''),
					'interaction_owners' => array('VCHAR_UNI:255', ''),
					'interaction_parents' => array('VCHAR_UNI:255', ''),
				),
			),
		);
	}
	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'posts' => array(
					'interaction_ID',
				),
			),
			'drop_tables' => array(
				$this->table+prefix . 'interaction',
			),
		);
	}
}

?>