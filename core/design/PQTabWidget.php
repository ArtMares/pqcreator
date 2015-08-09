<?php

class PQFormWidget extends QFrame
{
	public $centralWidget;

	private $sizeCtrl;
	private $penStyle;
	private $centralWidgetLayout;
	private $borderWidget;
	
	public function __construct($parent = 0)
	{
		if ($parent == 0) parent::__construct();
		else parent::__construct($parent);
		
		$this->setPHPEventListener($this, eventListener);
		$this->addPHPEventListenerType(QEvent::ContextMenu);
		$this->addPHPEventListenerType(QEvent::Show);
		$this->addPHPEventListenerType(QEvent::Resize);
		
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
		foreach($this->centralWidget->getChildObjects() as $widget) {
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
	
	public function __construct($parent = 0)
	{
		if ($parent == 0) parent::__construct();
		else parent::__construct($parent);
		$this->layout = new QVBoxLayout;
		$this->layout->spacing = 0;
		
		$this->stack = new QStackedWidget($this);
		$this->stack->lineWidth = 0;
		$this->stack->setSizePolicy(QSizePolicy::Preferred, QSizePolicy::Preferred, QSizePolicy::TabWidget);
		$this->stack->setFrameShape(QFrame::StyledPanel);
		$this->stack->objectName = '___pq_creator__pqtabwidget_stack_';
		$this->styleSheet = '#___pq_creator__pqtabwidget_stack_ { margin-top:-2px; border:none; }';
		
		$this->tabbar = new QTabBar($this);
		$this->tabbar->expanding = false;
		$this->tabbar->connect(SIGNAL('currentChanged(int)'), $this, SLOT('setActiveStackIndex(int)'));
		
		$this->setLayout($this->layout);
		$this->setSizePolicy(QSizePolicy::Expanding, QSizePolicy::Expanding, QSizePolicy::TabWidget);
		
		$this->layout->addWidget($this->tabbar);
		$this->layout->addWidget($this->stack);
	}

	public function addTab($widget, $text, $icon = null)
	{
		$index = (int)$this->tabbar->addTab($text);
		if ($icon != null) {
			$this->tabbar->setTabIcon($index, $icon);
		}

		$scrollArea = new QScrollArea;
		$scrollArea->objectName = '___pq_creator__pqtabwidget_scrollarea_';
		
		$viewport = new QWidget;
		$viewport->setPalette("#ffffff");
		$viewport->autoFillBackground = true;
		$viewport->objectName = '___pq_creator__pqtabwidget_scrollarea_viewport';
		$viewport->styleSheet = '#___pq_creator__pqtabwidget_scrollarea_viewport > QWidget { padding-top:2px; }';
		$scrollArea->setViewport($viewport);
		
		$scrollArea->setWidget($widget);
		$scrollArea->setWidgetResizable(true);
		
		$formwidget = new PQFormWidget($widget);
		$formwidget->resize(400, 300);
		
		$this->stack->addWidget($scrollArea);
	}

	public function setActiveStackIndex($sender, $index)
	{
		$index = (int)$index;
		if ($index == $this->stack->count() - 1) {
			$this->tabbar->currentIndex = 0;
			echo tr('Cannot create new form yet');
		}
		else {
			$this->stack->currentIndex = $index;
		}
	}
}
