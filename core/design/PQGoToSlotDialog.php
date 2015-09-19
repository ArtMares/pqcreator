<?php

class PQGoToSlotDialog extends QDialog {
    private $startIndex;

    public function __construct($events, $startIndex, $parent = 0) {
        parent::__construct($parent);
        
        $this->startIndex = $startIndex;
        
        /*
        $this->csPath = c(PQNAME)->csPath;
        $this->csEvents = c(PQNAME)->csEvents;
        $this->csName = c(PQNAME)->csName;
        
        $component = get_class($object);
        
        $events = array();
        while ($component != null) {
            $componentPath = $this->csPath . "/$component/" . $this->csName;
            $eventsPath = $this->csPath . "/$component/" . $this->csEvents;
            
            $r = array();
            
            if (file_exists($eventsPath) && is_file($eventsPath)) {
                require ($eventsPath);

                if (count($r) > 0) {
                    $events[$component] = $r;
                }
            }

            $component = null;
            require($componentPath);

            if (isset($r['parent']) && !empty(trim($r['parent']))) {
                $component = $r['parent'];
            }
        }
        
        $list = new QListWidget($this);
        $list->alternatingRowColors = true;
        $list->connect(SIGNAL('itemDoubleClicked(int)'), $this, SLOT('itemDoubleClicked(int)'));
        
        foreach($events as $c => $e) {
            foreach($e as $event) {
                $list->addItem($event['event']);
            }
        }*/
        
        $list = new QListWidget($this);
        $list->alternatingRowColors = true;
        $list->connect(SIGNAL('itemDoubleClicked(int)'), $this, SLOT('itemDoubleClicked(int)'));
        
        foreach($events as $eventIndex => $event) {
            $list->addItem($event['event']);
        }
    }
    
    public function itemDoubleClicked($sender, $index) {
        $eventIndex = $index + $this->startIndex;
        $this->done($eventIndex);
    }
}