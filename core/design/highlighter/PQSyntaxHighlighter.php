<?php

class PQSyntaxHighlighter extends QSyntaxHighlighter {
    private $rules;
    private $textEdit;

    public function __construct($textEdit) {
        parent::__construct($textEdit);
        $this->textEdit = $textEdit;
        $this->initRules();
    }
    
    private function initRules() {
        $xml = simplexml_load_file(__DIR__ . '/phpqt5.xml');
        $xmlstrarr = print_r($xml, true);
        
        // Загрузка правил подсветки
        $this->rules = array();
        foreach($xml->highlighting->list as $schema) {
            $name = (string) $schema['name'];
            $this->rules[$name] = array();
            $this->rules[$name]['keyWords'] = array();
            
            foreach($schema->item as $word) {
                $this->rules[$name]['keyWords'][] = $word;
            }
        }
        
        foreach($xml->highlighting->formats->format as $formatdata) {
            $format = new QTextCharFormat;
            $name = (string) $formatdata['name'];
            
            $attributes = $formatdata->attributes();
            foreach($attributes as $attr => $value) {
                switch($attr) {
                case 'foreground':
                    $format->foreground = (string) $value;
                    break;
                    
                case 'bold':
                    $value = (int) $value;
                    if($value == 1) $format->fontWeight = QFont::Bold;
                    break;
                }
            }
            
            $this->rules[$name]['format'] = $format;
        }
        
        // ключевые слова
        foreach($this->rules as $rule) {
            foreach($rule['keyWords'] as $word) {
                if(isset($rule['format'])) {
                    $word = (string) $word;
                    $this->addRule("\\b$word\\b", $rule['format']);
                }
            }
        }
        
        // комментарии
        $comment_format = new QTextCharFormat;
        $comment_format->foreground = '#898887';
        $this->addRule('/\*', '\*/', 1, $comment_format);
        $this->addRule('//', '$', 2, $comment_format);
        $this->addRule('#', '$', 3, $comment_format);
        
        // запрещённые
        $disabled_format = new QTextCharFormat;
        $disabled_format->foreground = '#590000';
        $disabled_format->background = '#C53C3C';
        $this->addRule('<\?(?:=|php)?', $disabled_format);
        
        // строки (с допуском форматирования)
        $strings_format = new QTextCharFormat;
        $strings_format->foreground = '#BF0303';
        $this->addRule('\"', '\"', 10, $strings_format);
        
        // переменные
        $variable_format = new QTextCharFormat;
        $variable_format->foreground = '#5555FF';
        $this->addRule('\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\[[a-zA-Z0-9_]*\])*', $variable_format);
        $this->addRule('\$\{[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\[[a-zA-Z0-9_]*\])*\}', $variable_format);
        $this->addRule('\{\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\[([0-9]*|"[^"]*"|\$[a-zA-Z]*)|\'[^\']*\'|\])*(->[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\[[a-zA-Z0-9_]*\])*(\[([0-9]*|"[a-zA-Z_]*")|\'[a-zA-Z_]*\'|\])*)*\}', $variable_format);
        
        // строки (без форматирования)
        $this->addRule('\'', '\'', 10, $strings_format);
    }
}
