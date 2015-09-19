<?php

class PQDownloader extends QDialog {
    private $fileDownloader;
    private $progressBar;
    private $textEdit_static_text;
    private $downloader;
    private $textEdit;
    private $button;
    private $tempFilePath;
    private $destinationDir;
    
    public function __construct() {
        parent::__construct();
        
        $this->textEdit_static_text = tr('Downloading PQPack package');
        $this->setWindowFlags( Qt::MSWindowsFixedSizeDialogHint
                                |Qt::WindowStaysOnTopHint
                                |Qt::WindowTitleHint );
        
        $this->downloader = new PQFileDownloader();
        $this->downloader->connect( SIGNAL('downloaded()'), $this, SLOT('downloaded()') );
        $this->downloader->connect( SIGNAL('downloadProgress(qint64,qint64)'), $this, SLOT('downloadProgress(qint64,qint64)') );
        
        $this->textEdit = new QTextEdit($this);
        $this->textEdit->html = $this->textEdit_static_text . '... (0Kb of 0Kb)';
        $this->textEdit->readOnly = true;
        $this->textEdit->minimumHeight = $this->textEdit->maximumHeight = 80;
        
        $this->progressBar = new QProgressBar($this);
        $this->progressBar->setMinimumSize(450, 30);
        
        $this->button = new QPushButton($this);
        $this->button->text = tr('Continue');
        $this->button->enabled = false;
        $this->button->connect( SIGNAL('clicked()'), $this, SLOT('close()') );
        $this->button->minimumHeight = 30;
        
        $layout = new QVBoxLayout();
        $layout->addWidget($this->textEdit);
        $layout->addWidget($this->progressBar);
        $layout->addWidget($this->button);
        
        $this->setLayout($layout);
    }
    
    public function download($url, $tempFilePath, $destinationDir) {
        $this->tempFilePath = $tempFilePath;
        $this->destinationDir = $destinationDir;
        $this->downloader->download($url);
        $this->exec();
    }
    
    public function downloaded() {
        $messagebox = new QMessageBox;
        
        if($this->progressBar->value != $this->progressBar->maximum) {
            $this->hide();
            $messagebox->critical(0, tr('PQCreator error'), 
                                    tr('Error download PQPack! :-( Please, try later...'),
                                    tr('Quit'));
            
            qApp::quit();
        }
    
        $this->textEdit->html .= "\n" . tr('Writing in file...');
        file_put_contents($this->tempFilePath, $this->downloader->downloadedData());
        
        $this->textEdit->html .= "\n" . sprintf( tr('Unzip data in `%s`'), $this->destinationDir );
        $zip = new ZipArchive;
        if ($zip->open($this->tempFilePath) === TRUE) {
            
            if(!mkdir($this->destinationDir)) {
                
                $this->hide();
                $messagebox->critical(0, tr('PQCreator error'), 
                                        sprintf( tr('Error creating directory `%s`!'), $this->destinationDir ),
                                        tr('Quit'));
                
                $zip->close();
                qApp::quit();
            }
            
            $zip->extractTo($this->destinationDir);
            $zip->close();
            
            $this->textEdit->html .= "\n<b>" . tr('Done!') . '</b>';
            $this->button->enabled = true;
            
            $messagebox->free();
            unset($messagebox);
            
            qApp::beep();
            
            return;
        } 
        
        $this->hide();
        $messagebox->critical(0, tr('PQCreator error'), 
                                sprintf( tr('Error unzip PQPack package `%s`!'), $this->tempFilePath ),
                                tr('Quit'));
                                
        qApp::quit();
    }
    
    public function downloadProgress($sender, $received, $total) {
        $this->progressBar->value = $received;
        $this->progressBar->maximum = $total;
        
        $rs = 'Kb';
        $ts = 'Kb';
        
        $r = round($received / 1024, 2);
        $t = round($total / 1024, 2);
        
        if($r > 1024) { $r = round($r / 1024, 2); $rs = 'Mb'; }
        if($t > 1024) { $t = round($t / 1024, 2); $ts = 'Mb'; }
        
        $this->textEdit->html = $this->textEdit_static_text . "... (${r}${rs} / ${t}${ts})";
    }
}