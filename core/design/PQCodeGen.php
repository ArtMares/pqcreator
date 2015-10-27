<?php

require_once ("PQPlainTextEdit.php");

class PQCodeGen extends PQPlainTextEdit {
    private $projectParentClass;
    private $objHash;
    private $projectData;
    private $sortedObjHash;
    private $nullParentName;
    private $pq_globals;
    
    private $typeHash;
    private $dynTypeHash;
    
    public function __construct($projectParentClass, &$objHash, &$projectData, $nullParentName)
    {
        parent::__construct();
        
        $this->projectParentClass = $projectParentClass;
        $this->objHash = &$objHash;
        $this->projectData = &$projectData;
        $this->nullParentName = $nullParentName;
        $this->pq_globals = c(PQNAME);
        $this->move(0, 0);
        $this->resize(400, 600);
        $this->readOnly = true;
       
        // хеш основных типов

        $this->typeHash = array(
            'objectName' => 'mixed',
            'x' => 'int',
            'y' => 'int',
            'width' => 'int',
            'height' => 'int',
            'enabled' => 'bool',
            'checked' => 'bool',
            'checkable' => 'bool',
            'visible' => 'bool',
            'text' => 'mixed',
            'styleSheet' => 'mixed'
        );
        
        $this->dynTypeHash = array();
    }
    
    public function setObjHash(&$objHash) {
        $this->objHash = &$objHash;
    }
    
    public function setProjectData(&$projectData) {
        $this->projectData = &$projectData;
    }
    
    public function resortHash() {
        $sortedObjHash = array();
        
        foreach($this->objHash as $objectName => $objData) {
            $index = count($sortedObjHash);
            $parentObjectName = c($objectName)->parent->objectName;
            
            $sortedObjHash[$index] = array('objectName' => $objectName, 
                                            'parentObjectName' => $parentObjectName,
                                            'lvl' => 0, 
                                            'objData' => $objData);
            
            while($parentObjectName !== $this->nullParentName) {
                $sortedObjHash[$index]['lvl']++;
                $parentObjectName = c($parentObjectName)->parent->objectName;
            }
        }
        
        $t = true;
        while($t) {
            $t = false;
            for($index = 0; $index < count($sortedObjHash) - 1; $index++) {
                if ($sortedObjHash[$index]['lvl'] > $sortedObjHash[$index + 1]['lvl']) {
                    $temp = $sortedObjHash[$index + 1];
                    $sortedObjHash[$index + 1] = $sortedObjHash[$index];
                    $sortedObjHash[$index] = $temp;
                    $t = true;
                }
            }
        }
        
        $this->sortedObjHash = $sortedObjHash;
    }
    
    public function getCode() {
        return "<?php\n\n" . $this->plainText;
    }
    
    public function getSortedObjHash() {
        $this->resortHash();
        return $this->sortedObjHash;
    }
  
    public function updateCode() 
    {
        // Update object tree
        $this->resortHash();
        
        $s1 = "    ";
        $s2 = "        ";
        $s3 = "            ";
        
        $sections = array('declaration-class',
                            'declaration-vars',
                            '__construct',
                            'pre-initComponents',
                            'post-initComponents',
                            'pre-loadEvents',
                            'post-loadEvents',
                            'pre-run',
                            'post-run'
                            );
        
        $e = '%--P-Q--C-O-D-E%%';
        $extends = $this->projectParentClass;
        
        $fn_privateVars = "$e\n%%--P-Q--declaration-vars--%%";
        $e_privateVars = '';
        
        $mainclass = "%%--P-Q--declaration-class--%%\n\nclass PQApp extends QObject {\n$e\n}";
        $e_mainclass = '';
        
        $fn___construct = "${s1}public function __construct() {\n$e\n$s1}";
        $e_fn___construct = "${s2}parent::__construct();"
                                ."\n\n%%--P-Q--__construct--%%${s2}\$this->initComponents();"
                                ."\n${s2}\$this->loadEvents();";
        
        $fn_initComponents = "${s1}private function initComponents() {\n%%--P-Q--pre-initComponents--%%$e\n%%--P-Q--post-initComponents--%%$s1}";
        $e_fn_initComponents = '';
        
        $fn_loadEvents = "${s1}private function loadEvents() {\n%%--P-Q--pre-loadEvents--%%$e\n%%--P-Q--post-loadEvents--%%$s1}";
        $e_fn_loadEvents = '';
        
        $mainForm_objectName = $this->sortedObjHash[0]['objectName'];
        $fn_run = "${s1}public function run() {\n%%--P-Q--pre-run--%%$e\n%%--P-Q--post-run--%%${s1}}";
        $e_fn_run = "${s2}\$this->${mainForm_objectName}->show();";
    
        $lastIndex = count($this->sortedObjHash) - 1;
        $index = 0;
        
        foreach($this->sortedObjHash as $index => $objHash) {
            $objectName = $objHash['objectName'];
            $objData = $objHash['objData'];
            
            $component = get_class($objData->object);
            $e_privateVars .= "${s1}private $${objectName};";
            
            $parentObjectName = $objData->object->parent->objectName;
            if($parentObjectName === $this->nullParentName) {
                $e_fn_initComponents .= "${s2}\$this->${objectName} = new ${component};\n";
            }
            else {
                $e_fn_initComponents .= "${s2}\$this->${objectName} = new ${component}(\$this->$parentObjectName);\n";
                $parentLayout = $this->objHash[$parentObjectName]->object->layout();
                if($parentLayout !== null) {
                    $e_fn_initComponents .= "${s2}\$this->${parentObjectName}->layout()->addWidget(\$this->${objectName});\n";
                }
            }
            
            $objectLayout = $objData->object->layout();
            if($objectLayout !== null) {
                $layoutClass = get_class($objectLayout);
                $e_fn_initComponents .= "${s2}\$this->${objectName}->setLayout(new $layoutClass);\n";
            }
            
            // Properties 
            if(isset($objData->properties)) {
                foreach($objData->properties as $property) {
                    $value = $objData->object->$property;
                    
                    $propertyType = $this->getPropertyType($component, $property);
                    switch($propertyType) {
                        case 'int':
                        $value = (int) $value;
                        $e_fn_initComponents .= "${s2}\$this->${objectName}->${property} = ${value};\n";
                        break;
                        
                        case 'mixed':
                        $e_fn_initComponents .= "${s2}\$this->${objectName}->${property} = \"${value}\";\n";
                        break;
                        
                        case 'bool':
                        $value = ((bool) $value) ? 'true' : 'false';
                        $e_fn_initComponents .= "${s2}\$this->${objectName}->${property} = ${value};\n";
                        break;
                    }
                }
            }
            
            // Methods 
            if(isset($objData->methods)) {
                foreach($objData->methods as $method) {
                    $e_fn_initComponents .= "${s2}\$this->${objectName}->$method;\n";
                }
            }
            
            // Events
            if(isset($objData->events)) {
                foreach($objData->events as $event => $eventData) {
                    $code = str_replace("\n", "${s3}\n", $eventData['code']);
                    $e_fn_loadEvents .= "${s2}\$this->${objectName}->$event = function(\$sender, \$event) {\n"
                                        . "${s3}$code\n"
                                        . "${s2}};\n";
                }
            }
            
            if($index < $lastIndex) {
                $e_fn_initComponents .= "\n";
                $e_privateVars .= "\n"; // 26.09.2015
            }
            // else $e_privateVars .= '/* }}} */'; // 26.09.2015
            
            $index++;
        }

        $fn___construct = str_replace($e, $e_fn___construct, $fn___construct);
        $fn_initComponents = str_replace($e, $e_fn_initComponents, $fn_initComponents);
        $fn_loadEvents = str_replace($e, $e_fn_loadEvents, $fn_loadEvents);
        $fn_run = str_replace($e, $e_fn_run, $fn_run);
        $fn_privateVars = str_replace($e, $e_privateVars, $fn_privateVars);

        $e_mainclass .= $fn___construct . "\n\n";
        $e_mainclass .= $fn_initComponents . "\n\n";
        $e_mainclass .= $fn_loadEvents . "\n\n";
        $e_mainclass .= $fn_run . "\n\n";
        $e_mainclass .= $fn_privateVars;
        
        $mainclass = str_replace($e, $e_mainclass, $mainclass);
        $mainclass .= "\n\n\$pqapp = new PQApp;\n\$pqapp->run();\nqApp::exec();";
        
        // Sections
        foreach($sections as $section) {
            $es = "%%--P-Q--${section}--%%";
            
            $e_section = '';
            if(isset($this->projectData['scripts'][$section])) {
                $e_section = '';
                $e_section_arr = explode("\n", $this->projectData['scripts'][$section]);
                foreach($e_section_arr as $line) {
                    $e_section .= "$s2$line\n";
                }
                $e_section .= "\n";
            }
            
            $mainclass = str_replace($es, $e_section, $mainclass);
        }
        
        $this->plainText = $mainclass;
    }

    public function getPropertyType($component, $property) {
        // Основные параметры достаются из хеша
        if(isset($this->typeHash[$property])) {
            return $this->typeHash[$property];
        }

        // Кэшированные параметры
        if(isset($this->dynTypeHash["${component}_${property}"])) {
            return $this->dynTypeHash["${component}_${property}"];
        }

        $componentPath = $this->pq_globals->csPath . "/$component/" . $this->pq_globals->csName;
        $propertiesPath = $this->pq_globals->csPath . "/$component/" . $this->pq_globals->csProperties;

        $type = 'unknown';

        $r = array();
        if(file_exists($propertiesPath)
                && is_file($propertiesPath)) {
            include($propertiesPath);
            
            // Пытаемся определить тип параметра
            foreach($r as $p) {
                if($p['property'] == $property) {
                    if(isset($p['type'])) {
                        $type = $p['type'];
                    }
                }
            }
            
            if($type == 'unknown') {
                foreach($r_ex as $p) {
                    if($p['property'] == $property) {
                        if(isset($p['type'])) {
                            $type = $p['type'];
                        }
                    }
                }
            }
            
            // Если тип не был определен, то пытаемся найти его в дочерних классах
            $r = array();
            if($type == 'unknown') {
                if(file_exists($componentPath)
                        && is_file($componentPath)) {
                    include($componentPath);
                    if(isset($r['parent'])) {
                        return $this->getPropertyType($r['parent'], $property);
                    }
                }
            }
        }
        
        // Кэшируем
        $this->dynTypeHash["${component}_${property}"] = $type;

        return $type;
    }
}