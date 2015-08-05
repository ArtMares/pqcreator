<?php

class ProjectsWindow extends QWidget {
  private $iconpath;
  private $newProjectFD;
  private $newProjectFD_lineEdit;
  private $user_projects_path;
  private $newProjectName_lineEdit;
  private $project_dir;
  
  public function __construct() {
    parent::__construct();
    $this->iconpath = c('___pq_globals_object_')->iconpath;
    
    $openProject = 
      $this->create_button( tr('Open project'), 'open.png', 
                           '', 
                           false );
    
    $qwidgetProject = 
      $this->create_button( tr('Qt QWidget application'), 'widget.png', 
                           SLOT('new_QWidget_project()') );
    
    $qmainwindowProject = 
      $this->create_button( tr('Qt QMainWindow application'), 'window.png', 
                           SLOT('new_QMainWindow_project()') );
    
    $consoleProject = 
      $this->create_button( tr('Console application'), 'console.png', 
                           '',
                           false );
    
    $silentProject = 
      $this->create_button( tr('Hidden application'), 'hidden.png', 
                           '', 
                           false );
    
    //$label1 = new QLabel($this);
    //$label1->text = tr('Open project');
    
    $label2 = new QLabel($this);
    $label2->text = tr('New project');
    $label2->styleSheet = 'font-weight:bold;font-size:12px;margin-bottom:4px;margin-left:-2px;';
    
    $hline = new QFrame($this);
    $hline->frameShape = QFrame::HLine;
    $hline->frameShadow = QFrame::Raised;
    
    $newProject = new QWidget($this);
    
    // NewProject name
    $newProjectName_label = new QLabel($newProject);
    $newProjectName_label->text = tr('Project name:');
    $newProjectName_label->styleSheet = 'margin-left:1px;';
    $newProjectName_lineEdit = new QLineEdit($newProject);
    $newProjectName_lineEdit->text = $this->get_def_project_name();
    $newProjectName_lineEdit->setRegExpValidator('[a-zA-Z0-9\-\_]*');
    $this->newProjectName_lineEdit = $newProjectName_lineEdit;
    
    // NewProject FileDialog
    $newProjectFD_label = new QLabel($newProject);
    $newProjectFD_label->text = tr('Create in:');
    $newProjectFD_label->styleSheet = 'margin-left:1px;';
    $newProjectFD_lineEdit = new QLineEdit($newProject);
    $newProjectFD_lineEdit->readOnly = true;
    $newProjectFD_lineEdit->text = $this->get_def_dir();
    $newProjectFD_button = new QPushButton($newProject);
    $newProjectFD_button->text = tr('View...');
    $newProjectFD_button->connect( SIGNAL('clicked()'), $this, SLOT('open_newProjectFD()') );
     
    // NewProject layout
    $newProject_layout = new QGridLayout;
    $newProject_layout->setMargin(0);
    $newProject_layout->addWidget($newProjectName_label, 0, 0);
    $newProject_layout->addWidget($newProjectName_lineEdit, 0, 1, 1, 2);
    $newProject_layout->addWidget(new QWidget($newProject), 1, 1, 1, 3); // Empty widget uses as small spacer
    $newProject_layout->addWidget($newProjectFD_label, 2, 0);
    $newProject_layout->addWidget($newProjectFD_lineEdit, 2, 1);
    $newProject_layout->addWidget($newProjectFD_button, 2, 2);
    $newProject->setLayout($newProject_layout);
    
    $this->newProjectFD = new QFileDialog;
    $this->newProjectFD_lineEdit = $newProjectFD_lineEdit;
    
    $emptyw0 = new QWidget($this);
    $emptyw0->minimumHeight = 8;
    $emptyw0->maximumHeight = 8;
    
    $layout = new QGridLayout;
    $layout->addWidget($openProject,1,0,1,2);
    $layout->addWidget($hline,2,0,1,2);
    $layout->addWidget($label2,3,0,1,2);
    $layout->addWidget($newProject,4,0,1,2);
    $layout->addWidget($emptyw0,5,0,1,2);
    $layout->addWidget($qwidgetProject,6,0);
    $layout->addWidget($qmainwindowProject,6,1);
    $layout->addWidget($consoleProject,7,0);
    $layout->addWidget($silentProject,7,1);
    $this->minimumWidth = 500;
    $this->windowFlags = Qt::Dialog | Qt::MSWindowsFixedSizeDialogHint;
    $this->setLayout($layout);
    $this->windowTitle = 'PQCreator';
  }
  
  public function open_newProjectFD($sender) {
    if(!$projects_dir = $this->get_def_dir()) {
      return;
    }
    
    $user_projects_path = $this->newProjectFD->getExistingDirectory(0, tr('Select directory'), $projects_dir);
    if(!empty(trim($user_projects_path))) {
      $this->user_projects_path = ___pq_prepare_path_to_win($user_projects_path);
      $this->newProjectFD_lineEdit->text = $this->user_projects_path;
    }
  }
  
  private function get_def_project_name() {
    $c_def_project_name = 'untitled';
    $def_project_name = $c_def_project_name;
    
    for($index = 1; file_exists($this->get_def_dir() . "/$def_project_name"); $index++) {
      $def_project_name = $c_def_project_name.$index;
    }
    
    return $def_project_name;
  }
  
  private function create_project_dir() {
    $project_dir = $this->user_projects_path . '/' . $this->newProjectName_lineEdit->text;
    
    if(file_exists($project_dir)
        || !mkdir($project_dir)) {
      $messageBox = new QMessageBox;
      $messageBox->critical(0, tr('Error creating directory'), tr('Cannot create directory') . " `$project_dir`",  tr('Ok'));
      $messageBox->free();
      return false;
    }
    
    $this->project_dir = ___pq_prepare_path($project_dir);
    return true;
  }
  
  private function get_def_dir() {
    if(empty(trim($this->user_projects_path))) {
      // TODO: replace
      // $c_def_dir = explode(':',  __DIR__)[0] . ':/PQProjects_tmp'; // sets the value of $c_def_dir to [C|D|E|...]:/PQProjects_tmp
      $c_def_dir = c('___pq_globals_object_')->exepath . '/PQProjects_tmp';
      $def_dir = $c_def_dir;
      
      // Check that the PATH is not a file
      for($index = 1; file_exists($def_dir) && is_file($def_dir); $index++) {
        $def_dir = $c_def_dir.$index;
      }
      
      // Create a directory if not exists
      if(!file_exists($def_dir) && !mkdir($def_dir)) {
        $messageBox = new QMessageBox;
        $messageBox->critical(0, tr('Error creating directory'), tr('Cannot create directory') . " `$def_dir`",  tr('Ok'));
        $messageBox->free();
        return false;
      }
      
      $this->user_projects_path = ___pq_prepare_path_to_win($def_dir);
    }
    
    return $this->user_projects_path;
  }
  
  private function create_button($text, $icon, $slot = '', $enabled = true, $align = 'left') {
    $widget = new QWidget($this);
    
    $button = new QPushButton($widget);
    $button->text = $text;
    $button->setMinimumSize(200, 36);
    $button->enabled = $enabled;
    $button->styleSheet = "text-align:$align;padding-left:50px;padding-right:20px;font-size:14px;";
    
    $label = new QLabel($widget);
    $label->icon = $this->iconpath . $icon;
    $label->setIconSize(32, 32);
    $label->resize(32, 32);
    $label->enabled = $enabled;
    $label->move(10, 0);
    
    if(!empty($slot)) {
      $button->connect( SIGNAL('clicked()'), $this, $slot );
      $label->connect( SIGNAL('clicked()'), $button, SLOT('click()') );
    }
    
    
    $layout = new QHBoxLayout;
    $layout->setContentsMargins(0, 6, 0, 0);
    $layout->addWidget($button);
    $widget->setLayout($layout);
    
    return $widget;
  }
  
  public function new_QWidget_project($sender) {
    if($this->create_project_dir()) {
      $this->hide();
      $designer = new PQDesigner('QWidget', $this->project_dir);
      $designer->show();
    }
  }
  
  public function new_QMainWindow_project($sender) {
    if($this->create_project_dir()) {
      $this->hide();
      $designer = new PQDesigner('QMainWindow', $this->project_dir);
      $designer->show();
    }
  }
}