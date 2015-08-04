<?php

class ProjectsWindow extends QWidget {
  private $iconpath;
  
  public function __construct() {
    parent::__construct();
    $this->iconpath = c('___pq_globals_object_')->iconpath;
    
    $openProject = 
      $this->create_button( tr('Open...'), 'open.png', 
                           '', 
                           false );
    
    $qwidgetProject = 
      $this->create_button( tr('Qt QWidget application'), 'widget.png', 
                           SLOT('qwidgetProject()') );
    
    $qmainwindowProject = 
      $this->create_button( tr('Qt QMainWindow application'), 'window.png', 
                           '', 
                           false );
    
    $consoleProject = 
      $this->create_button( tr('Console application'), 'console.png', 
                           '',
                           false );
    
    $silentProject = 
      $this->create_button( tr('Hidden application'), 'silent.png', 
                           '', 
                           false );
    
    $label1 = new QLabel($this);
    $label1->text = tr('Open project');
    $label1->styleSheet = 'font-weight:bold;';
    
    $label2 = new QLabel($this);
    $label2->text = tr('New project');
    $label2->styleSheet = 'font-weight:bold;';
    
    $hline = new QFrame($this);
    $hline->frameShape = QFrame::HLine;
    $hline->frameShadow = QFrame::Raised;
    
    $layout = new QGridLayout;
    $layout->addWidget($label1,0,0,1,2);
    $layout->addWidget($openProject,1,0,1,2);
    $layout->addWidget($hline,2,0,1,2);
    $layout->addWidget($label2,3,0,1,2);
    $layout->addWidget($qwidgetProject,4,0);
    $layout->addWidget($qmainwindowProject,4,1);
    $layout->addWidget($consoleProject,5,0);
    $layout->addWidget($silentProject,5,1);
    $this->windowFlags = Qt::Dialog | Qt::MSWindowsFixedSizeDialogHint;
    $this->setLayout($layout);
    $this->windowTitle = tr('PQCreator');
  }
  
  private function create_button($text, $icon, $slot = '', $enabled = true, $align = 'left') {
    $button = new QPushButton($this);
    $button->text = $text;
    $button->setMinimumSize(200, 40);
    $button->setIconSize(24,24);
    $button->enabled = $enabled;
    $button->icon = $this->iconpath . $icon;
    $button->styleSheet = "text-align:$align;";
    
    if(!empty($slot)) {
      $button->connect( SIGNAL('clicked()'), $this, $slot );
    }
    
    return $button;
  }
  
  public function qwidgetProject($sender) {
    $this->hide();
    $designer = new PQDesigner('QWidget');
    $designer->show();
  }
}