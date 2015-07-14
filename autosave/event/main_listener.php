<?php

namespace koutogima\autosave\event;

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
			'core.user_setup' => 'user_setup',
		);
	}
	protected $user;
	
	public function __construct(\phpbb\user $user) {
		$this->user = $user;
	}
	
	public function user_setup($event) {
		$this->user->add_lang_ext('koutogima/autosave', 'common');
	}
}
