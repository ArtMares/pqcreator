<?php

class PQCodeGen extends QTextEdit {
  private $projectParentClass;
  private $objHash;
  
  public function __construct($projectParentClass, &$objHash) {
    parent::__construct();
    
    $this->projectParentClass = $projectParentClass;
    $this->objHash = &$objHash;
    $this->show();
    $this->move(0, 0);
    $this->resize(400, 600);
    $this->readOnly = true;
  }
  
  public function update_code() {
    $e = '%--P-Q--C-O-D-E%%';
    $extends = $this->projectParentClass;
    
    $mainclass = "class Main extends $extends {\n$e\n}";
    $e_mainclass = '';
    
    $fn___construct = "  public function __construct() {\n$e\n  }";
    $e_fn___construct = "    parent::__construct();\n    \$this->initComponents();";
    
    $fn_initComponents = "  private function initComponents() {\n$e\n  }";
    $e_fn_initComponents = '';
    
    $lastIndex = count($this->objHash) - 1;
    $index = 0;
    foreach($this->objHash as $objectName => $object) {
      $component = get_class($object);
      
      $e_mainclass .= "  private $${objectName};\n";
      
      $objX = $object->x;
      $objY = $object->y;
      $objW = $object->width;
      $objH = $object->height;
      $e_fn_initComponents .= "    \$this->${objectName} = new ${component}(\$this);\n";
      $e_fn_initComponents .= "    \$this->${objectName}->move(${objX}, ${objY});\n";
      $e_fn_initComponents .= "    \$this->${objectName}->resize(${objW}, ${objH});";
      
      if($index < $lastIndex) $e_fn_initComponents .= "\n\n";
      else $e_mainclass .= "\n";
      
      $index++;
    }
    
    $fn___construct = str_replace($e, $e_fn___construct, $fn___construct);
    $fn_initComponents = str_replace($e, $e_fn_initComponents, $fn_initComponents);
    
    $e_mainclass .= $fn___construct . "\n\n";
    $e_mainclass .= $fn_initComponents;
    $mainclass = str_replace($e, $e_mainclass, $mainclass);
    
    $this->plainText = $mainclass;
  }
}