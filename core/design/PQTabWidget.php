<?php

class PQFormWidget extends QFrame {
  private $sizeCtrl;
  public $centralWidget;
  
  public function __construct($parent = 0) {
    if($parent == 0) parent::__construct();
    else parent::__construct($parent);
    
    $this->objectName = '___pq_formwidget_';
    $this->styleSheet = '#___pq_formwidget_ { border: 5px solid #aaa; }';
    
    $this->centralWidget = new QFrame($this);
    $this->centralWidget->objectName = '___pq_formwidget__centralwidget_';
    $this->centralWidget->styleSheet = '#___pq_formwidget__centralwidget_ { background:#fff; }';
    
    $layout = new QHBoxLayout;
    $layout->setMargin(0);
    $layout->addWidget($this->centralWidget);
    
    $this->setLayout($layout);
    
    connect($this, SIGNAL('widgetEvent(int)'), $this, SLOT('eventListener(int)'));
  }
  
  public function update_code() {}
  
  public function eventListener($sender, $event) {
    switch($event) {
      case QEvent::Resize:
        $this->parent->setMinimumSize( $this->width, $this->height );
      break;
    case QEvent::Show:
      $this->sizeCtrl = new PQSizeCtrl($this, $this->parent, $this, 8);
      $this->sizeCtrl->lt->hide();
      $this->sizeCtrl->lm->hide();
      $this->sizeCtrl->lb->hide();
      $this->sizeCtrl->rt->hide();
      $this->sizeCtrl->tm->hide();
      break;
    }
  }
}

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
    $this->stack->setSizePolicy(QSizePolicy::Preferred, QSizePolicy::Preferred, QSizePolicy::TabWidget);
    $this->stack->setFrameShape(QFrame::StyledPanel);
    $this->stack->objectName = '___pq_creator__pqtabwidget_stack_';
    
    $this->tabbar = new QTabBar($this);
    $this->tabbar->expanding = false;
    $this->tabbar->connect(SIGNAL('currentChanged(int)'), $this, SLOT('setActiveStackIndex(int)'));
    
    $this->setLayout($this->layout);
    $this->setSizePolicy(QSizePolicy::Expanding, QSizePolicy::Expanding, QSizePolicy::TabWidget);
    
    $this->layout->addWidget($this->tabbar);
    $this->layout->addWidget($this->stack);
  }
  
  public function addTab($widget, $text, $icon = null) {
    $index = (int) $this->tabbar->addTab($text);
    if($icon != null) {
      $this->tabbar->setTabIcon($index, $icon);
    }
    
    $scrollArea = new QScrollArea;
    $scrollArea->setWidget($widget);
    $scrollArea->setWidgetResizable(true);
    
    $formwidget = new PQFormWidget($widget);
    $formwidget->resize(400, 300);
    
    $this->stack->addWidget($scrollArea);
  }
  
  public function setActiveStackIndex($sender, $index) {
    $index = (int) $index;
    if($index == $this->stack->count() - 1) {
      $this->tabbar->currentIndex = 0;
      echo tr('Cannot create new form yet');
    }
    else {
      $this->stack->currentIndex = $index;
    }
  }
}