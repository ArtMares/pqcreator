<?php

require_once("SizeCtrl.php");

class Main extends QMainWindow {
  private $mainLayout;
  private $componentsLayout;
  private $componentsPanel;
  private $formarea;
  private $formareaLayout;
  
  private $objHash;
  
  private $formareaName = "___pq_creator__formarea_";
  
  private $lastEditedObject = null;
  private $startdragx;
  private $startdragy;
  private $sizeCtrl;
  
  private $gridSize = 8;
  
  private $componentsPath = __DIR__ . "/../components";
  
  public function __construct() {
    parent::__construct();
    
    $this->mainLayout = new QHBoxLayout;
    $this->componentsLayout = new QVBoxLayout;
    
    $centralWidget = new QWidget();
    $centralWidget->setLayout($this->mainLayout);
    
    $this->componentsPanel = new QWidget($centralWidget);
    $this->componentsPanel->minimumWidth = 200;
    $this->componentsPanel->maximumWidth = 200;
    $this->componentsPanel->setLayout($this->componentsLayout);
    
    $this->formarea = new QFrame($centralWidget);
    $this->formarea->frameShape = QFrame::StyledPanel;
    $this->formarea->objectName = $this->formareaName;
    
    $this->load_components();
    
    $this->componentsLayout->addSpacer(2000,2000,QSizePolicy::Preferred,QSizePolicy::Preferred);
    
    $this->mainLayout->addWidget($this->componentsPanel);
    $this->mainLayout->addWidget($this->formarea);
    
    $menubar = new QMenuBar($this);
    $filemenu = $menubar->addMenu(tr("File", "menubar"));
    $setsmenu = $menubar->addMenu(tr("Edit"));
    
    $openAction = $filemenu->addAction("ball.png", tr("Open"));
    connect($openAction, SIGNAL('triggered(bool)'), $this, SLOT('aaacl(bool)'));
    
    $this->setMenuBar($menubar);
    
    $this->objHash = array();
    $this->setCentralWidget($centralWidget);
    $this->resize(800,600);
    $this->windowTitle = "PQCreator";
  }
  
  public function aaacl($sender, $b) {
    echo 'OPEN!';
  }
  
  public function load_components() {
    $componentsPath = $this->componentsPath;
    if(is_dir($componentsPath)) { 
      if($dh = opendir($componentsPath)) { 
        while(($component = readdir($dh)) !== false) {
          if($component == '.' || $component == '..') continue;
          $cpath = "$componentsPath/$component";
          if(is_dir($cpath)) {
            $this->create_button($component);
          }
        } 
        closedir($dh); 
      } 
    } 
  }
  
  public function create_button($component) {
    $componentsPath = $this->componentsPath;
  
    $objectName = "create_$component";
    $buttonText = $objectName;
    $r = array();
    if(is_file("$componentsPath/$component/component.php")) {
      require_once("$componentsPath/$component/component.php");
      $buttonText = $r['title'];
    }
    
    $button = new QPushButton();
    $button->objectName = $objectName;
    $button->text = $buttonText;
    $button->styleSheet = "text-align: left";
    $button->minimumHeight = 30;
    $button->icon = "$componentsPath/$component/icon.png";
    $button->flat = true;
    $button->draggable = true;
    
    if(isset($r['defobjw']) && isset($r['defobjh'])) {
      $button->defobjw = $r['defobjw'];
      $button->defobjh = $r['defobjh'];
    }
      
    
    $this->componentsLayout->addWidget($button);
    connect($button, SIGNAL("mousePressed(int,int,int)"), $this, SLOT("create_object(int,int,int)"));
    connect($button, SIGNAL("mouseMoved(int,int)"), $this, SLOT("move_object(int,int)"));
    connect($button, SIGNAL('mouseReleased(int,int,int)'), $this, SLOT('test_create(int,int,int)'));
    $button->show();
  }
  
  public function test_create($sender, $x, $y, $button) {
    $wx = $x - $this->geometry()["x"];
    $wy = $y - $this->geometry()["y"];
    $widget = $this->centralWidget->widgetAt($wx, $wy);
    
    $obj = $this->lastEditedObject;
    
    if($widget != NULL) {
      if($widget->objectName == $this->formareaName) {
        $fax = $wx - $this->centralWidget->geometry()["x"] - $this->formarea->x;
        $fay = $wy - $this->centralWidget->geometry()["y"] - $this->formarea->y;
        
        $obj->windowOpacity = 1;
        $obj->setParent($this->formarea);
        
        $newx = floor( $fax / $this->gridSize ) * $this->gridSize;
        $newy = floor( $fay / $this->gridSize ) * $this->gridSize;
        
        $obj->move($newx, $newy);
        $obj->isDynObject = true;
        $obj->show();
        $this->select_object($obj);
        
        return;
      }
    }
    
    $this->lastEditedObject = null;
    $this->delete_object($obj);
  }
  
  public function create_object($sender, $x, $y, $button) {
    $type = explode("_", $sender->objectName)[1];
    
    $index = 0;
    $objectName = "$type";
    if(isset($this->objHash[$objectName])) {
      $index = 1;
      while(isset($this->objHash["${objectName}_$index"])) {
        $index++;
      }
      $objectName = "${objectName}_$index";
    }
    
    $this->startdragx = 0;
    $this->startdragy = 0;
    
    $this->unselect_object();
    
    $obj = new $type();
    $obj->objectName = $objectName;
    $obj->setWindowFlags(Qt::Tool|Qt::WindowStaysOnTopHint|Qt::FramelessWindowHint);
    $obj->setAttribute(Qt::WA_TranslucentBackground);
    $obj->text = $objectName;
    
    $obj->move($x, $y);
    $obj->windowOpacity = 0.5;
    $obj->lockParentClassEvents(true);
    
    if($sender->defobjw !== null &&
        $sender->defobjh !== null) {
      $obj->resize($sender->defobjw,$sender->defobjh);
    }
    
    $obj->connect(SIGNAL('mousePressed(int,int,int)'), $this, SLOT('start_drag(int,int,int)'));
    $obj->connect(SIGNAL('doubleClicked(int,int,int)'), $this, SLOT('unselect_object(int,int,int)'));
    $obj->connect(SIGNAL('mouseReleased(int,int,int)'), $this, SLOT('stop_drag(int,int,int)'));
    $obj->connect(SIGNAL('mouseMoved(int,int)'), $this, SLOT('move_object(int,int)'));
    
    $obj->show();
    
    $this->lastEditedObject = $obj;
    $this->objHash[$objectName] = $index;
  }
  
  public function unselect_object($sender=0,$x=0,$y=0,$btn=0) {
    if($this->sizeCtrl != null
        && is_object($this->sizeCtrl))
      $this->sizeCtrl->__destruct();
    $this->sizeCtrl = null;
  }
  
  public function select_object($object) {
    $this->unselect_object();
    $this->sizeCtrl = new SizeCtrl($this->formarea, $object);
  }
  
  public function stop_drag($sender, $x, $y, $button) {
    $sender->draggable = false;
    $this->sizeCtrl = new SizeCtrl($this->formarea, $sender);
  }
  
  public function start_drag($sender, $x, $y, $button) {
    $this->unselect_object();
    $sender->draggable = true;
    $dx = $x - ($this->geometry()["x"] + $this->formarea->x + $sender->x);
    $dy = $y - ($this->geometry()["y"] + $this->formarea->y + $sender->y);
    $this->startdragx = $this->geometry()["x"] + $this->formarea->x + $dx;
    $this->startdragy = $this->geometry()["y"] + $this->formarea->y + $dy;
    $this->lastEditedObject = &$sender;
  }
  
  public function move_object($sender, $x, $y) {
    if(!empty($this->lastEditedObject)
        && $this->lastEditedObject != null) {
      if($sender->draggable) {
        $newx = $x - $this->startdragx;
        $newy = $y - $this->startdragy;
        if($sender->isDynObject === true) {
          $newx = floor( $newx / $this->gridSize ) * $this->gridSize;
          $newy = floor( $newy / $this->gridSize ) * $this->gridSize;
        }
        $this->lastEditedObject->move($newx, $newy);
      }
    }
  }
  
  public function delete_object($obj) {
    unset($this->objHash[$obj->objectName]);
    $obj->free();
  }
}

$main = new Main;
$main->show();