<?php
// Include la base di Nextcloud e la configurazione
$workdir = realpath('../..');
require_once $workdir . '/lib/versioncheck.php';

try {
	require_once __DIR__ . '/lib/base.php';

	OC::handleRequest();
} catch (Exception $ex) {
    \OC::$server->getLogger()->logException($ex, ['app' => 'index']);
    // Mostra una pagina di errore dettagliata
    OC_Template::printExceptionErrorPage($ex, 500);
    echo $ex->getMessage();
}
exit();
