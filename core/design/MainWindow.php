<?php

require_once("SizeCtrl.php");

class Main extends QMainWindow {
  private $mainLayout;
  private $componentsLayout;
  private $componentsPanel;
  private $componentsDock;
  private $formarea;
  private $formareaLayout;
  private $propertiesLayout;
  private $propertiesPanel;
  private $propertiesDock;
  
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
    
    $centralWidget = new QWidget;
    $centralWidget->setLayout($this->mainLayout);
    
    $this->componentsPanel = new QWidget($centralWidget);
    $this->componentsPanel->width = 180;
    $this->componentsPanel->minimumWidth = 180;
    $this->componentsPanel->setLayout($this->componentsLayout);
    
    $componentsDock = new QDockWidget($this);
    $componentsDock->setAllowedAreas(Qt::LeftDockWidgetArea | Qt::RightDockWidgetArea);
    $componentsDock->setWidget($this->componentsPanel);
    $this->addDockWidget(Qt::LeftDockWidgetArea, $componentsDock);
    
    $this->formarea = new QFrame($centralWidget);
    $this->formarea->frameShape = QFrame::StyledPanel;
    $this->formarea->objectName = $this->formareaName;
    
    $this->load_components();
    
    $this->componentsLayout->addSpacer(2000,2000,QSizePolicy::Preferred,QSizePolicy::Preferred);
    
    //$this->mainLayout->addWidget($this->componentsPanel);
    $this->mainLayout->addWidget($this->formarea);
    $this->create_propertiesPanel();
    
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
  
  public function create_propertiesPanel() {
    $this->propertiesLayout = new QVBoxLayout;
    $this->propertiesPanel = new QWidget;
    $this->propertiesPanel->minimumWidth = 180;
    $this->propertiesPanel->width = 180;
    $this->propertiesPanel->setLayout($this->propertiesLayout);
    //$this->mainLayout->addWidget($this->propertiesPanel);
    
    $this->propertiesDock = new QDockWidget($this);
    $this->propertiesDock->setAllowedAreas(Qt::LeftDockWidgetArea | Qt::RightDockWidgetArea);
    $this->propertiesDock->setWidget($this->propertiesPanel);
    $this->addDockWidget(Qt::RightDockWidgetArea, $this->propertiesDock);
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
      require("$componentsPath/$component/component.php");
    }
    
    if(!isset($r['group'])
        || $r['group'] == 'NoVisual') {
      return;
    }
    
    if(isset($r['parent'])
        && !empty(trim($r['parent']))) {
      $parentClass = $r['parent'];
    } else {
      $parentClass = null;
    }
    
    $buttonText = $r['title'];
    
    $button = new QPushButton();
    $button->objectName = $objectName;
    $button->text = $buttonText;
    $button->styleSheet = "text-align: left";
    $button->minimumHeight = 30;
    $button->icon = "$componentsPath/$component/icon.png";
    $button->flat = true;
    $button->draggable = true;
    $button->parentClass = $parentClass;
    
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
    $objectName = strtolower($type);
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
    $obj->parentClass = $sender->parentClass;
    
    $obj->move($x, $y);
    $obj->windowOpacity = 0.5;
    $obj->lockParentClassEvents(true);
    $obj->defaultPropertiesLoaded = false;
    
    if($sender->defobjw !== null &&
        $sender->defobjh !== null) {
      $obj->resize($sender->defobjw, $sender->defobjh);
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
    $this->sizeCtrl = new SizeCtrl($this->formarea, $object, $this->gridSize);
    $this->load_object_properties($object);
  }
  
  public function stop_drag($sender, $x, $y, $button) {
    $sender->draggable = false;
    $this->select_object($sender);
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
  
  public function load_object_properties($object) {
    $component = get_class($object);
    $this->propertiesDock->free();
    $this->create_propertiesPanel();
    
    // Загружаем все свойства в массив
    $properties = array();
    while($component != null) {
      $componentPath = $this->componentsPath . "/$component/component.php";
      $propertiesPath = $this->componentsPath . "/$component/properties.php";
      
      $r = array();
      if(file_exists($propertiesPath)
          && is_file($propertiesPath)) {
        require($propertiesPath);
        
        if(count($r) > 0) {
          $properties[$component] = $r;
        }
      }
      
      $component = null;
      require($componentPath);
      if(isset($r['parent'])
          && !empty(trim($r['parent']))) {
        $component = $r['parent'];
      }
    }
    
    // Отображаем все свойства на панели
    foreach($properties as $c => $p) {
      $label = new QLabel($this->propertiesPanel);
      $label->text = $c;
      
      $table = new QTableWidget($this->propertiesPanel);
      $table->addColumns(2);
      $table->setHorizontalHeaderText(0, tr('Property'));
      $table->setHorizontalHeaderText(1, tr('Value'));
      $table->verticalHeaderVisible = false;
    
      foreach($p as $property) {
        $row = $table->rowCount();
        $table->addRow();
        $table->setTextAt($row, 0, $property['title']);
        $widget = null;
        
        switch($property['type']) {
        case 'mixed':
          $widget = new QLineEdit;
          
          if(isset($property['value'])
              && !$object->defaultPropertiesLoaded) {
            $widget->text = $property['value'];
          } else {
            $widget->text = $object->$property['property'];
          }
          
          if(isset($property['validator'])) {
            $widget->setRegExpValidator($property['validator']);
          }
          
          break;
          
        case 'bool':
          $widget = new QCheckBox;
          $widget->__pq_objectName_ = $object->objectName;
          $widget->__pq_property_ = $property['property'];
          
          if(isset($property['value'])
              && !$object->defaultPropertiesLoaded) {
            $widget->checked = $property['value'];
          } else {
            $widget->checked = $object->$property['property'];
          }
          
          $widget->connect(SIGNAL('toggled(bool)'), $this, SLOT('set_object_property(bool)'));
          break;
        }
        
        if($widget != null) {
          $table->setCellWidget($row, 1, $widget);
        }
      }
      
      $this->propertiesLayout->addWidget($label);
      $this->propertiesLayout->addWidget($table);
    }
    
    $object->defaultPropertiesLoaded = true;
  }
  
  public function set_object_property($sender, $value) {
    $objectName = $sender->__pq_objectName_;
    $property = $sender->__pq_property_;
    c($objectName)->$property = $value;
  }
}

$main = new Main;
$main->show();