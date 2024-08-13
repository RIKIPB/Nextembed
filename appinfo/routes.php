<?php
declare(strict_types=1);
// Consenti riferimenti da qualsiasi origine
header('Referrer-Policy: no-referrer');

// Disabilita il sniffing del tipo di contenuto
header('X-Content-Type-Options: nosniff');

// Prevenire l'apertura automatica di file scaricati (per IE)
header('X-Download-Options: noopen');

// Consenti il framing da qualsiasi origine (notare che ALLOW-FROM è deprecato)
header('X-Frame-Options: ALLOWALL');

// Content-Security-Policy per consentire il framing da qualsiasi origine
header('Content-Security-Policy: frame-ancestors *');

// Disabilita le politiche di cross-domain di Adobe
header('X-Permitted-Cross-Domain-Policies: *');

// Disabilita il controllo dei robot sui contenuti
header('X-Robots-Tag: *');

// Abilita la protezione XSS e blocca gli attacchi
header('X-XSS-Protection: 1; mode=block');
// SPDX-FileCopyrightText: RIKIPB <dkron@outlook.it>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Nextembed\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'resources' => [
		'note' => ['url' => '/notes'],
		'note_api' => ['url' => '/api/0.1/notes']
	],
	'routes' => [
		[
            'name' => 'page#embedfile',
            'url' => '/api/0.1/embedfile/{fileId}',
            'verb' => 'GET',
            'defaults' => ['fileId' => null]
        ],
		['name' => 'note_api#preflighted_cors', 'url' => '/api/0.1/{path}',
			'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']]
	]
];
