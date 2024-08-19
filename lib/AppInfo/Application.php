<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: RIKIPB <dkron@outlook.it>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Nextembed\AppInfo;

use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher; 
use OCA\Files\Event\LoadAdditionalScriptsEvent; 
use OCP\Util;

class Application extends App {
	public const APP_ID = 'nextembed';

	public function __construct() {
		parent::__construct(self::APP_ID);

		$container = $this->getContainer();
		$eventDispatcher = $container->get(IEventDispatcher::class);
		
		
		// load files plugin script when the Files app triggers the LoadAdditionalScriptsEvent event
		$eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			// this loads the js/filesplugin.js script once the Files app has done loading its scripts
			Util::addScript(self::APP_ID, 'filesplugin', 'files');
			Util::addStyle(self::APP_ID, 'nextembed-style');
		});
	}
}
