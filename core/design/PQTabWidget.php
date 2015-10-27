<?php

class PQFormWidget extends QFrame
{
    public $centralWidget;

    private $sizeCtrl;
    private $penStyle;
    private $centralWidgetLayout;
    private $borderWidget;
    
    private $designer;
    
    public function __construct($designer, $parent = 0)
    {
        if ($parent == 0) parent::__construct();
        else parent::__construct($parent);
        
        $this->designer = $designer;
        
        $this->setPHPEventListener($this, eventListener);
        $this->addPHPEventListenerType(QEvent::ContextMenu);
        $this->addPHPEventListenerType(QEvent::Show);
        $this->addPHPEventListenerType(QEvent::Resize);
        $this->addPHPEventListenerType(QEvent::MouseButtonRelease);
        
        $this->objectName = '___pq_formwidget_';
        $this->styleSheet = '#___pq_formwidget_ { border:1px dashed #222; margin:10px; } ';
        
        $this->borderWidget = new QFrame($this);
        $this->borderWidget->objectName = '___pq_formwidget__borderwidget_';
        $this->borderWidget->styleSheet = '#___pq_formwidget__borderwidget_ { border:7px solid #aaa; background:#f0f0f0;}';
        
        $this->centralWidget = new QWidget($this->borderWidget);
        $this->centralWidget->objectName = '___pq_formwidget__centralwidget_';
        $this->centralWidget->m_lockParentClassEvents = true;
        
        $this->centralWidget->setPHPEventListener($this, eventListener);
        $this->centralWidget->addPHPEventListenerType(QEvent::Paint);
        
        
        $borderWidget_layout = new QHBoxLayout;
        $borderWidget_layout->setMargin(0);
        $borderWidget_layout->addWidget($this->centralWidget);
        
        $layout = new QHBoxLayout;
        $layout->setMargin(0);
        $layout->addWidget($this->borderWidget);
        
        $this->borderWidget->setLayout($borderWidget_layout);
        $this->setLayout($layout);
        $this->penStyle = Qt::SolidLine;
    }

    public function eventListener($sender, $event)
    {
        switch ($event->type) {
        case QEvent::Resize:
            c('___pqcreator_mainwidget_')->unselectObject();
            $this->parent->setMinimumSize($this->width, $this->height);
            return true;
            
        case QEvent::Show:
            if ($this->sizeCtrl != null && is_object($this->sizeCtrl)) {
                $this->sizeCtrl->__destruct();
            }

            $this->sizeCtrl = new PQSizeCtrl($this, $this->parent, $this, 8, 10);
            $this->sizeCtrl->unmovable($this->sizeCtrl->lt);
            $this->sizeCtrl->unmovable($this->sizeCtrl->lm);
            $this->sizeCtrl->unmovable($this->sizeCtrl->lb);
            $this->sizeCtrl->unmovable($this->sizeCtrl->rt);
            $this->sizeCtrl->unmovable($this->sizeCtrl->tm);
            return true;
            
        case QEvent::ContextMenu:
            $menu = new QMenu();
            $menu_layout = $menu->addMenu(tr('Layout'));
            
            $action = $menu_layout->addAction(c(PQNAME)->qticonsPath . '/editbreaklayout.png', tr('Break layout'));
            $action->connect(SIGNAL('triggered(bool)') , $this, SLOT('menuLayoutAction(bool)'));
            $action->objectName = 'menuLayoutAction_NoLayout';
            
            $action = $menu_layout->addAction(c(PQNAME)->qticonsPath . '/editvlayout.png', tr('Vertical layout'));
            $action->connect(SIGNAL('triggered(bool)') , $this, SLOT('menuLayoutAction(bool)'));
            $action->objectName = 'menuLayoutAction_QVBoxLayout';
            
            $action = $menu_layout->addAction(c(PQNAME)->qticonsPath . '/edithlayout.png', tr('Horizontal layout'));
            $action->connect(SIGNAL('triggered(bool)') , $this, SLOT('menuLayoutAction(bool)'));
            $action->objectName = 'menuLayoutAction_QHBoxLayout';
            
            $menu->exec(mousePos() ['x'], mousePos() ['y']);
            $menu->free();
            return true;
            
        case QEvent::Paint:
            $painter = new QPainter($sender);
            $painter->setPen("#aaaaaa", 1, $this->penStyle);
            $painter->drawPointBackground(8, 8, $this->width, $this->height);
            $painter->free();
            return true;
            
        case QEvent::MouseButtonRelease:
            if($event->button === Qt::LeftButton) {
             //   $this->designer->unselectObject();
            }
        }

        return true;
    }

    public function menuLayoutAction($sender, $bool)
    {
        c('___pqcreator_mainwidget_')->unselectObject();
        if ($this->centralWidgetLayout != null 
                && is_object($this->centralWidgetLayout)) {
            $this->centralWidgetLayout->free();
        }
        
        $layoutClass = explode('_', $sender->objectName) [1];
        if ($layoutClass == 'NoLayout') {
            return;
        }

        $this->centralWidgetLayout = new $layoutClass;
        $this->centralWidget->setLayout($this->centralWidgetLayout);
        foreach($this->centralWidget->getChildObjects(false) as $widget) {
                $this->centralWidgetLayout->addWidget($widget);
        }
    }

    public function updateCode()
    {
    }
}

class PQTabWidget extends QWidget

{
    private $stack;
    private $tabbar;
    private $layout;
    
    private $designer;
    
    public function __construct(&$designer, $parent = 0)
    {
        if ($parent == 0) parent::__construct();
        else parent::__construct($parent);
        
        $this->designer = $designer;
        
        $this->layout = new QVBoxLayout;
        $this->layout->margin = 1;
        $this->layout->spacing = 0;
        
        $this->stack = new QStackedWidget($this);
        $this->stack->lineWidth = 0;
        $this->stack->setSizePolicy(QSizePolicy::Preferred, QSizePolicy::Preferred, QSizePolicy::TabWidget);
        $this->stack->setFrameShape(QFrame::StyledPanel);
        $this->stack->objectName = '___pq_creator__pqtabwidget_stack_';
        $this->styleSheet = '#___pq_creator__pqtabwidget_stack_ { margin-top:-2px; background:#fff; }';
        
        $this->tabbar = new QTabBar($this);
        $this->tabbar->expanding = false;
        $this->tabbar->connect(SIGNAL('currentChanged(int)'), $this, SLOT('setActiveStackIndex(int)'));
        
        $this->setLayout($this->layout);
        $this->setSizePolicy(QSizePolicy::Expanding, QSizePolicy::Expanding, QSizePolicy::TabWidget);
        
        $this->layout->addWidget($this->tabbar);
        $this->layout->addWidget($this->stack);
    }
    
    public function setTabText($index, $text) {
        $this->tabbar->setTabText($index, $text);
    }

    private function createToolBar($stackArea, $stack) {
        $toolbar = new QWidget($stackArea);
        
        $projectButton = new QPushButton($toolbar);
        $projectButton->text = tr('Project');
        $projectButton->checkable = true;
        $projectButton->checked = true;
        $projectButton->autoExclusive = true;
        
        $projectButton->onClicked = function($button, $event) use($stack) {
            $stack->setCurrentIndex(0);
            
            $codegen = $stack->widget(1);
            if($codegen != null) {
                $codegen->disableRules();
            }
        };
        
        $sourceButton = new QPushButton($toolbar);
        $sourceButton->text = tr('Source');
        $sourceButton->checkable = true;
        $sourceButton->autoExclusive = true;
        
        $sourceButton->onClicked = function($button, $event) use($stack) {
            $stack->setCurrentIndex(1);
            
            $codegen = $stack->widget(1);
            if($codegen != null) {
                $codegen->enableRules();
                $codegen->rehighlight();
            }
        };
        
        $toolbar_layout = new QHBoxLayout;
        $toolbar_layout->addWidget($projectButton);
        $toolbar_layout->addWidget($sourceButton);
        
        $toolbar->setLayout($toolbar_layout);
        
        return $toolbar;
    }
    
    public function addTab($widget, &$codegen, $text, $icon = null)
    {
        $index = $this->tabbar->addTab($text);
        if ($icon != null) {
            $this->tabbar->setTabIcon($index, $icon);
        }
        
        $stackArea = new QWidget;
        
        $scrollArea_viewport = new QWidget;
        $scrollArea_viewport->setPalette("#ffffff");
        $scrollArea_viewport->objectName = '___pq_creator__pqtabwidget_scrollarea_viewport';
        $scrollArea_viewport->styleSheet = '#___pq_creator__pqtabwidget_scrollarea_viewport > QWidget { padding-top:2px; }';
        
        $scrollArea = new QScrollArea($stackArea);
        $scrollArea->objectName = '___pq_creator__pqtabwidget_scrollarea_';
        $scrollArea->setViewport($scrollArea_viewport);
        $scrollArea->setWidget($widget);
        $scrollArea->styleSheet = '#___pq_creator__pqtabwidget_scrollarea_ { border: none; }';
        
        $widget->resize(300,300);
        $widget->isFormAreaWidget = true;
        $widget->tabIndex = $index-1;
        
        $stack = new QStackedWidget($stackArea);
        $stack->objectName = '___pq_creator__pqtabwidget_stackarea_stack_';
        $stack->addWidget($scrollArea);
        if($codegen != null) $stack->addWidget($codegen);
        
        // $toolbar = $this->createToolBar($stackArea, $stack);
        
        $stackArea_layout = new QVBoxLayout;
        $stackArea_layout->setMargin(0);
        // $stackArea_layout->addWidget($toolbar);
        $stackArea_layout->addWidget($stack);
        
        $stackArea->setLayout($stackArea_layout);
        
        $this->stack->addWidget($stackArea);
        
        if($index > 1) { // 26.09.2015
            $this->tabbar->moveTab($index, $index-1);
            $this->tabbar->currentIndex = $index-1;
        }
    }

    public function setActiveStackIndex($sender, $index)
    {
        $tabCount = $this->stack->count;
        
        if ($index == $tabCount-1) {
            $widget = new QWidget;
            $widget->objectName = '___pq_formwidget__centralwidget_form';
            $windowTitle = "Form " . $tabCount;
            
            $this->tabbar->currentIndex = 0;
            $null = null;
            $this->addTab($widget, $null, $windowTitle);
            //$this->tabbar->moveTab($tabCount, $index); // 26.09.2015
            //$this->tabbar->currentIndex = $index; // 26.09.2015
            
            $widget->tabIndex = $index;
            
            $this->designer->createForm($widget, "QWidget", $windowTitle);
            return;
        } 
        
        // из-за перемещения вкладок (moveTab), 
        // индексы стака не соответствуют индексам вкладок
        $this->stack->currentIndex = $index == 0 ? 0 : $index + 1;
    }
}
