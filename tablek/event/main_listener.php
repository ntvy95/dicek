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
			'core.modify_text_for_display_before' => 'modify_text_for_display_before',
		);
	}
	
	static $pattern_table;
	static $pattern_row_overall;
	static $pattern_col_overall;
	static $pattern_class;
	static $pattern_id;
	
	public function __construct() {
		//self::$pattern_table = "@\[tablek\]((?:[^[]|\[(?!/?tablek])|(?R))+)\[/tablek]@s";
		//self::$pattern_table = "@\[tablek(| (.)+)\]((?:[^[]|\[(?!/?tablek])|(?R))+)\[/tablek]@s";
		//self::$pattern_table = "@\[tablek(| (.)+)\]((.)+)\[/tablek\]@s";
		self::$pattern_row_overall = "@{(|(.))+}@";
		self::$pattern_class = "@class=(([a-zA-Z0-9-_]+)|('[a-zA-Z0-9-_ ]+)')@";
		self::$pattern_id = "@id=([a-zA-Z0-9-_]+)@";
		ini_set("pcre.recursion_limit", "524");
	}
	
	public function modify_text_for_display_before($event) {
		$event['text'] = self::tablek_parse($event['text']);
	}
	
	static public function tablek_parse($message) {
			$open_tag = "[tablek]";
			$close_tag = "[/tablek]";
			$isopen = strpos($message, $open_tag, 0);
			while($isopen !== false) {
				$isopen = $isopen + strlen($open_tag);
				//$isopen = $open_matches[0][1] + strlen($open_matches[0][0]);
				$isclose = strpos($message, $close_tag, $isopen2);
				//exit(var_dump($isclose));
				if($isclose !== false) {
					$table = substr($message, $isopen, $isclose - $isopen);
					//var_dump($table);
					/*$table_tag = explode("|", $open_matches[0][0], 2);
					$table_tag_html = $table_tag[0];
					if(empty($table_tag_html) == false) {
						//BEGIN: Table HTML attributes.
						preg_match(self::$pattern_id, $table_tag_html, $table_id);
						preg_match(self::$pattern_class, $table_tag_html, $table_class);
						//END: Table HTML attributes.
					}
					$table_tag_css = '';
					if(isset($table_tag[1])) {
						$table_tag_css = $table_tag[1];
					}*/
					//$head = '<table id="' . $table_id[1] . '" class ="' . $table_class[1] . '" style="' . $table_tag_css . '">';
					$head = '<table>';
					$body = $table;
					//var_dump($body);
					$tail = '</table>';
					$rows = preg_split(self::$pattern_row_overall, $body, NULL, PREG_SPLIT_OFFSET_CAPTURE);
					for($index = 1, $index_end = sizeof($rows); $index < $index_end; $index++){
						$row = $rows[$index];
						$begin_tag = strpos($body, '{', intval($rows[$index - 1][1]));
						$end_tag = strpos($body, '}', $begin_tag);
						if($end_tag === false) {
							unset($rows[$index]);
							continue;
						}
							
						$row_tag = substr($body, $begin_tag, $end_tag - $begin_tag);
						$row_tag = explode("|", $row_tag, 2);
						$row_tag_html = $row_tag[0];
						if(empty($row_tag_html) == false) {
							//BEGIN: Row HTML attributes.
							preg_match(self::$pattern_id, $row_tag_html, $row_id);
							preg_match(self::$pattern_class, $row_tag_html, $row_class);
							//END: Row HTML attributes.
						}
						$row_tag_css = '';
						if(isset($row_tag[1])) {
							$row_tag_css = $row_tag[1];
						}
							
						$head_row = '<tr id=' . $row_id[1] . ' class=' . $row_class[1] . ' style="' . $row_tag_css . '" />';
						$body_row = self::row_parse($row[0]);
						if(strcmp($body_row, $row[0]) === 0) {
							$body_row = '';
						}
						$tail_row = '</tr>';
							
						$rows[$index] = $head_row . $body_row . $tail_row;				
					}
					unset($rows[0]);
					$body = implode($rows);
					$table = $head . $body . $tail;
					$isclose = $isclose + strlen($close_tag);
					$message = substr_replace($message, $table, $isopen - strlen($open_tag), $isclose + strlen($close_tag) - $isopen);
				}
				$isopen = strpos($message, $open_tag, $isclose);
			}
			return $message;
		//}
		//return preg_replace_callback(self::$pattern_table, 'self::tablek_parse', $message);
	}
	
	static public function row_parse($row) {
		$cols = explode("|", $row);
		//exit(var_dump($cols));
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
					if(empty($col_tag_html) == false) {
						//BEGIN: Col HTML attributes.
						preg_match(self::$pattern_id, $col_tag_html, $col_id);
						preg_match(self::$pattern_class, $col_tag_html, $col_class);
						//END: Col HTML attributes.
					}
					$col_tag_css = '';
					if(isset($col_tag[1])) {
						$col_tag_css = $col_tag[1];
					}
					$head_col = '<td id=' . $col_id[1] . ' class=' . $col_class[1] . ' style="' . $col_tag_css . '" />';
					$body_col = $content[1];
					$tail_col = '</td>';
				}
				$cols[$index] = $head_col . $body_col . $tail_col;
				//exit(var_dump($cols[$index]));
			}
		//exit(var_dump(implode('', $cols)));
		return implode($cols);
	}
}

?>