<?php

class PQTabWidget extends QWidget {
  private $stack;
  private $tabbar;
  private $layout;
  
  public function __construct($parent = 0) {
    if($parent == 0) parent::__construct();
    else parent::__construct($parent);
    
    $this->layout = new QVBoxLayout;
    $this->layout->spacing = 0;
    
    $this->stack = new QStackedWidget($this);
    $this->stack->lineWidth = 0;
    $this->stack->resize(200,200);
    $this->stack->setSizePolicy(QSizePolicy::Preferred, QSizePolicy::Preferred, QSizePolicy::TabWidget);
    $this->stack->styleSheet = 'QStackedWidget{background-color: white;margin-top:-2px;}';
    $this->stack->setFrameShape(QFrame::StyledPanel);
    $this->stack->objectName = '___pq_creator__pqtabwidget_stack_';
    
    $this->tabbar = new QTabBar($this);
    $this->tabbar->expanding = false;
    
    $this->setLayout($this->layout);
    $this->setSizePolicy(QSizePolicy::Expanding, QSizePolicy::Expanding, QSizePolicy::TabWidget);
    
    $this->layout->addWidget($this->tabbar);
    $this->layout->addWidget($this->stack);
  }
  
  public function addTab($widget, $text) {
    $this->tabbar->addTab($text);
    $this->stack->addWidget($widget);
    $widget->setParent($this->stack);
    $parent = $widget->parent;
  }
}