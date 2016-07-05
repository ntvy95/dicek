<?php
/** 
*
* @package Hide_BBcode
* @copyright (c) 2016 Kou Togima based on Marco van Oort's work
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2 
*
*/

namespace koutogima\hide\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	protected $user;
	protected $template;
	protected $current_row;
	protected $hilit;

	/**
	* Constructor
	*
	* @param \phpbb\db\driver\driver $db Database object
	* @param \phpbb\controller\helper    $helper        Controller helper object
	*/
	public function __construct(\phpbb\user $user, \phpbb\template\template $template)
	{
		$this->user = $user;
		$this->template = $template;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'	=> 'load_language_on_setup',
			'core.viewtopic_post_rowset_data'	=> 'viewtopic_post_rowset_data',
			'core.topic_review_modify_row' => 'topic_review_modify_row',
			'core.modify_format_display_text_after' => 'modify_format_display_text_after',
			'core.search_modify_tpl_ary' => 'search_modify_tpl_ary',
			'core.search_modify_rowset' => 'search_modify_rowset',
		);
	}
	
		/**
	* Load common files during user setup
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'koutogima/hide',
			'lang_set' => 'hide_bbcode',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
	
	public function viewtopic_post_rowset_data($event) {
		$rowset_data = $event['rowset_data'];
		$this->replace_hide_bbcode_wrapper($rowset_data['user_id'], $rowset_data['bbcode_uid'], $rowset_data['post_text'], false);
		$event['rowset_data'] = $rowset_data;
	}
	
	public function topic_review_modify_row($event) {
		$post_row = $event['post_row'];
		$row = $event['row'];
		$this->replace_hide_bbcode_wrapper($row['user_id'], $row['bbcode_uid'], $post_row['MESSAGE'], false);
		$this->replace_hide_bbcode_wrapper($row['user_id'], $row['bbcode_uid'], $post_row['DECODED_MESSAGE'], true);
		$event['post_row'] = $post_row;
	}
	
	public function modify_format_display_text_after($event) {
		$text = $event['text'];
		$this->replace_hide_bbcode_wrapper($this->user->data['user_id'], null, $text, false);
		$event['text'] = $text;
	}
	
	public function search_modify_rowset($event) {
		$this->hilit = $event['hilit'];
	}
	
	public function search_modify_tpl_ary($event) {
		$tpl_ary = $event['tpl_ary'];
		$message = $tpl_ary['MESSAGE'];
		if($this->hilit == "hide") {
			$message = str_replace('<span class="posthilit">hide</span>', 'hide', $message);
		}
		$message = preg_replace('@\[hide(|\=(<span class="posthilit">([0-9,]+)</span>)(|\|([0-9,]+)))(|'
								.$event['row']['bbcode_uid'].')\]@', '[hide=${3}|${5}]', $message);
		$message = preg_replace('@\[hide(|\=(|[0-9,]+)(|\|(<span class="posthilit">([0-9,]+)</span>)))(|'
								.$event['row']['bbcode_uid'].')\]@', '[hide=${2}|${5}]', $message);
		$this->replace_hide_bbcode_wrapper($event['row']['poster_id'], $event['row']['bbcode_uid'], $message, true);
		$tpl_ary['MESSAGE'] = $message;
		$event['tpl_ary'] = $tpl_ary;
	}
	
	public function replace_hide_bbcode_wrapper($user_id, $bbcode_uid, &$message, $decoded) {
		$this->current_row['user_id'] = $user_id;
		$this->current_row['regex']['open_tag'] = "@\[hide(|\=(|[0-9,]+)(|\|([0-9,]+)))(|:". $bbcode_uid .")\]@is";
		$this->current_row['regex']['close_tag'] = "@\[/hide(|:". $bbcode_uid .")\]@is";
		if(preg_match_all($this->current_row['regex']['open_tag'], $message, $open_matches, PREG_OFFSET_CAPTURE)
		&& preg_match_all($this->current_row['regex']['close_tag'], $message, $close_matches, PREG_OFFSET_CAPTURE))
		{
			$result = $this->find_pairs($open_matches, $close_matches);
			$matches = $result[0];
			$tree = $result[1];
			//var_dump("-----------TREE------------\n");
			//var_dump($tree);
			//var_dump($matches);
			//var_dump($open_matches);
			if(!$decoded) {
				$this->template->set_style(array('styles', 'ext/koutogima/hide/styles'));
				$bbcode = new \bbcode();
				$bbcode->template_filename = $this->template->get_source_file_for_handle('hide_bbcode.html');
				$unhide_open = $bbcode->bbcode_tpl('unhide_open');
				$unhide_close = $bbcode->bbcode_tpl('unhide_close');
				$hide = $bbcode->bbcode_tpl('hide');
			}
			else {
				$unhide_open = "[hide]";
				$unhide_close = "[/hide]";
				$hide = "[hide][/hide]";
			}
			$message = $this->replace_hide_bbcode($message, $tree, $matches, $open_matches, $close_matches, $unhide_open, $unhide_close, $hide);
		}
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
	
	public function replace_hide_bbcode($subject, $tree, $matches, $open_matches, $close_matches, $unhide_open, $unhide_close, $hide){
		$len['unhide_open'] = strlen($unhide_open);
		$len['unhide_close'] = strlen($unhide_close);
		$len['hide'] = strlen($hide);
		
		$start_dist = 0;
		$previous_depth = 0;
		$current_close = -1;
		//var_dump("-----------STACK------------\n");
		foreach($tree as $parent => $children) {
			$stack = array();
			array_push($stack, array($parent, $children, 0));
			while(count($stack) > 0) {
				//var_dump("=======\n");
				$current = array_pop($stack);
				//var_dump($current);
				$i = $current[0];
				$j = $matches[$current[0]];
				$len['close_tag'] = strlen($close_matches[0][$j][0]);
				$len['open_tag'] = strlen($open_matches[0][$i][0]);
				$start = $open_matches[0][$i][1];
				$length = $close_matches[0][$j][1] + $len['close_tag'] - $start;
				$more_dist = 0;
				if($current_close != -1
				&& $j > $current_close) {
					$start_dist = $start_dist + ($len['unhide_close'] - $len['close_tag']) * ($previous_depth - $current[2] + 1);
				}
				if($this->user->data['user_id'] == $this->current_row['user_id']
				|| in_array($this->user->data['user_id'], explode(',', $open_matches[2][$i][0]))
				|| in_array($this->user->data['group_id'], explode(',', $open_matches[4][$i][0]))) {
					$replace_string = $unhide_open
					. substr($subject,
							$start_dist + $start + $len['open_tag'],
							$length - $len['close_tag'] - $len['open_tag'])
					. $unhide_close;
					$more_dist = $len['unhide_open'];
					if(is_array($current[1])) {
						$current[1] = array_reverse($current[1], true);
						foreach($current[1] as $child => $grandchildren) {
							array_push($stack, array($child, $grandchildren, $current[2] + 1));
						}
					}
					$previous_depth = $current[2];
					$current_close = $j;
				}
				else {
					$replace_string = $hide;
					$more_dist = $len['hide'] - $length + $len['open_tag'];
				}
				//var_dump(substr(
				//	$subject, $start_dist + $start, $length
				//));
				//var_dump($replace_string);
				$subject = substr_replace($subject, $replace_string, $start_dist + $start, $length);
				$start_dist = $start_dist + $more_dist - $len['open_tag'];
			}
		}
		/*$close_threshold = null;
		$current_parent_close = null;
		$parent_close_count = 0;
		ksort($matches);
		foreach($matches as $i => $j) {
			if($close_threshold === null || $j > $close_threshold) {
				$len['close_tag'] = strlen($close_matches[0][$j][0]);
				$len['open_tag'] = strlen($open_matches[0][$i][0]);
				
				$start = $open_matches[0][$i][1];
				$length = $close_matches[0][$j][1] + $len['close_tag'] - $start;
				$more_dist = 0;
				
				if($current_parent_close !== null
				&& $j > $current_parent_close)
				{
					$start_dist = $start_dist + ($len['unhide_close'] - $len['close_tag']);
				}
				if($this->user->data['user_id'] == $this->current_row['user_id']
				|| in_array($this->user->data['user_id'], explode(',', $open_matches[2][$i][0]))
				|| in_array($this->user->data['group_id'], explode(',', $open_matches[4][$i][0]))) {
					$replace_string = $unhide_open
					. substr($subject,
							$start_dist + $start + $len['open_tag'],
							$length - $len['close_tag'] - $len['open_tag'])
					. $unhide_close;
					$more_dist = $len['unhide_open'];
					$current_parent_close = $j;
				}
				else {
					$replace_string = $hide;
					$more_dist = $len['hide'] - $length + $len['open_tag'];
					$close_threshold = $j;
					$current_parent_close = null;
					$parent_close_count = 0;
				}
				var_dump(substr(
					$subject, $start_dist + $start, $length
				));
				var_dump($replace_string);
				$subject = substr_replace($subject, $replace_string, $start_dist + $start, $length);
				$start_dist = $start_dist + $more_dist - $len['open_tag'];
			}
		}*/
		return $subject;
	}
}
