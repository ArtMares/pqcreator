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
    private $projectDir;
    private $projectName;
    
    private $runningProcess;
    private $processCheckTimer;
    
    private $runAction;
    private $stopAction;
    private $buildAction;
    
    /* "Мёртвая дистанция", при которой компонента не будет перемещаться. */
    private $deadDragDistance = 8;
    
    /* Эффект отлипания перетаскиваемого компонента.
     * Если true - компонент "отлипает" от формы,
     * если false - компонент начинает перещение плавно. */
    private $detachEffect = true;
    
    public function __construct($projectParentClass = '', $projectDir = '', $projectName = '')
    {
        parent::__construct();
        
        $this->objHash = array();
        $this->iconsPath = c(PQNAME)->iconsPath;
        $this->componentsPath = c(PQNAME)->csPath;
        $this->projectParentClass = $projectParentClass;
        $this->projectDir = $projectDir;
        $this->projectName = $projectName;
        
        $this->createToolBars();
        $this->createMenuBar();
        $this->createComponentsPanel();
        $this->createFormarea();
        $this->createPropertiesDock();
        
        $this->loadComponents();
        
        $this->mainLayout = new QVBoxLayout;
        $this->mainLayout->setMargin(0);
        $this->mainLayout->addWidget($this->actionsPanel);
        $this->mainLayout->addWidget($this->formarea);
        
        $this->centralWidget = new QWidget;
        $this->centralWidget->setLayout($this->mainLayout);
        
        $this->codegen = new PQCodeGen($projectParentClass, $this->objHash, '___pq_formwidget__centralwidget_form1');
        $this->codegen->windowFlags = Qt::Tool;
        $this->codegen->show();
        
        $this->processCheckTimer = new QTimer(300);
        $this->processCheckTimer->onTimer = function($timer, $event) {
            if($this->runningProcess != null) {
                $status = proc_get_status($this->runningProcess);
                if(!$status['running']) {
                    $this->runningProcess = null;
                    $this->processCheckTimer->stop();
                    $this->stopAction->enabled = false;
                    $this->runAction->enabled = true;
                }
            }
        };
        
        $this->resize(900, 600);
        $this->windowTitle = $projectName . ' - PQCreator';
        $this->objectName = '___pqcreator_mainwidget_';
        
        $this->show();
        
        $nullSender = new QWidget();
        $nullSender->objectName = 'nullSender_QWidget_form1';
        $point = c('___pq_formwidget__centralwidget_form1')->mapToGlobal($this->gridSize, $this->gridSize);
        $this->createObject($nullSender, 0, 0, $point['x'], $point['y'], 0);
        $this->testCreate($nullSender, 0, 0, $point['x']+$this->gridSize, $point['y']+$this->gridSize, 0);
        $this->lastEditedObject->draggable = false;
        $this->lastEditedObject->movable = false;
        $this->lastEditedObject->isMainWidget = true;
        $this->lastEditedObject->disabledSels = 'lt,rm,lb,rt,tm';
        $this->lastEditedObject->resize(400, 300);
        $this->selectObject($this->lastEditedObject);
        
    }
    
    public function createFormarea() 
    {
        $this->formarea = new PQTabWidget($this);
        $this->formarea->objectName = '___pq_creator__pqtabwidget_';
        
        $widget = new QWidget;
        $widget->objectName = '___pq_formwidget__centralwidget_form1';
        $widget->resize(100,100);
        
        $this->formarea->addTab($widget, 'Form 1');
        $this->formarea->addTab(new QWidget, '', 'C:/pqcreator-git/pqcreator/core/design/faenza-icons/new.png');
        $this->formarea->objectName = $this->formareaName;
    }
    
    public function createMenuBar()
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

    public function createToolBars()
    {
        $topToolBar = new QToolBar($this);
        
        $this->buildAction = $topToolBar->addAction($this->iconsPath . '/build.png', tr('Build'));
        $this->buildAction->enabled = false;
        
        $this->stopAction = $topToolBar->addAction($this->iconsPath . '/stop.png', tr('Stop'));
        $this->stopAction->connect(SIGNAL('triggered(bool)') , $this, SLOT('pqStopAction(bool)'));
        $this->stopAction->enabled = false;
        
        $this->runAction = $topToolBar->addAction($this->iconsPath . '/run.png', tr('Run'));
        $this->runAction->connect(SIGNAL('triggered(bool)') , $this, SLOT('pqRunAction(bool)'));
        
        $this->addToolBar(Qt::TopToolBarArea, $topToolBar);
    }

    public function pqRunAction()
    {
        $filename = $this->projectDir . '/main.php';
        $exec = $this->projectDir . '/pqengine.exe';
        if(file_put_contents($filename, $this->codegen->getCode()) === FALSE) {
            $messagebox = new QMessageBox;
            $messagebox->warning(0, tr('PQCreator error'),
                                    sprintf( tr("Cannot write data to file: %1\r\n".
                                                'Please check that the file is not opened in another application'), 
                                            $filename )
                                );
            $messagebox->free();
        }
        else {
            $pipes = array();
            $this->runningProcess = proc_open($exec, array(), $pipes, $this->projectDir);
            $this->runAction->enabled = false;
            $this->stopAction->enabled = true;
            $this->processCheckTimer->start();
        }
    }

    public function pqStopAction()
    {
        if($this->runningProcess != null) {
            $this->processCheckTimer->stop();
            $status = proc_get_status($this->runningProcess);
            exec('taskkill /F /T /PID ' . $status['pid']);
        
            $this->stopAction->enabled = false;
            $this->runAction->enabled = true;
        }
    }

    public function createComponentsPanel()
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
        $this->objectList->connect(SIGNAL('currentIndexChanged(int)'), $this, SLOT('selectObjectByListIndex(int)'));
        $this->componentsLayout->addWidget($this->objectList);
        
        $this->componentsDock = new QDockWidget($this);
        $this->componentsDock->setAllowedAreas(Qt::LeftDockWidgetArea | Qt::RightDockWidgetArea);
        $this->componentsDock->setWidget($this->componentsPanel);
        $this->componentsDock->width = 180;
        $this->componentsDock->minimumWidth = 180;
        
        $this->addDockWidget(Qt::LeftDockWidgetArea, $this->componentsDock);
    }
    
    public function loadComponents()
    {
        $componentsPath = $this->componentsPath;
        if (is_dir($componentsPath)) {
            if ($dh = opendir($componentsPath)) {
                while (($component = readdir($dh)) !== false) {
                    if ($component == '.' || $component == '..') continue;
                    $cpath = "$componentsPath/$component";
                    if (is_dir($cpath)) {
                        $this->createButton($component);
                    }
                }
                closedir($dh);
            }
        }
        
        $this->componentsLayout->addSpacer(0, 5000, QSizePolicy::Preferred, QSizePolicy::Expanding);
    }
    
    public function createButton($component)
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
        $button->creator = true;
        
        $button->parentClass = $parentClass;
        if (isset($r['defobjw']) && isset($r['defobjh'])) {
            $button->defobjw = $r['defobjw'];
            $button->defobjh = $r['defobjh'];
        }

        $button->connect(SIGNAL("mousePressed(int,int,int,int,int)") , $this, SLOT("createObject(int,int,int,int,int)"));
        $button->connect(SIGNAL("mouseMoved(int,int,int,int)") , $this, SLOT("moveObject(int,int,int,int)"));
        $button->connect(SIGNAL('mouseReleased(int,int,int,int,int)') , $this, SLOT('testCreate(int,int,int,int,int)'));
        
        $this->componentsLayout->addWidget($button);
    }

    public function selectObjectByListIndex($sender, $index)
    {
        if ($index == -1) return;
        $object = c($this->objectList->itemText($index));
        if ($object == NULL) return;
        $this->selectObject($object);
    }

    public function createPropertiesDock()
    {
        $this->propertiesDock = new QDockWidget($this);
        $this->propertiesDock->setAllowedAreas(Qt::LeftDockWidgetArea | Qt::RightDockWidgetArea);
        
        $this->createPropertiesPanel();
        
        $this->addDockWidget(Qt::RightDockWidgetArea, $this->propertiesDock);
    }
    
    public function createPropertiesPanel()
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

    public function createObject($sender, $x, $y, $globalX, $globalY, $button)
    {
        $this->unselectObject();
        
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

        $obj = new $type;
        $obj->objectName = $objectName;
        $obj->setWindowFlags(Qt::Tool | Qt::WindowStaysOnTopHint | Qt::FramelessWindowHint);
        $obj->parentClass = $sender->parentClass;
        $obj->move($globalX, $globalY);
        $obj->windowOpacity = 0.6;
        $obj->lockParentClassEvents = true;
        $obj->defaultPropertiesLoaded = false;
        $obj->draggable = true;
        
        if ($sender->defobjw !== null 
                && $sender->defobjh !== null) {
            $obj->resize($sender->defobjw, $sender->defobjh);
        }

        $obj->setPHPEventListener($this, dynObjectEventListener);
        $obj->addPHPEventListenerType(QEvent::ContextMenu);
        $obj->addPHPEventListenerType(QEvent::KeyPress);
        $obj->addPHPEventListenerType(QEvent::MouseButtonPress);
        $obj->addPHPEventListenerType(QEvent::MouseButtonRelease);
        $obj->addPHPEventListenerType(QEvent::MouseMove);
        
        if($type == 'QWidget') {
            $obj->addPHPEventListenerType(QEvent::Paint);
            $obj->addPHPEventListenerType(QEvent::Resize);
        }
        
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
        
        /* Смещение создаваемого компонента влево-наверх,
           чтобы он не перекрывался курсором */
        $this->startdragx = 5; 
        $this->startdragy = 5;
    }

    public function testCreate($sender, $x, $y, $globalX, $globalY, $button)
    {
         $sender->releaseMouse();
        $obj = $this->lastEditedObject;
        $widget = $this->isFormarea($obj, $globalX, $globalY);
        if ($obj === $widget) {
            $this->selectObject($obj);
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
            $obj->movable = true;
            $obj->move($newObjX, $newObjY);
            $obj->show();
            
            if (!$obj->defaultPropertiesLoaded) {
                $obj->isDynObject = true;
                $objectName = $obj->objectName;
                $component = get_class($obj);
                $icon = $this->componentsPath . "/$component/icon.png";
                
                $this->objectList->addItem($objectName, $icon);
                $this->objectList->currentIndex = $this->objectList->count() - 1;
                
                /* TODO: O_о
                if ($component == 'QWidget') {
                    $obj->connect(SIGNAL('widgetEvent(int)') , $this, SLOT('dynObjectListener(int)'));
                }
                */
            }

            $this->codegen->updateCode();
            $this->selectObject($obj);
            return;
        }
        else {
            $this->lastEditedObject = null;
            $this->deleteObject($obj);
        }
    }

    public function dynObjectListener($sender, $event)
    {
        if ($event == QEvent::Paint) {
            $painter = new QPainter($sender);
            $painter->setPen("#888", 1, $this->penStyle);
            $painter->drawPointBackground(8, 8, $sender->width, $sender->height);
            $painter->free();
        }
    }

    public function unselectObject($sender = 0, $x = 0, $y = 0, $globalX = 0, $globalY = 0, $btn = 0)
    {
        if ($this->sizeCtrl != null && is_object($this->sizeCtrl)) {
            $this->sizeCtrl->__destruct();
        }

        $this->sizeCtrl = null;
    }

    public function selectObject($object)
    {
        $this->unselectObject();
        $this->createPropertiesPanel();
        $this->lastEditedObject = $object;
        $this->sizeCtrl = new PQSizeCtrl($this->codegen, $object->parent, $object, $this->gridSize);
        $this->loadObjectProperties($object);
        $this->objectList->setCurrentText($object->objectName);
        $object->setFocus();
    }

    public function dynObjectEventListener($sender, $event)
    {
        switch ($event->type) {
        case QEvent::ContextMenu:
            // Запретить открывать меню, если указатель был смещен с объекта
            if ($sender != widgetAt(mousePos() ['x'], mousePos() ['y'])) {
                return true;
            }

            $this->selectObject($sender);
            $menu = new QMenu();
            
            $raiseAction = $menu->addAction(c(PQNAME)->qticonsPath . '/editraise.png', tr('To front'));
            $raiseAction->connect(SIGNAL('triggered(bool)'), $this, SLOT('raiseObject(bool)'));
            $raiseAction->__pq_objectName_ = $sender->objectName;
            
            $lowerAction = $menu->addAction(c(PQNAME)->qticonsPath . '/editlower.png', tr('To back'));
            $lowerAction->connect(SIGNAL('triggered(bool)'), $this, SLOT('lowerObject(bool)'));
            $lowerAction->__pq_objectName_ = $sender->objectName;
            
            $component = get_class($sender);
            if($component == 'QWidget'
                || $component == 'QGroupBox'
                || $component == 'QFrame') {
                
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
            }
            
            $menu->exec(mousePos() ['x'], mousePos() ['y']);
            $menu->free();
            return true;
            
        case QEvent::KeyPress:
            if ($event->key === 16777223) { // Delete button
                $this->deleteObject($sender);
                $this->createPropertiesPanel();
            }
            return true;
            
        case QEvent::MouseButtonPress:
            if($event->button === Qt::LeftButton) {
                $this->startDrag($sender, $event->x, $event->y, $event->globalX, $event->globalY, $event->button);
            }
            return true;
        
        case QEvent::MouseButtonRelease:
            if($event->button === Qt::LeftButton) {
                $this->stopDrag($sender, $event->x, $event->y, $event->globalX, $event->globalY, $event->button);
            }
            return true;
            
        case QEvent::MouseMove:
            $this->moveObject($sender, $event->x, $event->y, $event->globalX, $event->globalY);
            return true;
            
        case QEvent::Paint:
            $painter = new QPainter($sender);
            $painter->setPen("#888", 1, Qt::SolidLine);
            $painter->drawPointBackground(8, 8, $sender->width, $sender->height);
            $painter->free();
            $sender->autoFillBackground = true;
          //  c('___pq_formwidget__centralwidget_')->resize((int)($sender->x + $sender->width + $this->gridSize), $sender->y + $sender->height + $this->gridSize);
           // return true;
            break;
            
        case QEvent::Resize:
            if($sender->isMainWidget === true) {
                c('___pq_formwidget__centralwidget_form1')->resize((int)($sender->x + $sender->width + $this->gridSize), $sender->y + $sender->height + $this->gridSize);
            }
            break;
            
        default: 
            return false;
        }
        
        return false;
    }
    
    public function menuLayoutAction($sender, $bool)
    {
        $layout = $this->lastEditedObject->layout();
        if ($layout != null) {
            $layout->free();
        }
        
        $layoutClass = explode('_', $sender->objectName) [1];
        if ($layoutClass == 'NoLayout') {
            return;
        }

        $layout = new $layoutClass;
        $this->lastEditedObject->setLayout($layout);
        foreach($this->lastEditedObject->getChildObjects(false) as $widget) {
            $layout->addWidget($widget);
        }
        
        $this->codegen->updateCode();
    }

    public function startDrag($sender, $x, $y, $globalX, $globalY, $button)
    {
        $this->unselectObject();
        
        switch ($button) {
        case Qt::LeftButton:
            $this->lastEditedObject = $sender;
            
            if($sender->movable) {
                $sender->draggable = true;
                $sender->moved = false;
                
                $this->startdragx = $x;
                $this->startdragy = $y;
            }
            
            return true;
        }
    }

    public function stopDrag($sender, $x, $y, $globalX, $globalY, $button)
    {
        $sender->releaseMouse();
        
        switch ($button) {
        case Qt::LeftButton:
            if($sender->draggable
                && $sender->moved) {
                $this->testCreate($sender, $x, $y, $globalX, $globalY, $button);
            }
            else {
                $this->selectObject($sender);
            }
            
            $sender->draggable = false;
            
            return true;
        }
    }

    public function raiseObject($sender, $bool)
    {
        c($sender->__pq_objectName_)->raise();
    }

    public function lowerObject($sender, $bool)
    {
        c($sender->__pq_objectName_)->lower();
    }
    
    public function isFormarea($object, $globalX, $globalY)
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
                if ($parent->objectName != '___pq_formwidget__centralwidget_form1') {
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

    public function moveObject($sender, $x, $y, $globalX, $globalY)
    {
        if (!empty($this->lastEditedObject) 
            && $this->lastEditedObject != null) {
            
            if ($sender->creator) {
                $sender = $this->lastEditedObject;
            }
            
            if ($sender->draggable) {
                if ($sender->isDynObject 
                    && !$sender->moved) {
                    
                    $ppoint = $sender->mapToGlobal(0, 0);
                    
                    $dx = $ppoint['x'] - ($globalX - $this->startdragx);
                    $dy = $ppoint['y'] - ($globalY - $this->startdragy);
                    
                    if(abs($dx) <= $this->deadDragDistance
                        && abs($dy) <= $this->deadDragDistance) {
                        
                        return;
                    }
                    else {
                        if(!$this->detachEffect) {
                            $this->startdragx -= $dx;
                            $this->startdragy -= $dy;
                        }
                    }
                    
                    $this->unselectObject();
                    
                    $sender->move($ppoint['x'], $ppoint['y']);
                    $sender->draggable = true;
                    $sender->setParent(0);
                    $sender->windowFlags = Qt::Tool | Qt::WindowStaysOnTopHint | Qt::FramelessWindowHint;
                    $sender->windowOpacity = 0.6;
                    $sender->show();
                    
                    $component = get_class($sender);
                    if ($component != 'QTextEdit' && $component != 'QTabWidget' && $component != 'QTableWidget') {
                        $sender->grabMouse();
                    }
                    
                    $sender->moved = true;
                    
                    return;
                }
                
                $newx = $globalX - $this->startdragx;
                $newy = $globalY - $this->startdragy;
                
                // if($sender->isDynObject) {
                    if(!$this->isFormarea($sender, $globalX, $globalY)) {
                        $sender->styleSheet = 'background-color:#ff0000; border:1px solid #600000;';
                        $sender->windowOpacity = 0.4;
                    }
                    else {
                        $sender->styleSheet = '';
                        $sender->windowOpacity = 0.6;
                    }
                // }
                
                $sender->move($newx, $newy);
            }
        }
    }

    public function deleteObject($object)
    {
        $childObjects = $object->getChildObjects();
        if($childObjects != null) {
            foreach($childObjects as $childObject) {
                $this->deleteObject($childObject);
            }
        }

        $this->unselectObject();
        $objectName = $object->objectName;
        unset($this->objHash[$objectName]);
        $this->objectList->removeItem($this->objectList->itemIndex($objectName));
        $object->free();
        $this->codegen->updateCode();
    }

    // TODO: добавить кэширование!
    public function loadObjectProperties($object)
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
                    if (!$defaultPropertiesLoaded) {
                        $objectName = $object->objectName;
                        if (isset($property['value'])) {
                            $object->$property['property'] = $property['value'];
                        }
                        else {
                            $object->$property['property'] = $objectName;
                        }
                        
                        if (!in_array($property['property'], $this->objHash[$objectName]->properties)) {
                            $this->objHash[$objectName]->properties[] = $property['property'];
                        }
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

                    $widget->connect(SIGNAL('textChanged(QString)') , $this, SLOT('setObjectProperty(QString)'));
                    break;

                case 'bool':
                    $widget = new QCheckBox;
                    if (isset($property['value']) && !$defaultPropertiesLoaded) {
                        $widget->checked = $property['value'];
                    }
                    else {
                        $widget->checked = $object->$property['property'];
                    }

                    $widget->connect(SIGNAL('toggled(bool)') , $this, SLOT('setObjectProperty(bool)'));
                    break;
                }

                if ($widget != null) {
                    $widget->__pq_property_ = $property['property'];
                    $table->setCellWidget($row, 1, $widget);
                }

                /* TODO: Не помню для чего это, но вроде оно реализовано в setObjectProperty() =D
                 * Скорее всего не понадобится
                if (!$defaultPropertiesLoaded) {
                    $objectName = $object->objectName;
                    if (!in_array($property['property'], $this->objHash[$objectName]->properties)) {
                        if (isset($property['value'])) {

                            $this->objHash[$objectName]->properties[] = $property['property'];

                        }
                    }
                }
                */
            }

            $this->propertiesLayout->addWidget($label);
            $this->propertiesLayout->addWidget($table);
        }

        if (!$defaultPropertiesLoaded) {
            $object->defaultPropertiesLoaded = true;
        }
    }

    public function setObjectProperty($sender, $value)
    {
        $property = $sender->__pq_property_;
        $object = $this->lastEditedObject;
        $objectName = $object->objectName;
        
        if ($property == "objectName") {
            $objData = $this->objHash[$objectName];
            unset($this->objHash[$objectName]);
            $this->objectList->setItemText($this->objectList->itemIndex($objectName) , $value);
            $this->objHash[$value] = $objData;
            $objectName = $value;
        }

        if (!in_array($property, $this->objHash[$objectName]->properties)) {
            $this->objHash[$objectName]->properties[] = $property;
        }

        $object->$property = $value;
        $this->codegen->updateCode();
    }
}