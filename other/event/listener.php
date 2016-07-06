<?php
/** 
*
* @package Hide_BBcode
* @copyright (c) 2016 Kou Togima based on Marco van Oort's work
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2 
*
*/

namespace koutogima\other\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	
	protected $post_list;
	protected $iterator;
	protected $end;
	protected $user;

	public function __construct(\phpbb\user $user)
	{
		$this->user = $user;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.viewtopic_modify_post_data'	=> 'viewtopic_modify_post_data',
			'core.topic_review_modify_post_list' => 'topic_review_modify_post_list',
			'core.topic_review_modify_row' => 'topic_review_modify_row',
			'core.modify_text_for_display_after' => 'modify_text_for_display_after',
			'core.modify_format_display_text_after' => 'modify_format_display_text_after',
			'core.search_modify_rowset' => 'search_modify_rowset',
			'core.viewtopic_modify_post_row' => 'viewtopic_modify_post_row',
			'core.search_modify_tpl_ary' => 'search_modify_tpl_ary',
		);
	}
	
	public function viewtopic_modify_post_data($event) {
		$this->post_list = array();
		$post_list = $event['post_list'];
		$rowset = $event['rowset'];
		for ($i = 0, $end = sizeof($post_list); $i < $end; ++$i)
		{
			if (!isset($rowset[$post_list[$i]]))
			{
				continue;
			}
			$row = $rowset[$post_list[$i]];
			$poster_id = $row['user_id'];
			$this->post_list[$i] = $poster_id;
		}
		$this->iterator = 0;
		$this->end = $end;
		$this->decoded = false;
	}
	
	public function search_modify_rowset($event) {
		$this->post_list = array();
		$rowset = $event['rowset'];
		$i = 0;
		foreach ($rowset as $row)
		{
			$this->post_list[$i] = $row['poster_id'];
			$i = $i + 1;
		}
		$this->iterator = 0;
		$this->end = count($rowset);
		$this->decoded = true;
	}
	
	public function topic_review_modify_post_list($event) {
		$this->post_list = array();
		$post_list = $event['post_list'];
		$rowset = $event['rowset'];
		for ($i = 0, $end = sizeof($post_list); $i < $end; ++$i)
		{
			if (!isset($rowset[$post_list[$i]]))
			{
				continue;
			}
			$row = $rowset[$post_list[$i]];
			$poster_id = $row['user_id'];
			$this->post_list[$i] = $poster_id;
		}
		$this->iterator = 0;
		$this->end = $end;
		$this->decoded = false;
	}
	
	public function modify_text_for_display_after($event) {
		if(isset($this->iterator)) {
			$event['text'] = str_replace("-_USERID_-", $this->post_list[$this->iterator], $event['text']);
		}
	}
	
	public function modify_format_display_text_after($event) {
		$event['text'] = str_replace("-_USERID_-", $this->user->data['user_id'], $event['text']);
	}

	public function topic_review_modify_row($event) {
		$this->iterate();
	}
	
	public function viewtopic_modify_post_row($event) {
		$this->iterate();
	}
	
	public function search_modify_tpl_ary($event) {
		$this->iterate();
	}
	
	public function iterate() {
		$this->iterator = $this->iterator + 1;
		if($this->iterator >= $this->end) {
			unset($this->iterator);
		}
	}
}
