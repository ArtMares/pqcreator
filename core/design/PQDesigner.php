<?php
require_once ("PQSizeCtrl.php");
require_once ("PQTabWidget.php");
require_once ("PQCodeGen.php");

class PQDesigner extends QMainWindow
{
	private $iconpath;
	private $mainLayout;
	private $componentsLayout;
	private $componentsPanel;
	private $componentsDock;
	private $formarea;
	private $formareaLayout;
	private $propertiesLayout;
	private $propertiesPanel;
	private $propertiesDock;
	private $actionsPanel;
	private $actionsLayout;
	private $objHash;
	private $forms;
	private $objectList;
	private $formareaName = "___pq_creator__formarea_";
	private $lastEditedObject = null;
	private $startdragx;
	private $startdragy;
	private $sizeCtrl;
	private $gridSize = 8;
	private $componentsPath;
	private $codegen;
	private $projectParentClass;
	private $project_dir;
	
	public function __construct($projectParentClass = '', $project_dir = '')
	{
		parent::__construct();
		
		$this->objHash = array();
		$this->iconpath = c('___pq_globals_object_')->iconpath;
		$this->componentsPath = c('___pq_globals_object_')->csPath;
		$this->projectParentClass = $projectParentClass;
		
		$this->create_toolBars();
		$this->create_menuBar();
		$this->create_componentsPanel();
		$this->create_formarea();
		$this->create_propertiesDock();
		
		$this->load_components();
		
		$this->mainLayout = new QVBoxLayout;
		$this->mainLayout->setMargin(0);
		$this->mainLayout->addWidget($this->actionsPanel);
		$this->mainLayout->addWidget($this->formarea);
		
		$this->centralWidget = new QWidget;
		$this->centralWidget->setLayout($this->mainLayout);
		
		$this->codegen = new PQCodeGen($projectParentClass, $this->objHash);
		$this->codegen->windowFlags = Qt::Tool;
		$this->codegen->show();
		
		$this->resize(900, 600);
		$this->windowTitle = 'PQCreator';
		$this->objectName = '___pqcreator_mainwidget_';
	}
	
	public function create_formarea() 
	{
		$this->formarea = new PQTabWidget;
		$this->formarea->objectName = '___pq_creator__pqtabwidget_';
		$this->formarea->addTab(new QWidget, 'Form 1');
		$this->formarea->addTab(new QWidget, '', 'C:/pqcreator-git/pqcreator/core/design/faenza-icons/new.png');
		$this->formarea->objectName = $this->formareaName;
	}
	
	public function create_menuBar()
	{
		$menubar = new QMenuBar($this);
		$filemenu = $menubar->addMenu(tr("File", "menubar"));
		$setsmenu = $menubar->addMenu(tr("Edit"));
		$openAction = $filemenu->addAction(tr("Open"));
		connect($openAction, SIGNAL('triggered(bool)') , $this, SLOT('aaacl(bool)'));
		$this->setMenuBar($menubar);
	}

	public function tabCloseRequested($sender, $index)
	{
		echo "tabCloseRequested $index";
	}

	public function create_toolBars()
	{
		$topToolBar = new QToolBar($this);
		$stopAction = $topToolBar->addAction($this->iconpath . 'stop.png', tr('Stop'));
		$runAction = $topToolBar->addAction($this->iconpath . 'run.png', tr('Run'));
		$stopAction->connect(SIGNAL('triggered(bool)') , $this, SLOT('pq_stop_action(bool)'));
		$runAction->connect(SIGNAL('triggered(bool)') , $this, SLOT('pq_run_action(bool)'));
		$this->addToolBar(Qt::TopToolBarArea, $topToolBar);
	}

	public function pq_run_action()
	{
	}

	public function pq_stop_action()
	{
		echo 'stop';
	}

	public function create_componentsPanel()
	{
		$this->componentsLayout = new QVBoxLayout;
		$this->componentsLayout->setMargin(2);
		
		$this->componentsPanel = new QWidget;
		$this->componentsPanel->width = 180;
		$this->componentsPanel->minimumWidth = 180;
		$this->componentsPanel->setLayout($this->componentsLayout);
		
		$this->objectList = new QComboBox($this->componentsPanel);
		$this->objectList->setIconSize(24, 24);
		$this->objectList->minimumHeight = 28;
		$this->objectList->connect(SIGNAL('currentIndexChanged(int)'), $this, SLOT('select_object_by_list_index(int)'));
		$this->componentsLayout->addWidget($this->objectList);
		
		$this->componentsDock = new QDockWidget($this);
		$this->componentsDock->setAllowedAreas(Qt::LeftDockWidgetArea | Qt::RightDockWidgetArea);
		$this->componentsDock->setWidget($this->componentsPanel);
		$this->componentsDock->width = 180;
		$this->componentsDock->minimumWidth = 180;
		
		$this->addDockWidget(Qt::LeftDockWidgetArea, $this->componentsDock);
	}
	
	public function load_components()
	{
		$componentsPath = $this->componentsPath;
		if (is_dir($componentsPath)) {
			if ($dh = opendir($componentsPath)) {
				while (($component = readdir($dh)) !== false) {
					if ($component == '.' || $component == '..') continue;
					$cpath = "$componentsPath/$component";
					if (is_dir($cpath)) {
						$this->create_button($component);
					}
				}
				closedir($dh);
			}
		}
		
		$this->componentsLayout->addSpacer(0, 5000, QSizePolicy::Preferred, QSizePolicy::Expanding);
	}
	
	public function create_button($component)
	{
		$componentsPath = $this->componentsPath;
		$componentPath = "$componentsPath/$component/component.php";
		$r = array();
		if (file_exists($componentPath) && is_file($componentPath)) {
			include $componentPath;

		}
		else return;
		if (!isset($r['group']) || $r['group'] == 'NoVisual') {
			return;
		}

		if (isset($r['parent']) && !empty(trim($r['parent']))) {
			$parentClass = $r['parent'];
		}
		else {
			$parentClass = null;
		}

		$objectName = isset($r['objectName']) ? "pqcreatebutton_${component}_${r[objectName]}" : "pqcreatebutton_${component}_${component}";
		$buttonText = isset($r['title']) ? $r['title'] : $component;
		
		$button = new QPushButton($this->componentsPanel);
		$button->objectName = $objectName;
		$button->text = $buttonText;
		$button->styleSheet = "text-align: left";
		$button->minimumHeight = 30;
		$button->icon = "$componentsPath/$component/icon.png";
		$button->flat = true;
		$button->draggable = true;
		
		$button->parentClass = $parentClass;
		if (isset($r['defobjw']) && isset($r['defobjh'])) {
			$button->defobjw = $r['defobjw'];
			$button->defobjh = $r['defobjh'];
		}

		$button->connect(SIGNAL("mousePressed(int,int,int,int,int)") , $this, SLOT("create_object(int,int,int,int,int)"));
		$button->connect(SIGNAL("mouseMoved(int,int,int,int)") , $this, SLOT("move_object(int,int,int,int)"));
		$button->connect(SIGNAL('mouseReleased(int,int,int,int,int)') , $this, SLOT('test_create(int,int,int,int,int)'));
		
		$this->componentsLayout->addWidget($button);
	}

	public function select_object_by_list_index($sender, $index)
	{
		if ($index == -1) return;
		$object = c($this->objectList->itemText($index));
		if ($object == NULL) return;
		$this->select_object($object);
	}

	public function create_propertiesDock()
	{
		$this->propertiesDock = new QDockWidget($this);
		$this->propertiesDock->setAllowedAreas(Qt::LeftDockWidgetArea | Qt::RightDockWidgetArea);
		
		$this->create_propertiesPanel();
		
		$this->addDockWidget(Qt::RightDockWidgetArea, $this->propertiesDock);
	}
	
	public function create_propertiesPanel()
	{
		if ($this->propertiesDock != null) {
			$widget = $this->propertiesDock->widget();
			if ($widget != null) {
				$widget->free();
			}
		}
		
		$this->propertiesLayout = new QVBoxLayout;
		$this->propertiesPanel = new QWidget;
		$this->propertiesPanel->minimumWidth = 180;
		$this->propertiesPanel->width = 180;
		$this->propertiesPanel->setLayout($this->propertiesLayout);
		$this->propertiesDock->setWidget($this->propertiesPanel);
	}

	public function aaacl($sender, $b)
	{
		echo 'OPEN!';
	}

	public function create_object($sender, $x, $y, $globalX, $globalY, $button)
	{
		$this->unselect_object();
		
		$e = explode("_", $sender->objectName);
		$type = $e[1];
		$objectName = $e[2];
		
		$index = 0;
		if (isset($this->objHash[$objectName])) {
			$index = 1;
			while (isset($this->objHash["${objectName}_$index"])) {
				$index++;
			}

			$objectName = "${objectName}_$index";
		}

		$this->startdragx = 5;
		$this->startdragy = 5;
		
		$obj = new $type;
		$obj->objectName = $objectName;
		$obj->setWindowFlags(Qt::Tool | Qt::WindowStaysOnTopHint | Qt::FramelessWindowHint);
		//$obj->setAttribute(Qt::WA_TranslucentBackground);
		$obj->parentClass = $sender->parentClass;
		$obj->move($globalX, $globalY);
		$obj->windowOpacity = 0.7;
		$obj->lockParentClassEvents = true;
		$obj->defaultPropertiesLoaded = false;
		
		if ($sender->defobjw !== null 
				&& $sender->defobjh !== null) {
			$obj->resize($sender->defobjw, $sender->defobjh);
		}

		$obj->setPHPEventListener($this, dynObject_eventListener);
		$obj->addPHPEventListenerType(QEvent::ContextMenu);
		$obj->addPHPEventListenerType(QEvent::KeyPress);
		$obj->addPHPEventListenerType(QEvent::MouseButtonPress);
		$obj->addPHPEventListenerType(QEvent::MouseButtonRelease);
		$obj->addPHPEventListenerType(QEvent::MouseMove);
		$obj->show();
		
		$objDataArr = array( 'object' => $obj, 'properties' => array() );
		$objDataArr['properties'][] = 'objectName';
		$objDataArr['properties'][] = 'x';
		$objDataArr['properties'][] = 'y';
		$objDataArr['properties'][] = 'width';
		$objDataArr['properties'][] = 'height';
		
		$objData = new ArrayObject($objDataArr, ArrayObject::ARRAY_AS_PROPS);
		
		$this->objHash[$objectName] = $objData;
		$this->lastEditedObject = $obj;
	}

	public function object_key_event($sender, $key, $text)
	{
	}

	public function test_create($sender, $x, $y, $globalX, $globalY, $button)
	{
		$sender->releaseMouse();
		$obj = $this->lastEditedObject;
		$widget = $this->is_formarea($obj, $globalX, $globalY);
		if ($obj === $widget) {
			$this->select_object($obj);
			return;
		}

		if ($widget != NULL) {
			$ppoint = $widget->mapFromGlobal($globalX - $this->startdragx, $globalY - $this->startdragy);
			$newObjX = floor($ppoint['x'] / $this->gridSize) * $this->gridSize;
			$newObjY = floor($ppoint['y'] / $this->gridSize) * $this->gridSize;
			$obj->draggable = false;
			$obj->setParent($widget);
			if ($widget->layout() != NULL) {
				$widget->layout()->addWidget($obj);
			}

			$obj->windowOpacity = 1;
			$obj->styleSheet = '';
			$obj->move($newObjX, $newObjY);
			$obj->show();
			if (!$obj->defaultPropertiesLoaded) {
				$obj->isDynObject = true;
				$objectName = $obj->objectName;
				$component = get_class($obj);
				$icon = $this->componentsPath . "/$component/icon.png";
				$this->objectList->addItem($objectName, $icon);
				$this->objectList->currentIndex = $this->objectList->count() - 1;
				if ($component == 'QWidget') {
					$obj->connect(SIGNAL('widgetEvent(int)') , $this, SLOT('dynObjectListener(int)'));
				}
			}

			$this->codegen->update_code();
			$this->select_object($obj);
			return;
		}
		else {
			$this->lastEditedObject = null;
			$this->delete_object($obj);
		}
	}

	public function dynObjectListener($sender, $event)
	{
		if ($event == QEvent::Paint) {
			$painter = new QPainter($sender);
			$painter->setPen("#666", 1, $this->penStyle);
			$painter->drawPointBackground(8, 8, $sender->width, $sender->height);
			$painter->free();
		}
	}

	public function unselect_object($sender = 0, $x = 0, $y = 0, $globalX = 0, $globalY = 0, $btn = 0)
	{
		if ($this->sizeCtrl != null && is_object($this->sizeCtrl)) {
			$this->sizeCtrl->__destruct();
		}

		$this->sizeCtrl = null;
	}

	public function select_object(&$object)
	{
		$this->unselect_object();
		$this->create_propertiesPanel();
		$this->lastEditedObject = &$object;
		$this->sizeCtrl = new PQSizeCtrl($this->codegen, $object->parent, $object, $this->gridSize);
		$this->load_object_properties($object);
		$this->objectList->setCurrentText($object->objectName);
		$object->setFocus();
	}

	public function dynObject_eventListener($sender, $event)
	{
		switch ($event->type) {
		case QEvent::ContextMenu:
			// Запретить открывать меню, если указатель был смещен с объекта
			if ($sender != widgetAt(mousePos() ['x'], mousePos() ['y'])) {
				return true;
			}

			$this->select_object($sender);
			$menu = new QMenu();
			
			$raiseAction = $menu->addAction(c('___pq_globals_object_')->qticonpath . 'editraise.png', tr('To front'));
			$raiseAction->connect(SIGNAL('triggered(bool)'), $this, SLOT('raise_object(bool)'));
			$raiseAction->__pq_objectName_ = $sender->objectName;
			
			$lowerAction = $menu->addAction(c('___pq_globals_object_')->qticonpath . 'editlower.png', tr('To back'));
			$lowerAction->connect(SIGNAL('triggered(bool)'), $this, SLOT('lower_object(bool)'));
			$lowerAction->__pq_objectName_ = $sender->objectName;
			
			$menu->exec(mousePos() ['x'], mousePos() ['y']);
			$menu->free();
			return true;
			
		case QEvent::KeyPress:
			if ($event->key === 16777223) { // Delete button
				$this->unselect_object();
				$this->delete_object($sender);
				$this->create_propertiesPanel();
			}
			return true;
			
		case QEvent::MouseButtonPress:
			if($event->button === Qt::LeftButton) {
				$this->start_drag($sender, $event->x, $event->y, $event->globalX, $event->globalY, $event->button);
			}
			return true;
		
		case QEvent::MouseButtonRelease:
			if($event->button === Qt::LeftButton) {
				$this->stop_drag($sender, $event->x, $event->y, $event->globalX, $event->globalY, $event->button);
			}
			return true;
			
		case QEvent::MouseMove:
				$this->move_object($sender, $event->x, $event->y, $event->globalX, $event->globalY);
			return true;
		}
		
		return false;
	}

	public function start_drag($sender, $x, $y, $globalX, $globalY, $button)
	{
		$this->unselect_object();
		switch ($button) {
		case Qt::LeftButton:
			$sender->draggable = true;
			$ppoint = $sender->mapToGlobal(0, 0);
			$sender->move($ppoint['x'], $ppoint['y']);
			$sender->draggable = true;
			$sender->setParent(0);
			$sender->windowFlags = Qt::Tool | Qt::WindowStaysOnTopHint | Qt::FramelessWindowHint;
			$sender->windowOpacity = 0.7;
			$sender->show();
			$component = get_class($sender);
			if ($component != 'QTextEdit' && $component != 'QTabWidget' && $component != 'QTableWidget') {
				$sender->grabMouse();
			}

			$this->lastEditedObject = & $sender;
			$this->startdragx = &$x;
			$this->startdragy = &$y;
			break;
		}
	}

	public function stop_drag($sender, $x, $y, $globalX, $globalY, $button)
	{
		$sender->releaseMouse();
		$sender->draggable = false;
		switch ($button) {
		case Qt::LeftButton:
			$this->test_create($sender, $x, $y, $globalX, $globalY, $button);
			break;
		}
	}

	public function raise_object($sender, $bool)
	{
		c($sender->__pq_objectName_)->raise();
	}

	public function lower_object($sender, $bool)
	{
		c($sender->__pq_objectName_)->lower();
	}
	
	public function is_formarea($object, $globalX, $globalY)
	{
		$wpoint = $this->mapFromGlobal($globalX, $globalY);
		$widget = $this->widgetAt($wpoint['x'], $wpoint['y']);
		if ($widget === $object) {
			$wpoint = $widget->parent->mapFromGlobal($globalX, $globalY);
			$widget = $widget->parent->widgetAt($wpoint['x'], $wpoint['y']);
		}

		if ($widget != NULL) {
			$parent = $widget;
			while ($parent != NULL) {
				if ($parent->objectName != '___pq_formwidget__centralwidget_') {
					$parent = $parent->parent;
					continue;
				}

				$parentClass = get_class($widget);
				while ($parentClass != 'QWidget' && $parentClass != 'QFrame' && $parentClass != 'QGroupBox' && $parentClass != 'PQTabWidget' && $widget != NULL) {
					$widget = $widget->parent;
					$parentClass = get_class($widget);
				}

				return $widget;
			}
		}

		return NULL;
	}

	public function move_object($sender, $x, $y, $globalX, $globalY)
	{
		if (!empty($this->lastEditedObject) && $this->lastEditedObject != null) {
			if ($sender->draggable) {
				$newx = $globalX - $this->startdragx;
				$newy = $globalY - $this->startdragy;
				
				//if($this->lastEditedObject->isDynObject) {
					if(!$this->is_formarea($this->lastEditedObject, $globalX, $globalY)) {
						$this->lastEditedObject->styleSheet = 'background-color:#ff0000; border:1px solid #600000;';
						$this->lastEditedObject->windowOpacity = 0.4;
					}
					else {
						$this->lastEditedObject->styleSheet = '';
						$this->lastEditedObject->windowOpacity = 0.8;
					}
				//}
				
				$this->lastEditedObject->move($newx, $newy);
			}
		}
	}

	public function delete_object($object)
	{
		$childObjects = $object->getChildObjects();
		foreach($childObjects as $childObject) {
			unset($this->objHash[$childObject->objectName]);
		}

		$objectName = $object->objectName;
		unset($this->objHash[$objectName]);
		$this->objectList->removeItem($this->objectList->itemIndex($objectName));
		$object->free();
		$this->codegen->update_code();
	}

	public function load_object_properties($object)
	{
		$component = get_class($object);
		
		// Загружаем все свойства в массив
		
		$properties = array();
		while ($component != null) {
			$componentPath = $this->componentsPath . "/$component/component.php";
			$propertiesPath = $this->componentsPath . "/$component/properties.php";
			$r = array();
			if (file_exists($propertiesPath) && is_file($propertiesPath)) {
				require ($propertiesPath);

				if (count($r) > 0) {
					$properties[$component] = $r;
				}
			}

			$component = null;
			require ($componentPath);

			if (isset($r['parent']) && !empty(trim($r['parent']))) {
				$component = $r['parent'];
			}
		}

		// Отображаем все свойства на панели

		foreach($properties as $c => $p) {
			$label = new QLabel($this->propertiesPanel);
			$label->text = $c;
			$label->styleSheet = 'font-weight:bold;';
			$table = new QTableWidget($this->propertiesPanel);
			$table->addColumns(2);
			$table->setHorizontalHeaderText(0, tr('Property'));
			$table->setHorizontalHeaderText(1, tr('Value'));
			$table->verticalHeaderVisible = false;
			$defaultPropertiesLoaded = $object->defaultPropertiesLoaded;
			foreach($p as $property) {
				$row = $table->rowCount();
				$table->addRow();
				$table->setTextAt($row, 0, $property['title']);
				$widget = null;
				switch ($property['property']) {
				case 'text':
				case 'title':
					if (isset($property['value'])) {
						$object->$property['property'] = $property['value'];
					}
					else {
						$object->$property['property'] = $object->objectName;
					}

					break;
				}

				switch ($property['type']) {
				case 'mixed':
				case 'int':
					$widget = new QLineEdit;
					if (isset($property['value']) && !$defaultPropertiesLoaded) {
						$widget->text = $property['value'];
					}
					else {
						$widget->text = $object->$property['property'];
					}

					// set validator if section exists

					if (isset($property['validator'])) {
						$widget->setRegExpValidator($property['validator']);
					}
					else {

						// if property type is `int` and validator section not exists,
						// then set a default validator for integers

						if ($property['type'] == 'int') {
							$widget->setRegExpValidator('[0-9]*');
						}
					}

					$widget->connect(SIGNAL('textChanged(QString)') , $this, SLOT('set_object_property(QString)'));
					break;

				case 'bool':
					$widget = new QCheckBox;
					if (isset($property['value']) && !$defaultPropertiesLoaded) {
						$widget->checked = $property['value'];
					}
					else {
						$widget->checked = $object->$property['property'];
					}

					$widget->connect(SIGNAL('toggled(bool)') , $this, SLOT('set_object_property(bool)'));
					break;
				}

				if ($widget != null) {
					$widget->__pq_objectName_ = $object->objectName;
					$widget->__pq_property_ = $property['property'];
					$table->setCellWidget($row, 1, $widget);
				}

				if (!$defaultPropertiesLoaded) {
					$objectName = $object->objectName;
					if (!in_array($property['property'], $this->objHash[$objectName]->properties)) {
						if (isset($property['value'])) {

							// $this->objHash[$objectName]->properties[] = $property['property'];

						}
					}
				}
			}

			$this->propertiesLayout->addWidget($label);
			$this->propertiesLayout->addWidget($table);
		}

		if (!$defaultPropertiesLoaded) {
			$object->defaultPropertiesLoaded = true;
		}
	}

	public function set_object_property($sender, $value)
	{
		$objectName = $sender->__pq_objectName_;
		$property = $sender->__pq_property_;
		$object = c($objectName);
		if ($property == "objectName") {
			$objData = $this->objHash[$objectName];
			unset($this->objHash[$objectName]);
			$this->objectList->setItemText($this->objectList->itemIndex($objectName) , $value);
			$this->objHash[$value] = $objData;
			$sender->__pq_objectName_ = $value;
			$objectName = $value;
		}

		if (!in_array($property, $this->objHash[$objectName]->properties)) {
			$this->objHash[$objectName]->properties[] = $property;
		}

		$object->$property = $value;
		$this->codegen->update_code();
	}
}