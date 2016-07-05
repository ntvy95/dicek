<?php

namespace koutogima\copy\event;

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
			'core.viewtopic_post_rowset_data' => 'viewtopic_post_rowset_data',
			'core.topic_review_modify_row' => 'topic_review_modify_row',
			'core.modify_text_for_display_after' => 'modify_text_for_display_after',
			'core.modify_format_display_text_before' => 'modify_format_display_text_before',
			'core.modify_format_display_text_after' => 'modify_format_display_text_after',
			'core.ucp_pm_view_messsage' => 'ucp_pm_view_messsage',
		);
	}
	
	protected $open_tag;
	protected $close_tag;
	protected $len;
	protected $replace_string;
	
	public function __construct() {
		$this->open_tag = "@\[copy\]@is";
		$this->close_tag = "@\[\/copy\]@is";
		$this->len['open_tag'] = strlen("[copy]");
		$this->len['close_tag'] = strlen("[/copy]");
		$this->replace_string['open_tag'] =
			'<textarea readonly="readonly" onclick="this.select();" style="width: calc(100% - 6px); max-height: 1em; overflow: hidden; display: block;">';
		$this->replace_string['close_tag'] = '</textarea>';
		$this->len['replace_open_tag'] = strlen($this->replace_string['open_tag']);
		$this->len['replace_close_tag'] = strlen($this->replace_string['close_tag']);
	}
	
	public function viewtopic_post_rowset_data($event) {
		$rowset_data = $event['rowset_data'];
		$rowset_data['post_text'] = $this->copy_parse_wrapper($rowset_data['post_text']);
		$event['rowset_data'] = $rowset_data;
	}
	
	public function topic_review_modify_row($event) {
		$post_row = $event['post_row'];
		$row = $event['row'];
		$post_row['MESSAGE'] = $this->copy_parse_wrapper($post_row['MESSAGE']);
		$event['post_row'] = $post_row;
	}
	
	public function ucp_pm_view_messsage($event) {
		$msg_data = $event['msg_data'];
		$msg_data['MESSAGE'] = $this->copy_parse_wrapper($msg_data['MESSAGE']);
		$event['msg_data'] = $msg_data;
	}
	
	public function modify_text_for_display_after($event) {
		$event['text'] = $this->remove_br($event['text']);
	}
	
	public function modify_format_display_text_before($event) {
		$event['text'] = $this->copy_parse_wrapper($event['text']);
	}
	
	public function modify_format_display_text_after($event) {
		$event['text'] = $this->remove_br($event['text']);
	}
	
	public function copy_parse_wrapper($message) {
		if(preg_match_all($this->open_tag, $message, $open_matches, PREG_OFFSET_CAPTURE)
		&& preg_match_all($this->close_tag, $message, $close_matches, PREG_OFFSET_CAPTURE))
		{
			$result = $this->find_pairs($open_matches, $close_matches);
			$matches = $result[0];
			$tree = $result[1];
			//var_dump($result);
			//var_dump("=====================\n");
			$result = $this->replace_copy_bbcode($message, $tree, $matches, $open_matches, $close_matches);
			$message = $result[0];
		}
		return $message;
	}
	
	public function replace_copy_bbcode($message, $tree, $matches, $open_matches, $close_matches, $start_dist = 0, $message_length = 0) {
		foreach($tree as $parent => $children) {
			$i = $parent;
			$j = $matches[$parent];
			$start = $open_matches[0][$i][1];
			$copy_content_length = $close_matches[0][$j][1] - $this->len['open_tag'] - $start;
			$display_content = substr(	$message,
										$start_dist + $start + $this->len['open_tag'],
										$copy_content_length);
			$replace_count = 0;
			$copy_content = str_replace(array("[", "]"), array("&#91;", "&#93;"), $display_content, $replace_count);
			//var_dump($message);
			//var_dump("--------------------\n");
			//var_dump($display_content);
			if(is_array($children)) {
				$result = $this->replace_copy_bbcode($display_content, $children, $matches, $open_matches, $close_matches,
						- $start - $this->len['open_tag'], $copy_content_length);
				//var_dump($result);
				$display_content = $result[0];
				$display_content_length = $result[1];
			}
			else {
				$display_content_length = $copy_content_length;
			}
			$length = $this->len['open_tag'] + $this->len['close_tag'] + $display_content_length;
			$content = $this->replace_string['open_tag'] . $copy_content . $this->replace_string['close_tag'] . $display_content;
			$message = substr_replace($message, $content, $start_dist + $start, $length);
			$dist = - $this->len['open_tag'] - $this->len['close_tag']
						+ $display_content_length + $replace_count * 4 + $this->len['replace_open_tag'] + $this->len['replace_close_tag'];
			$start_dist = $start_dist + $dist;
			$message_length = $message_length + $dist;
			//var_dump($message);
		}
		return array($message, $message_length);
	}
	
	public function remove_br($message) {
		$open_tag = "<textarea ";
		$close_tag = "</textarea>";
		$open_tag_pos = stripos($message, $open_tag);
		while($open_tag_pos !== false) {
			$open_tag_pos = $open_tag_pos + strlen($open_tag);
			$close_tag_pos = stripos($message, $close_tag, $open_tag_pos);
			if($close_tag_pos !== false) {
				$content = substr($message, $open_tag_pos, $close_tag_pos - $open_tag_pos);
				$content = str_replace("<br />", "\n", $content);
				$message = substr_replace($message, $content, $open_tag_pos, $close_tag_pos - $open_tag_pos);
			}
			$next_pos = $open_tag_pos + strlen($content);
			$open_tag_pos = stripos($message, $open_tag, $next_pos);
		}
		return $message;
	}
	
	public function find_pairs($open_matches, $close_matches) {
		$matches = array();
		$tree_inf = array();
		if(count($open_matches[0]) > count($close_matches[0])) {
			$current = count($open_matches[0]) - count($close_matches[0]);
		}
		else {
			$current = 0;
		}
		$prev[$current] = $current - 1; $next[$current] = $current + 1;
		$max_current = 0;
		$tree_inf[$current] = $current;
		for($i = 0; $i < count($close_matches[0]); $i++) {
			while($current != -1) {
				if($next[$current] < count($open_matches[0])
				&& $open_matches[0][$next[$current]][1] < $close_matches[0][$i][1]) {
					if(isset($tree_inf[$current])
					  && $tree_inf[$current] == $current) {
						$tree_inf[$current] = array();
					}
					$tree_inf[$current][$next[$current]] = $next[$current];
					$prev[$next[$current]] = $current;
					$current = $next[$current];
					if(!isset($next[$current])) {
						$next[$current] = $current + 1;
					}
				}
				else {
					if($current > $max_current) {
						$max_current = $current;
					}
					$matches[$current] = $i;
					$next[$prev[$current]] = $next[$current];
					$current = $prev[$current];
					break;
				}
			}
			if($current == -1
			&& $max_current + 1 < count($open_matches[0])) {
				$current = $max_current + 1;
				$next[$current] = $current + 1;
				$tree_inf[$current] = $current;
				$prev[$current] = -1;
			}
		}
		while(count($tree_inf) > 0) {
			$traverse = key($tree_inf);
			$tree[$traverse] = $this->buildTree($traverse, $tree_inf);
		}
		return array($matches, $tree);
	}
	
	public function buildTree($parent, &$tree_inf) {
		if(isset($tree_inf[$parent])) {
			$children = $tree_inf[$parent];
			unset($tree_inf[$parent]);
			if(is_array($children)) {
				foreach($children as &$child) {
					$child = $this->buildTree($child, $tree_inf);
				}
			}
		}
		else {
			$children = $parent;
		}
		return $children;
	}
}
