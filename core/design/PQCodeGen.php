<?php

class PQCodeGen extends QTextEdit {
  private $projectParentClass;
  private $objHash;
  private $pq_globals;
  
  private $typeHash;
  
  public function __construct($projectParentClass, &$objHash) {
    parent::__construct();
    
    $this->projectParentClass = $projectParentClass;
    $this->objHash = &$objHash;
    
    $this->show();
    $this->move(0, 0);
    $this->resize(400, 600);
    $this->readOnly = true;
    $this->pq_globals = c('___pq_globals_object_');
    
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
  }
  
  public function update_code() {
    $e = '%--P-Q--C-O-D-E%%';
    $extends = $this->projectParentClass;
    
    $mainclass = "class PQMain extends $extends {\n$e\n}";
    $e_mainclass = '';
    
    $fn___construct = "  public function __construct() {\n$e\n  }";
    $e_fn___construct = "    parent::__construct();\n    \$this->initComponents();";
    
    $fn_initComponents = "  private function initComponents() {\n$e  }";
    $e_fn_initComponents = '';
    
    $lastIndex = count($this->objHash) - 1;
    $index = 0;
    foreach($this->objHash as $objectName => $objData) {
      $component = get_class($objData->object);
      
      $e_mainclass .= "  private $${objectName};\n";
      
      $e_fn_initComponents .= "    \$this->${objectName} = new ${component}(\$this);\n";
      if(isset($objData->properties)) {
        foreach($objData->properties as $property) {
          $value = $objData->object->$property;
          
          $propertyType = $this->get_property_type($component, $property);
          switch($propertyType) {
            case 'int':
              $value = (int) $value;
              $e_fn_initComponents .= "    \$this->${objectName}->${property} = ${value};\n";
              break;
              
            case 'mixed':
              $e_fn_initComponents .= "    \$this->${objectName}->${property} = \"${value}\";\n";
              break;
              
            case 'bool':
              $value = ((bool) $value) ? 'true' : 'false';
              $e_fn_initComponents .= "    \$this->${objectName}->${property} = ${value};\n";
              break;
          }
        }
      }
      
      if($index < $lastIndex) $e_fn_initComponents .= "\n";
      else $e_mainclass .= "\n";
      
      $index++;
    }
    
    $fn___construct = str_replace($e, $e_fn___construct, $fn___construct);
    $fn_initComponents = str_replace($e, $e_fn_initComponents, $fn_initComponents);
    
    $e_mainclass .= $fn___construct . "\n\n";
    $e_mainclass .= $fn_initComponents;
    $mainclass = str_replace($e, $e_mainclass, $mainclass);
    $mainclass .= "\n\n\$pqmain = new PQMain; \$pqmain->show();";
    
    $this->plainText = $mainclass;
  }
  
  private function get_property_type($component, $property) {
    // Основные параметры достаются из хеша
    if(isset($this->typeHash[$property])) {
      return $this->typeHash[$property];
    }
    
    $componentPath = $this->pq_globals->csPath . "$component/" . $this->pq_globals->csName;
    $propertiesPath = $this->pq_globals->csPath . "$component/" . $this->pq_globals->csProperties;
    
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
      
      // Если тип не был определен, то пытаемся найти его в дочерних классах
      $r = array();
      if($type == 'unknown') {
        if(file_exists($componentPath)
            && is_file($componentPath)) {
          include($componentPath);
          if(isset($r['parent'])) {
            return $this->get_property_type($r['parent'], $property);
          }
        }
      }
    }
    
    return $type;
  }
}