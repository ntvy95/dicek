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
			'core.modify_text_for_display_before' => 'modify_text_for_display_before',
		);
	}
	
	static $open_tag;
	static $close_tag;
	
	public function __construct() {
		self::$open_tag = "[copy]";
		self::$close_tag = "[/copy]";
	}
	
	public function modify_text_for_display_before($event) {
		$event['text'] = self::copy_parse($event['text']);
	}
	
	static public function copy_parse($message) {
		$open_tag_pos = stripos($message, self::$open_tag);
		while($open_tag_pos !== false) {
			$open_tag_pos = $open_tag_pos + strlen(self::$open_tag);
			$close_tag_pos = stripos($message, self::$close_tag);
			if($close_tag_pos !== false) {
				$display_content = substr($message, $open_tag_pos, $close_tag_pos - $open_tag_pos);
				$copy_content = str_replace(array("[", "]"), array("&#91;", "&#93;"), $display_content);
				$content = '<textarea readonly="readonly" onclick="this.select();" style="width: 100%; max-height: 2em; overflow: hidden;">'
							. $copy_content . '</textarea>' . $display_content;
				$open_tag_pos = $open_tag_pos - strlen(self::$open_tag);
				$close_tag_pos = $close_tag_pos + strlen(self::$close_tag);
				$message = substr_replace($message, $content, $open_tag_pos, $close_tag_pos - $open_tag_pos);;
			}
			$next_pos = $open_tag_pos + strlen($content);
			$open_tag_pos = stripos($message, self::$open_tag, $next_pos);
		}
		return $message;
	}
}
