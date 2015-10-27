<?php

function ___pq_prepare_path_to_win($path) {
  return str_replace('/', '\\', $path); // windows path
}

function ___pq_prepare_path($winpath) {
  return str_replace('\\', '/', $winpath); // normal path
}

DEFINE('PQNAME', '___pq_creator_');

set_tr_lang('ru');

require_once('core/design/PQDesigner.php');
require_once('core/design/PQDownloader.php');
require_once('core/design/ProjectsWindow.php');
require_once('core/logo/PQCreatorLogo.php');
require_once('core/pqpack/PQBuilder.php');

class PQCreator extends QObject {
    private $projects;
    private $downloader;
    
    public function __construct() {
        parent::__construct();
        $this->objectName = PQNAME;
        
        $this->currentPath = QDir::CurrentPath;
        
        $this->iconsPath = $this->currentPath . '/core/design/faenza-icons';
        $this->qticonsPath = $this->currentPath . '/core/design/qt-icons';
        //$this->pqpackPath = ___pq_prepare_path(getenv("PROGRAMFILES")) . '/PQPack';
        $this->pqpackPath = $this->currentPath . '/core/pqpack';
        $this->defaultBuildPath = explode(':',$this->currentPath)[0] . '/pqprojects';
        
        // Components
        $this->csPath = $this->currentPath . '/core/components';
        $this->csName = 'component.php';
        $this->csProperties = 'properties.php';
        $this->csEvents = 'events.php';
        $this->csIcon = 'icon.png';
        
        $this->init();
    }
    
    private function init() {
        $this->projects = new ProjectsWindow;
    }
    
    private function getPQPackDialog() {
        $messagebox = new QMessageBox;
        $answer = (int) $messagebox->question(0, tr('Package not found'), 
                                                sprintf( tr("PQPack package not found in `%s` directory.\r\n".
                                                "Without PQPack package you can't compile applications.\r\n\r\n".
                                                "Download and install it?"), $this->pqpackPath ), 
                                                tr('Yes'), 
                                                tr('No (Quit)') );
                                                
        if($answer == 1) {
            qApp::quit();
        }
        else {
            $this->downloadPQPack();
        }
        
        if(!file_exists($this->pqpackPath)) {
            qApp::quit();
        }
    }
    
    private function checkPQPack() {
        if(!file_exists($this->pqpackPath)) {
            $this->getPQPackDialog();
        }
        else if(!is_dir($this->pqpackPath)) {
            $messagebox = new QMessageBox;
            $messagebox->critical(0, tr('PQCreator error'), 
                                    sprintf( tr('Cannot access to PQPack directory `%s`'), $this->pqpackPath), 
                                    tr('Quit'));
        }
    }
    
    private function downloadPQPack() {
        $this->downloader = new PQDownloader;
        
        $tempFile_c = QDir::TempPath . '/pqpack.';
        $tempFilePath = $tempFile_c . rand(1,9999) . '.pqdownload';
        
        while(file_exists($tempFilePath)) {
            $tempFilePath = $tempFile_c . rand(1,9999) . '.pqdownload';
        }
        
        $this->downloader->download( 'http://phpqt.ru/downloads/pqpack/pqpack-0.1-rc1.zip', $tempFilePath, $this->pqpackPath );
    }
    
    public function run() {
        //$this->checkPQPack();
        
        //$logo = new PQCreatorLogo("0.1-alpha1\n(build: 1)");
        //$logo->show();
        $this->projects->show();
    }
}

$pqcreator = new PQCreator;
$pqcreator->run();
qApp::exec();


