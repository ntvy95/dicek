<?php

namespace koutogima\postcrashprotection\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.posting_modify_template_vars' => 'posting_modify_template_vars',
			'core.ucp_display_module_before' => 'ucp_display_module_before',
			'core.user_add_modify_data' => 'user_add_modify_data',
			'core.user_setup' => 'user_setup',
			'core.viewtopic_modify_page_title' => 'viewtopic_modify_page_title',
		);
	}
	
	protected $db;
	protected $users_table;
	protected $user;
	protected $template;
	
	public function __construct(\phpbb\user $user, \phpbb\template\template $template, \phpbb\db\driver\driver_interface $db, $users_table) {
		$this->user = $user;
		$this->db = $db;
		$this->users_table = $users_table;
		$this->template = $template;
	}
	
	public function posting_modify_template_vars($event) {
		$sql_last_sel    = 'SELECT last_post_text 
                    FROM ' . $this->users_table . "
                    WHERE user_id = " . (int) $this->user->data['user_id'];
		$result_last_sel = $this->db->sql_query_limit($sql_last_sel, 1);
		$last_sel        = $this->db->sql_fetchrowset($result_last_sel);
		$this->db->sql_freeresult($result_last_sel);
		$this->template->assign_var('POST_SAVE_INSERT',(!empty($last_sel[0]['last_post_text'])) ? $last_sel[0]['last_post_text'] : '');
	}
	
	public function viewtopic_modify_page_title($event) {
		$sql_last_sel    = 'SELECT last_post_text 
                    FROM ' . $this->users_table . "
                    WHERE user_id = " . (int) $this->user->data['user_id'];
		$result_last_sel = $this->db->sql_query_limit($sql_last_sel, 1);
		$last_sel        = $this->db->sql_fetchrowset($result_last_sel);
		$this->db->sql_freeresult($result_last_sel);
		$this->template->assign_var('POST_SAVE_INSERT',(!empty($last_sel[0]['last_post_text'])) ? $last_sel[0]['last_post_text'] : '');
	}
	
	public function ucp_display_module_before($event) {
		$sql_last_sel    = 'SELECT last_post_text_pm
                    FROM ' . $this->users_table . "
                    WHERE user_id = " . (int) $this->user->data['user_id'];
		$result_last_sel = $this->db->sql_query_limit($sql_last_sel, 1);
		$last_sel       = $this->db->sql_fetchrowset($result_last_sel);
		$this->db->sql_freeresult($result_last_sel);
		$this->template->assign_var('PM_SAVE_INSERT',(!empty($last_sel[0]['last_post_text_pm'])) ? $last_sel[0]['last_post_text_pm'] : '');
	}
	
	public function user_add_modify_data($event) {
		$additional_vars = array(
			'last_post_text'            => ' ',  
			'last_post_text_pm'         => ' ',
		);
		
		// Now fill the sql array with not required variables
		foreach ($additional_vars as $key => $default_value)
		{
			$sql_ary[$key] = (isset($user_row[$key])) ? $user_row[$key] : $default_value;
		}

		// Any additional variables in $user_row not covered above?
		$remaining_vars = array_diff(array_keys($user_row), array_keys($sql_ary));

		// Now fill our sql array with the remaining vars
		if (sizeof($remaining_vars))
		{
			foreach ($remaining_vars as $key)
			{
				$sql_ary[$key] = $user_row[$key];
			}
		}
		
		array_merge($event['sql_ary'], $sql_ary);
		array_merge($event['user_row'], $user_row);
	}
	
	public function user_setup($event) {
		$this->user->add_lang_ext('koutogima/postcrashprotection', 'common');
	}
}
