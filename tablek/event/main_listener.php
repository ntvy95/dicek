<?php

namespace koutogima\tablek\event;

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
			'core.modify_text_for_display_after' => 'modify_text_for_display_after',
			'core.modify_format_display_text_after' => 'modify_format_display_text_after',
		);
	}
	
	static $pattern_row_overall;
	/*sstatic $pattern_col_overall;
	static $pattern_class;
	static $pattern_id;*/
	
	public function __construct() {
		self::$pattern_row_overall = "@\{(|((?!\}).)+)\}@";
		/*self::$pattern_class = "@class=(|(.+?))((?=([a-zA-Z0-9-_ ]+)))@i";
		self::$pattern_id = "@id=[^\s]+@i";
		self::$pattern_colspan = "@colspan=[0-9]+@i";*/
	}
	
	public function modify_text_for_display_after($event) {
		$event['text'] = self::tablek_parse($event['text']);
	}
	
	public function modify_format_display_text_after($event) {
		$event['text'] = self::tablek_parse($event['text']);
	}
	
	static public function tablek_parse($message) {
			$open_tag_start = "[tablek";
			$open_tag_end = "]";
			$close_tag = "[/tablek]";
			$isopen = stripos($message, $open_tag_start, 0);
			while($isopen !== false) {
				$isopen = $isopen + strlen($open_tag_start);
				$isopen2 = strpos($message, $open_tag_end, $isopen);
				if($isopen2 !== false) {
					$table_tag = substr($message, $isopen, $isopen2 - $isopen);
					$isopen2 = $isopen2 + strlen($open_tag_end);
					$isclose = stripos($message, $close_tag, $isopen2);
					if($isclose !== false) {
						$table = substr($message, $isopen2, $isclose - $isopen2);
						if(empty($table_tag) == false) {
							$table_tag = explode("|", $table_tag, 2);
							$table_tag_html = $table_tag[0];
							/*if(empty($table_tag_html) == false) {
								//BEGIN: Table HTML attributes.
								preg_match(self::$pattern_id, $table_tag_html, $table_id);
								preg_match(self::$pattern_class, $table_tag_html, $table_class);
								//END: Table HTML attributes.
							}*/
							$table_tag_css = '';
							if(isset($table_tag[1])) {
								$table_tag_css = $table_tag[1];
							}
							$head = '<table ' . $table_tag_html . ' style="' . $table_tag_css . '">';
						}
						else {
							$head = '<table>';
						}
						$body = $table;
						$tail = '</table>';
						$rows = preg_split(self::$pattern_row_overall, $body, NULL, PREG_SPLIT_OFFSET_CAPTURE);
						$rows_collect = array();
						for($index = 1, $index_end = sizeof($rows); $index < $index_end; $index++){
							$row = $rows[$index];
							$begin_tag = strpos($body, '{', intval($rows[$index - 1][1])) + 1;
							$end_tag = strpos($body, '}', $begin_tag);
							if($end_tag === false) {
								unset($rows[$index]);
								continue;
							}
								
							$row_tag = substr($body, $begin_tag, $end_tag - $begin_tag);
							$row_tag = explode("|", $row_tag, 2);
							$row_tag_html = $row_tag[0];
							/*if(empty($row_tag_html) == false) {
								//BEGIN: Row HTML attributes.
								preg_match(self::$pattern_id, $row_tag_html, $row_id);
								preg_match(self::$pattern_class, $row_tag_html, $row_class);
								//END: Row HTML attributes.
							}*/
							$row_tag_css = '';
							if(isset($row_tag[1])) {
								$row_tag_css = $row_tag[1];
							}
								
							$head_row = '<tr ' . $row_tag_html . ' style="' . $row_tag_css . '">';
							$body_row = self::row_parse($row[0]);
							if(strcmp($body_row, $row[0]) === 0) {
								$body_row = '';
							}
							$tail_row = '</tr>';
								
							$rows_collect[$index] = $head_row . $body_row . $tail_row;				
						}
						$body = implode($rows_collect);
						$table = $head . $body . $tail;
						$isclose = $isclose + strlen($close_tag);
						$isopen = $isopen - strlen($open_tag_start);
						$message = substr_replace($message, $table, $isopen, $isclose - $isopen);
					}
					else {
						break;
					}
				}
				else {
					break;
				}
				$isopen = stripos($message, $open_tag_start, $isopen - strlen($open_tag_start) + strlen($table));
			}
			return $message;
	}
	
	static public function row_parse($row) {
		$cols = explode("|", $row);
			unset($cols[0]);
			foreach($cols as $index => $col) {
				$content = explode("}", $col, 2);
				if(sizeof($content) === 1) {
					$head_col = '<td>';
					$body_col = $col;
					$tail_col = '</td>';
				}
				else {
					$col_tag = explode("[", $content[0], 2);
					$col_tag_html = $col_tag[0];
					/*if(empty($col_tag_html) == false) {
						//BEGIN: Col HTML attributes.
						preg_match(self::$pattern_id, $col_tag_html, $col_id);
						preg_match(self::$pattern_class, $col_tag_html, $col_class);
						//END: Col HTML attributes.
					}*/
					$col_tag_css = '';
					if(isset($col_tag[1])) {
						$col_tag_css = $col_tag[1];
					}
					$head_col = '<td ' . $col_tag_html . ' style="' . $col_tag_css . '">';
					$body_col = $content[1];
					$tail_col = '</td>';
				}
				$cols[$index] = $head_col . $body_col . $tail_col;
			}
		return implode($cols);
	}
}

?>