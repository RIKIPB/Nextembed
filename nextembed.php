<?php
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

// Include la base di Nextcloud e la configurazione
$workdir = realpath('../..');
require_once $workdir . '/lib/versioncheck.php';

try {
    require_once $workdir . '/lib/base.php'; 

    // If no session
    // if (!\OC_User::getUser()) {
     
    // }

    // Recupera l'ID del file dalla richiesta
    $fileId = $_REQUEST['token'] ?? null;

    function detokenizeFile($token) {
        $output = false;
        $key = \OC::$server->getConfig()->getSystemValue('secret') ?? 'default_secret';
        $IV = \OC::$server->getConfig()->getSystemValue('passwordsalt') ?? 'default_IV';
        $key = hash('sha256', $key);
        $iv = substr(hash('sha256', $IV), 0, 16);
        return openssl_decrypt(base64_decode($token), 'AES-256-CBC', $key, 0, $iv);
    }

    /* Handle of embed */
    function embedfile($fileId = null) {               
        if (empty($fileId)) {
            $fileId = $_REQUEST['token'];
        }

        // Recupera il file
        // $file = \OC::$server->getRootFolder()->getById($fileId);        
        $filePath = detokenizeFile($fileId);

        // Debug: Mostra se il file è stato trovato
        if(!file_exists($filePath)) {
            return new OCP\AppFramework\Http\RedirectResponse(\OC::$server->getURLGenerator()->linkToRoute('core.PageNotFound'));
        }

        $fileName = basename($filePath);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Genera le immagini di anteprima
        $previewPaths = generatePreview($filePath, $fileExtension);

        // Debug: Mostra il percorso delle anteprime
        if ($previewPaths && count($previewPaths) > 0) {   
            $html = '<html>
            <style>
            body::before {
        content: "Only for testing purposes";
    position: fixed;
    top: 113px;
    left: 10px;
    font-size: 24px;
    color: rgb(255 11 11 / 40%);
    font-family: Arial, sans-serif;
    transform: rotate(-45deg);
    z-index: 9999;
    pointer-events: none;
    white-space: nowrap;
            </style>
            <body>';
            foreach ($previewPaths as $path) {
                $html .= '<img src="' . htmlspecialchars($path) . '" style="width:100%; margin-bottom:10px;" />';
            }
            $html .= '</body></html>';
            echo $html;
        } else {
            echo "No previews generated.<br>";
            $url = \OC::$server->getURLGenerator()->linkToRoute('files.view.index', ['dir' => dirname($filePath), 'openfile' => $fileId]);
            //return new RedirectResponse($url);
        }
    }

    function generatePreviewImages($filePath, $deletePreview = false) {
        $previewBase64 = [];
        
        // Usa finfo per ottenere il tipo MIME del file
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);
    
        // Gestione dei file PDF
        if ($mimeType === 'application/pdf') {
            $pdf = new \Imagick($filePath);
            $pdf->setResolution(600, 600); // Imposta una risoluzione più alta
    
            foreach ($pdf as $i => $page) {
                $page->setImageFormat('png'); // Usa PNG per la qualità
                $page->setResolution(600,600);
                $imageData = $page->getImageBlob(); // Ottieni i dati binari dell'immagine
                $base64 = base64_encode($imageData); // Converti i dati in base64
                $previewBase64[] = 'data:image/png;base64,' . $base64;
            }
    
            $pdf->clear();
            $pdf->destroy();
        } 
        // Gestione dei file immagine
        elseif (strpos($mimeType, 'image/') === 0) {
            $image = new \Imagick($filePath);
            $image->setImageFormat('jpeg'); // Converti le immagini in JPEG per ridurre il peso
            $imageData = $image->getImageBlob(); // Ottieni i dati binari dell'immagine
            $base64 = base64_encode($imageData); // Converti i dati in base64
            $previewBase64[] = 'data:image/jpeg;base64,' . $base64;
            $image->clear();
            $image->destroy();
        }

        // Check if the preview pdf must be deleted
        if ($deletePreview) {
            unlink($filePath);
        }
    
        return $previewBase64;
    }

    function generatePreview($filePath, $fileExtension) {
        $config = \OC::$server->getConfig();
        $previewDir = $config->getSystemValue('tempdirectory', sys_get_temp_dir());
        $previewPath = $previewDir . '/' . uniqid('preview_', true) . '.pdf';
        
        switch (strtolower($fileExtension)) {
            case 'doc':
            case 'docx':
            case 'xls':
            case 'xlsx':
            case 'odt':
            case 'odp':
            case 'ppt':
            case 'pptx':
                return generateWordPreview($filePath, $previewPath);            
            default:
                return generatePreviewImages($filePath);
        }
    }

    function generateWordPreview($inputFile, $pdfFile) {     
        $conversion = [];   
        if (convertToPDF($inputFile, $pdfFile)) {
            $conversion = generatePreviewImages($pdfFile, true);
        }
        return $conversion;
    }

    function convertToPDF($inputFile, $outputDir) {
        $config = \OC::$server->getConfig();
        $previewDir = $config->getSystemValue('tempdirectory', sys_get_temp_dir());
    
        if (!file_exists($inputFile)) {
            echo "Error: input file does not exist.\n";
            return false;
        }
    
        if (!is_readable($inputFile)) {
            echo "Error: input file is not readable.\n";
            return false;
        }
    
        if (!is_writable($previewDir)) {
            echo "Error: the tmp directory is not writable.\n";
            return false;
        }
    
        // Comando di conversione
        $command = 'unoconv --verbose -f pdf -o ' . escapeshellarg($outputDir) . ' ' . escapeshellarg($inputFile);
    
        // Esegui il comando e cattura l'output e il codice di ritorno
        exec($command . ' 2>&1', $output, $returnVar);
    
        if (count($output) > 0) {
            if (strpos($output[0], 'Error') !== false) {
                echo implode("\n", $output);
                return false;
            }
            return true;
        } elseif ($returnVar !== 0) {
            // Errore durante l'esecuzione del comando
            echo "Errore durante la conversione: " . implode("\n", $output) . "\n";
            return false;
        } else {
            return true;
        }
    }

    embedfile($fileId);
} catch (Exception $ex) {
    \OC::$server->getLogger()->logException($ex, ['app' => 'index']);
    // Mostra una pagina di errore dettagliata
    OC_Template::printExceptionErrorPage($ex, 500);
    echo $ex->getMessage();
}
exit();
