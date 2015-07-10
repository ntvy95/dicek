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
			//'core.posting_modify_submit_post_before' => 'posting_modify_submit_post_before',
			//'core.posting_modify_submit_post_after' => 'posting_modify_submit_post_after',
			//'core.posting_modify_message_text' => 'posting_modify_message_text',
			'core.submit_post_end' => 'submit_post_end',
			'core.viewtopic_modify_post_data' => 'viewtopic_modify_post_data',
			'core.modify_posting_auth' => 'modify_posting_auth',
			'core.modify_format_display_text_after' => 'modify_format_display_text_after',
		);
	}
	protected $db;
	protected $posts_table;
	protected $post_id;
	protected $dicek_range_index;
	protected $post_dicek;
	static $pattern;
	static $pattern_checkdicek;
	
	public function __construct(\phpbb\db\driver\driver_interface $db, $posts_table) {
		$this->db = $db;
		$this->posts_table = $posts_table;
		$this->dicek_range_index = array();
		self::$pattern = '@\[dicek\]([0-9]+|([0-9]+(-[0-9]+)+))\[/dicek\]@i';
		self::$pattern_checkdicek = '@\[checkdicek](|[0-9]+)\[/checkdicek]@i';
	}
	
	public function submit_post_end($event) {
		$message = $event['data']['message'];
		$post_id = $event['data']['post_id'];
		$dicek_range = self::get_dicek_range_from_message($message);

		if(empty($dicek_range) == false) {

			$current_dicek_range_string = $this->get_current_dicek_range_string($post_id);
			$current_dicek_range = self::convert_dicek_string_to_array($current_dicek_range_string);
			$new_dicek_range = self::new_dicek_range($current_dicek_range, $dicek_range);
			$new_dicek_value = self::generate_dicek_value($new_dicek_range);
			
			$new_dicek_range_string = self::convert_dicek_array_to_string($new_dicek_range);
			$new_dicek_value_string = self::convert_dicek_array_to_string($new_dicek_value);
			
			if(empty($current_dicek_range_string) == false) {
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
	
	static public function generate_dicek_value($dicek_range) {
		$dicek_value = array();
		foreach($dicek_range as $index => $max) {
			$dicek_value[$index] = rand(1, intval($max));
		}
		return $dicek_value;
	}
	
	static public function new_dicek_range($current, $posting) {
		$new_dicek_range = array();
		$current_count = self::count_repeat_value($current);
		$posting_count = self::count_repeat_value($posting);
		foreach($posting as $value) {
			if(in_array($value, $current, true) == false || $posting_count[$value] > $current_count[$value]) {
				array_push($new_dicek_range, $value);
				$posting_count[$value] = $posting_count[$value] - 1;
			}
		}
		return $new_dicek_range;
	}
	
	static public function count_repeat_value($arr) {
		$count = array();
		foreach($arr as $value) {
			if(array_key_exists($value, $count) == false) {
				$count[$value] = 0;
			}
			$count[$value] = $count[$value] + 1;
		}
		return $count;
	}
	
	static public function get_dicek_range_from_message($message) {
		preg_match_all(self::$pattern, $message, $matches);
		$dicek_range = array();
		foreach($matches[1] as $match) {
			$match_arr = self::convert_dicek_string_to_array($match);
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
	
	static public function convert_dicek_string_to_array($string) {
		if(empty($string) == false) {
			return explode('-', $string);
		}
		return array();
	}
	
	static public function convert_dicek_array_to_string($arr) {
		return implode('-', $arr);
	}
	
	static public function get_current_dicek($dicek_range_string, $dicek_value_string) {
		$dicek_range = self::convert_dicek_string_to_array($dicek_range_string);
		$dicek_value = self::convert_dicek_string_to_array($dicek_value_string);
		$dicek = self::key_to_data($dicek_range, $dicek_value);
		return $dicek;
	}
	
	static public function key_to_data($key, $data) {
		$arr = array();
		foreach($data as $index => $value) {
			if($arr[$key[$index]] == NULL) {
				$arr[$key[$index]] = array();
			}
			array_push($arr[$key[$index]], $value);
		}
		return $arr;
	}
	
	public function message_dicek_after($message) {
		return preg_replace_callback(self::$pattern,
		function ($match) {
			return $this->bb_dicek_replace($match[1]);
		}, $message);
	}	
	
	public function message_checkdicek_after($message) {
		return preg_replace_callback(self::$pattern_checkdicek,
		function ($match) {
			return $this->bb_checkdicek_replace($match[1]);
		}, $message);
	}
	
	public function bb_dicek_replace($dicek_range_string) {
			$dicek_range = self::convert_dicek_string_to_array($dicek_range_string);
				$dicek_value = array();
				foreach($dicek_range as $range) {
					if(array_key_exists($range, $this->dicek_range_index) == false) {
						$this->dicek_range_index[$range] = 0;
					}
					array_push($dicek_value, $this->post_dicek[$range][$this->dicek_range_index[$range]]);
					$this->dicek_range_index[$range] = $this->dicek_range_index[$range] + 1;
				}
				return self::convert_dicek_array_to_string($dicek_value);
	}
	
	public function bb_checkdicek_replace($post_id) {
		if(empty($post_id)) {
			if($this->post_id != NULL) {
				$post_id = $this->post_id;
			}
			else {
				return '[checkdicek][/checkdicek]';
			}
		}
		$message = '<hr />POST ID: ' . $post_id . '<br /> Dice Range: ' .
					self::get_current_dicek_range_string($post_id) .
					'<br /> Corresponding Dice Result: ' . self::get_current_dicek_value_string($post_id) . '<hr />';
		return $message;
	}
	
	public function viewtopic_modify_post_data($event) {
		$rowset = $event['rowset'];
		for($i = 0, $size = sizeof($event['post_list']); $i < $size; $i++) {
			$this->post_id = $event['post_list'][$i];
			$message = $rowset[$this->post_id]['post_text'];
			$post_dicek_range_string = $this->get_current_dicek_range_string($this->post_id);
			if(empty($post_dicek_range_string) === false) {
				$post_dicek_value_string = $this->get_current_dicek_value_string($this->post_id);
				$this->post_dicek = self::get_current_dicek($post_dicek_range_string, $post_dicek_value_string);
				$message = $this->message_dicek_after($message);
				$this->dicek_range_index = array();
			}
			$message = $this->message_checkdicek_after($message);
			$rowset[$this->post_id]['post_text'] = $message;
		}
		$event['rowset'] = $rowset;
	}
	
	public function modify_format_display_text_after($event) {
		$event['text'] = $this->message_checkdicek_after($event['text']);
	}
	
	public function modify_posting_auth($event) {
		if(empty($event['post_id']) == false) {
			$this->post_id = $event['post_id'];
		}
	}
}
