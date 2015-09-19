<?php

require_once ("PQPlainTextEdit.php");

class PQSourceTextEditCloseDialog extends QDialog {
    public function __construct($text, $parent = 0) 
    {
        parent::__construct($parent);
        
        $dialogLabel = new QLabel($this);
        $dialogLabel->text = $text;
        
        $yesBtn = new QPushButton($this);
        $yesBtn->text = tr('Yes');
        $yesBtn->onClicked = function($sender, $event) {
            $this->done(1);
        };
        
        $noBtn = new QPushButton($this);
        $noBtn->text = tr('No');
        $noBtn->onClicked = function($sender, $event) {
            $this->done(0);
        };
        
        $dialogLayout = new QGridLayout;
        $dialogLayout->addWidget($dialogLabel, 0, 0, 1, 2);
        $dialogLayout->addWidget($yesBtn, 1, 0);
        $dialogLayout->addWidget($noBtn, 1, 1);
        
        $this->setLayout($dialogLayout);
    }
}

class PQSourceTextEdit extends QDialog {
    public $textEdit;
    public $headerLabel1;
    
    public $__pq_objectName_;
    public $__pq_eventName_;
    public $__pq_eventArgs_;
    
    public function __construct($parent = 0) 
    {
        parent::__construct($parent);
        
        $this->setWindowFlags(Qt::Window);
        
        $this->textEdit = new PQPlainTextEdit($this);
        $this->headerLabel1 = new QLabel($this);
        
        $this->setPHPEventListener($this, eventListener);
        $this->addPHPEventListenerType(QEvent::Close);
        
        $buttonsPanel = new QWidget($this);
        
        $cancelBtn = new QPushButton($buttonsPanel);
        $cancelBtn->text = tr('Cancel');
        $cancelBtn->onClicked = function($sender, $event) {
            $this->close();
        };
        
        $okBtn = new QPushButton($buttonsPanel);
        $okBtn->text = tr('OK');
        $okBtn->onClicked = function($sender, $event) {
            $this->done(1);
        };
        
        $buttonsPanelLayout = new QHBoxLayout;
        $buttonsPanelLayout->addWidget(new QWidget($buttonsPanel)); // распорка
        $buttonsPanelLayout->addWidget($cancelBtn);
        $buttonsPanelLayout->addWidget($okBtn);
        $buttonsPanelLayout->setMargin(0);
        $buttonsPanel->setLayout($buttonsPanelLayout);
        
        $layout = new QVBoxLayout;
        $layout->addWidget($this->headerLabel1);
        $layout->addWidget($this->textEdit);
        $layout->addWidget($buttonsPanel);
        
        $this->setLayout($layout);
    }
    
    public function exec() {
        $this->textEdit->setFocus();
        return parent::exec();
    }
    
    public function eventListener($sender, $event) {
        switch($event->type) {
        case QEvent::Close:
            $dialog = new PQSourceTextEditCloseDialog(tr('Do you want to close the source code editor without save?'),
                                                        $this);
            
            $result = $dialog->exec();
            
            if($result === 1) {
                $this->done(0);
                return false;
            }
            else {
                $event->ignore();
                return true;
            }
            
            break;
        }
    }
}





























