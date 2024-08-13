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

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */
	public function pdfpreview(): DataResponse {
		$currentDir = realpath('.');
		include($currentDir . '/config/config.php');
		$filename   = urldecode($_REQUEST['filename']);
		$path       = urldecode($_REQUEST['path']);
		$user       = urldecode($_REQUEST['user']);

		try{
			if(isset($filename) && isset($path) && isset($user)) {
					//Sostituisco tutte le / con DIRECTORY_SEPARATOR
					$filepath = str_replace('/', DIRECTORY_SEPARATOR, $CONFIG['datadirectory'].'/'.$user.'/files'.$path.'/'.$filename);
					if(file_exists($filepath)) {
							//Imagick converrt pdf to jpg and output to browser
							$imagick = new \Imagick();
							$imagick->setResolution(300, 300);
							$imagick->readImage($filepath.'[0]');
							$imagick->setImageFormat('jpg');

							//Imagick set image size as 330x390
							$imagick->thumbnailImage(330, 390, true, false);

							// header('Content-Type: image/jpeg');
							// echo $imagick->getImageBlob();
							// $imagick->clear();
							// $imagick->destroy();

							return new DataResponse(['preview' => 'data:image/jpg;base64,'.base64_encode($imagick->getImageBlob())]);
					}else
							return new DataResponse(['error' => 'File not found.', 'path' => $filepath]);
			}else
					return new DataResponse(['error' => 'Missing parameters']);
		}catch(\Exception $e) {
				return new DataResponse(['error' => $e->getMessage()]);
		}
	}

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return DataResponse
     */
    public function embedfile() {
        $fileId = $_REQUEST['fileId'];
        $file = $this->rootFolder->getById($fileId);

        if (empty($file)) {
            return new RedirectResponse($this->urlGenerator->linkToRoute('core.PageNotFound'));
        }

        $filePath = \OC::$server->getConfig()->getSystemValue('datadirectory').$file[0]->getPath();
        $fileName = $file[0]->getName();
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        

        // Genera le immagini di anteprima
        $previewPaths = $this->generatePreview($filePath, $fileExtension);

        if ($previewPaths && count($previewPaths) > 0) {   

            // Crea l'HTML per visualizzare le immagini di anteprima
            $html = '<html><body>';
            foreach ($previewPaths as $path) {
                $html .= '<img src="' . $path . '" style="width:100%; margin-bottom:10px;" />';
            }
            $html .= '</body></html>';
            echo $html;
            //return new DataResponse($html, 200, ['Content-Type' => 'text/html']);

            // Usa TemplateResponse per il rendering HTML
            //return new TemplateResponse('nextembed', 'preview', ['previews' => $previewPaths]);
        } else {
            $url = $this->urlGenerator->linkToRoute('files.view.index', ['dir' => dirname($filePath), 'openfile' => $fileId]);
            //return new RedirectResponse($url);
        }
    }

    /**
     * Genera immagini di anteprima per ciascuna pagina del documento
     *
     * @param string $filePath
     * @param string $fileExtension
     * @return array
     */
    private function generatePreviewImages($filePath, $deletePreview = false) {
        $previewBase64 = [];
        
        // Usa finfo per ottenere il tipo MIME del file
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);
    
        // Gestione dei file PDF
        if ($mimeType === 'application/pdf') {
            $pdf = new \Imagick($filePath);
            $pdf->setResolution(600, 600); // Imposta una risoluzione più alta
    
            foreach ($pdf as $i => $page) {
                $page->setImageFormat('png'); // Usa JPEG per ridurre il peso
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
        // Aggiungi logica per altri tipi di file se necessario

        //I check if the preview pdf must be deleted
        if($deletePreview) {
            unlink($filePath);
        }
    
        return $previewBase64;
    }
    


    /**
     * @PublicPage
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return DataResponse
     */
    private function generatePreview($filePath, $fileExtension) {
        // $previewDir = \OC::$server->getTempManager()->getTemporaryFolder();
        // Ottieni la configurazione di Nextcloud
        $config = \OC::$server->getConfig();
        // Ottieni la cartella temporanea configurata
        $previewDir = $config->getSystemValue('tempdirectory', sys_get_temp_dir());
        $previewPath = $previewDir.'/'.uniqid('previewtodelete_', true) . '.pdf';
        
        switch (strtolower($fileExtension)) {
            case 'doc':
            case 'docx':
            case 'xls':
            case 'xlsx':
            case 'odt':
            case 'odp':
            case 'ppt':
            case 'pptx':
                return $this->generateWordPreview($filePath, $previewPath);            
            // return $this->generateExcelPreview($filePath, $previewPath);
            // Aggiungi altri formati di file come necessario
            default:
                return $this->generatePreviewImages($filePath);
        }
    }

    private function generateWordPreview($inputFile, $pdfFile) {     
        $conversion = [];   
        if($this->convertToPDF($inputFile, $pdfFile))
            $conversion = $this->generatePreviewImages($pdfFile, true);
        // unlink($pdfFile);
        return $conversion;
    }

    // private function generateExcelPreview($inputFile, $pdfFile) {
    //     $conversion = [];
    //     if($this->convertToPDF($inputFile, $pdfFile))
    //         $conversion = $this->generatePreviewImages($pdfFile, true);
    //     // unlink($pdfFile);
    //     return $conversion;
    // }

    private function convertToPDF($inputFile, $outputDir) {
        $config = \OC::$server->getConfig();
        // Ottieni la cartella temporanea configurata
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
    
        if(count($output) > 0) {
            if(strpos($output[0], 'Error') !== false) {
                echo implode("\n", $output);
                return false;
            }
            return true;
        }elseif ($returnVar !== 0) {
            // Errore durante l'esecuzione del comando
            echo "Errore during the conversion: " . implode("\n", $output) . "\n";
            return false;
        }else
            return true;
    }
   
    
    private function generatePreviewFromPDF($pdfFile, $outputImage) {
        $imagick = new Imagick();
        $imagick->readImage($pdfFile . '[0]'); // Legge la prima pagina
        $imagick->setImageFormat('jpg');
        $imagick->writeImage($outputImage);
    }
}

