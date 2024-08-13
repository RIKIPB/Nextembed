<?php

// SPDX-FileCopyrightText: RIKIPB <dkron@outlook.it>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Nextembed\Controller;

use \OC;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use Imagick;
use OCP\AppFramework\Http\DataResponse;


class PageController extends Controller {
    private $userSession;
    private $rootFolder;
    private $urlGenerator;

    public function __construct($appName, IRequest $request, IUserSession $userSession, IRootFolder $rootFolder, IURLGenerator $urlGenerator) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->rootFolder = $rootFolder;
        $this->urlGenerator = $urlGenerator;
    }

    private function getSecret() {       
        return \OC::$server->getConfig()->getSystemValue('secret') ?? 'default_secret'; // Fallback in caso il secret non sia trovato
    }

    private function getSecretIV() {
        return \OC::$server->getConfig()->getSystemValue('passwordsalt') ?? 'default_IV';
    }

    private function encrypt($string) {
        $output = false;
        $key = $this->getSecret();
        $IV = $this->getSecretIV();
        $key = hash('sha256', $key);
        $iv = substr(hash('sha256', $IV), 0, 16);
        $output = openssl_encrypt($string, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($output);
    } 
    

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return DataResponse
     */
    public function tokenizeFile($fileId) {
        $file = $this->getFileById($fileId);

        // Genera un token usando la criptazione
        $token = $this->encrypt($file);

        return new DataResponse(['fileToken' => $token]);
    }

    private function getFileById($fileId) {
        $file = $this->rootFolder->getById($fileId);

        if (empty($file)) {
            return new RedirectResponse($this->urlGenerator->linkToRoute('core.PageNotFound'));
        }

        $filePath = \OC::$server->getConfig()->getSystemValue('datadirectory').$file[0]->getPath();
        return $filePath;
        // $fileName = $file[0]->getName();
        // $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    }
}

