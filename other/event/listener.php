<?php

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
	static public function getSubscribedEvents()
	{
		return array(
			'core.viewtopic_modify_post_row' => 'viewtopic_modify_post_row',
		);
	}
	
	public function __construct() {
		
	}
	
	public function viewtopic_modify_post_row($event) {
		$post_row = $event['post_row'];
		$post_row['MESSAGE'] = preg_replace_callback('!\[tip\=([^\h\n\r\(\)]+)\|(.*?)\|([a-zA-Z0-9-_]+)(|:'
								. $event['row']['bbcode_uid'] .')\](.*?)\[/tip(|:'
								. $event['row']['bbcode_uid'] .')\]!is',
								array($this, 'replace_bbcode_tip'), $post_row['MESSAGE']);
		$event['post_row'] = $post_row;
	}
	
	public function replace_bbcode_tip($matches) {
		return '<img src="./styles/alticon.png"><a target="_blank" href="'
				. $matches[1] . '" id="tooltip_'
				. $matches[3] . '" class="tooltip'
				. $matches[3] . '">'
				. $matches[2] . '</a><div id="data_tooltip_'
				. $matches[3] . '" style="display: none;">'
				. $matches[5] . '</div><script type="text/javascript"> $(\'.tooltip'
				. $matches[3] . '\').tooltip();</script>';
	}
}