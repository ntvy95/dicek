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
	protected $reg_pattern;

	public function __construct(\phpbb\user $user)
	{
		$this->user = $user;
		$this->reg_pattern['style'] = '@\[style\=([^,\]\{\}]+)\]([^\[\{\}]+)\[/style\]@ius';
		$this->reg_pattern['style_animation'] = '%\[style\=@([^,\]\{\}]+)\]([^\[]+)\[/style\]%ius';
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.viewtopic_modify_post_data'	=> 'viewtopic_modify_post_data',
			'core.topic_review_modify_post_list' => 'topic_review_modify_post_list',
			'core.topic_review_modify_row' => 'iterate',
			'core.modify_text_for_display_after' => 'modify_text_for_display_after',
			'core.modify_format_display_text_after' => 'modify_format_display_text_after',
			'core.search_modify_rowset' => 'search_modify_rowset',
			'core.viewtopic_modify_post_row' => 'iterate',
			'core.search_modify_tpl_ary' => 'iterate',
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
		if($event['show_results'] == 'posts') {
			$rowset = $event['rowset'];
			$i = 0;
			foreach ($rowset as $row)
			{
				if(!$row['display_text_only']) {
					$this->post_list[$i] = $row['poster_id'];
					$i = $i + 1;
				}
			}
			$this->iterator = 0;
			$this->end = $i;
		}
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
		$event['text'] = $this->rep_bbcode_style_wrapper($event['text']);
		if(isset($this->iterator)) {
			$event['text'] = str_replace("-_USERID_-", $this->post_list[$this->iterator], $event['text']);
		}
	}
	
	public function modify_format_display_text_after($event) {
		$event['text'] = $this->rep_bbcode_style_wrapper($event['text']);
		$event['text'] = str_replace("-_USERID_-", $this->user->data['user_id'], $event['text']);
	}
	
	public function rep_bbcode_style_wrapper($text) {
		$text = preg_replace_callback($this->reg_pattern['style_animation'], function ($matches) {
			return $this->rep_bbcode_style($matches, 1);
		}, $text);
		$text = preg_replace_callback($this->reg_pattern['style'], function ($matches) {
			return $this->rep_bbcode_style($matches, 0);
		}, $text);
		return $text;
	}
	
	public function rep_bbcode_style($matches, $type) {
		switch($type) {
			case 0:
				return '<style type="text/css"> .user_-_USERID_-_ ' . $matches[1] . ' {' . str_replace('<br />', '', $matches[2]) . '} </style>';
				break;
			case 1:
				if(substr_count($matches[2], '{') - substr_count($matches[2], '}') == 0) {
					return '<style type="text/css">@' . $matches[1] . ' {' . str_replace('<br />', '', $matches[2]) . '} </style>';
				}
				else {
					return $matches[0];
				}
				break;
		}
	}
	
	public function iterate() {
		if(isset($this->iterator)) {
			$this->iterator = $this->iterator + 1;
			if($this->iterator >= $this->end) {
				unset($this->iterator);
			}
		}
	}
}
