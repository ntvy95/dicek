<?php

namespace koutogima\dicek\event;

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
			'core.submit_post_end' => 'submit_post_end',
			'core.viewtopic_modify_post_data' => 'viewtopic_modify_post_data',
			'core.topic_review_modify_row' => 'iterate',
			'core.viewtopic_modify_post_row' => 'iterate',
			'core.search_modify_tpl_ary' => 'iterate',
			'core.search_modify_rowset' => 'search_modify_rowset',
			'core.topic_review_modify_post_list' => 'topic_review_modify_post_list',
			'core.modify_text_for_display_after' => 'modify_text_for_display_after',
		);
	}
	protected $db;
	protected $posts_table;
	protected $post_list;
	protected $iterator;
	protected $end;
	static $pattern;
	static $pattern_checkdicek;
	
	public function __construct(\phpbb\db\driver\driver_interface $db, $posts_table) {
		$this->db = $db;
		$this->posts_table = $posts_table;
		self::$pattern = '@\[dicek\]([0-9]+|([0-9]+(-[0-9]+)+))\[/dicek\]@i';
		self::$pattern_checkdicek = '@\[checkdicek](|[0-9]+)\[/checkdicek]@i';
	}
	
	public function submit_post_end($event) {
		$message = $event['data']['message'];
		$post_id = $event['data']['post_id'];
		$dicek_range = $this->get_dicek_range_from_message($message);

		if(!empty($dicek_range)) {			
			$current_dicek_range_string = $this->get_current_dicek_range_string($post_id);			
			$current_dicek_range = $this->convert_dicek_string_to_array($current_dicek_range_string);
			$new_dicek_range = $this->new_dicek_range($current_dicek_range, $dicek_range);
			if(count($new_dicek_range) > 0) { 
				$new_dicek_value = $this->generate_dicek_value($new_dicek_range);
					
				$new_dicek_range_string = $this->convert_dicek_array_to_string($new_dicek_range);
				$new_dicek_value_string = $this->convert_dicek_array_to_string($new_dicek_value);
				if(!empty($current_dicek_range_string)) {
					$new_dicek_range_string = '-' . $new_dicek_range_string;
					$new_dicek_value_string = '-' . $new_dicek_value_string;
				}					
				$current_dicek_value_string = $this->get_current_dicek_value_string($post_id);
					
				$dicek_range_string = $current_dicek_range_string . $new_dicek_range_string;
				$dicek_value_string = $current_dicek_value_string . $new_dicek_value_string;
					
				$sql_update = 'UPDATE ' . $this->posts_table . '
							SET dicek_range = "' . $dicek_range_string . '", dicek_value = "' . $dicek_value_string . '"
							WHERE post_id = ' . (int) $post_id;
				$this->db->sql_query($sql_update);
			}
		}
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
			$this->post_list[$i] = $row['post_id'];
		}
		$this->iterator = 0;
		$this->end = $end;
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
			$this->post_list[$i] = $row['post_id'];
		}
		$this->iterator = 0;
		$this->end = $end;
	}
	
	public function iterate() {
		if(isset($this->iterator)) {
			$this->iterator = $this->iterator + 1;
			if($this->iterator >= $this->end) {
				unset($this->iterator);
			}
		}
	}
	
	public function modify_text_for_display_after($event) {
		if(isset($this->iterator)) {
			$event['text'] = $this->parse_bbcode_dicek($this->post_list[$this->iterator], $event['text']);
		}
	}
	
	public function parse_bbcode_dicek($post_id, $message) {
		$post_dicek_range_string = $this->get_current_dicek_range_string($post_id);
		if(empty($post_dicek_range_string) === false) {
			$post_dicek_value_string = $this->get_current_dicek_value_string($post_id);
			$post_dicek = $this->get_current_dicek($post_dicek_range_string, $post_dicek_value_string);
			$message = $this->message_dicek_after($message, $post_dicek, $post_id);
		}
		$message = $this->message_checkdicek_after($message, $post_id);
		return $message;
	}
	
	public function generate_dicek_value($dicek_range) {
		$dicek_value = array();
		foreach($dicek_range as $index => $max) {
			$dicek_value[$index] = rand(1, intval($max));
		}
		return $dicek_value;
	}
	
	public function new_dicek_range($current, $posting) {
		$new_dicek_range = array();
		$current_count = $this->count_repeat_value($current);
		$posting_count = $this->count_repeat_value($posting);
		foreach($posting as $value) {
			if(!in_array($value, $current, true) || $posting_count[$value] > $current_count[$value]) {
				array_push($new_dicek_range, $value);
				$posting_count[$value] = $posting_count[$value] - 1;
			}
		}
		return $new_dicek_range;
	}
	
	public function count_repeat_value($arr) {
		$count = array();
		foreach($arr as $value) {
			if(!array_key_exists($value, $count)) {
				$count[$value] = 0;
			}
			$count[$value] = $count[$value] + 1;
		}
		return $count;
	}
	
	public function get_dicek_range_from_message($message) {
		preg_match_all(self::$pattern, $message, $matches);
		$dicek_range = array();
		foreach($matches[1] as $match) {
			$match_arr = $this->convert_dicek_string_to_array($match);
			$dicek_range = array_merge($dicek_range, $match_arr);
		}
		return $dicek_range;
	}
	
	public function get_current_dicek_range_string($post_id) {
		$sql_dicek_range = 'SELECT dicek_range
						   FROM ' . $this->posts_table . '
						   WHERE post_id = ' . (int) $post_id;
		$sql_dicek_range_result = $this->db->sql_query($sql_dicek_range);
		$data = $this->db->sql_fetchrow($sql_dicek_range_result);
		$this->db->sql_freeresult($sql_dicek_range_result);
		return $data['dicek_range'];
	}
	
	public function get_current_dicek_value_string($post_id) {
		$sql_dicek_value = 'SELECT dicek_value
						   FROM ' . $this->posts_table . '
						   WHERE post_id = ' . (int) $post_id;
		$sql_dicek_value_result = $this->db->sql_query($sql_dicek_value);
		$data = $this->db->sql_fetchrow($sql_dicek_value_result);
		$this->db->sql_freeresult($sql_dicek_value_result);
		return $data['dicek_value'];
	}
	
	public function convert_dicek_string_to_array($string) {
		if(empty($string) == false) {
			return explode('-', $string);
		}
		return array();
	}
	
	public function convert_dicek_array_to_string($arr) {
		return implode('-', $arr);
	}
	
	public function get_current_dicek($dicek_range_string, $dicek_value_string) {
		$dicek_range = $this->convert_dicek_string_to_array($dicek_range_string);
		$dicek_value = $this->convert_dicek_string_to_array($dicek_value_string);
		$dicek = $this->key_to_data($dicek_range, $dicek_value);
		return $dicek;
	}
	
	public function key_to_data($key, $data) {
		$arr = array();
		foreach($data as $index => $value) {
			if(!isset($arr[$key[$index]])) {
				$arr[$key[$index]] = array();
			}
			array_push($arr[$key[$index]], $value);
		}
		return $arr;
	}
	
	public function message_dicek_after($message, $post_dicek, $post_id) {
		$dicek_range_index = array();
		return preg_replace_callback(self::$pattern,
		function ($match) use ($post_dicek, $post_id, &$dicek_range_index) {
			return $this->bb_dicek_replace($match[1], $dicek_range_index, $post_dicek, $post_id);
		}, $message);
	}	
	
	public function message_checkdicek_after($message, $current_post_id) {
		return preg_replace_callback(self::$pattern_checkdicek,
		function ($match) use ($current_post_id) {
			return $this->bb_checkdicek_replace($match[1], $current_post_id);
		}, $message);
	}
	
	public function bb_dicek_replace($dicek_range_string, &$dicek_range_index, $post_dicek, $post_id) {
		$dicek_range = $this->convert_dicek_string_to_array($dicek_range_string);
		$dicek_value = array();
		foreach($dicek_range as $range) {
			if(array_key_exists($range, $dicek_range_index) == false) {
				$dicek_range_index[$range] = 0;
			}
			array_push($dicek_value, $post_dicek[$range][$dicek_range_index[$range]]);
			$dicek_range_index[$range] = $dicek_range_index[$range] + 1;
		}
		return '<a target="_blank" href="checkdicek.php?post_id=' . $post_id . '">' . $this->convert_dicek_array_to_string($dicek_value) . '</a>';
	}
	
	public function bb_checkdicek_replace($post_id, $current_post_id) {
		if(empty($post_id)) {
			$post_id = $current_post_id;
		}
		$message = '<hr />POST ID: ' . $post_id . '<br /> Dice Range: ' .
					$this->get_current_dicek_range_string($post_id) .
					'<br /> Corresponding Dice Result: ' . $this->get_current_dicek_value_string($post_id) . '<hr />';
		return $message;
	}
}
