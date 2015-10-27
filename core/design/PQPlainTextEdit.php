<?php

require_once(__DIR__ . '/highlighter/PQSyntaxHighlighter.php');

class PQPlainTextEdit extends QPlainTextEdit {
    private $highlighter;
    private $backspaceDedentType = 0;
    
    private $tabSpacesCount = 4;
    private $tabChar = '    ';
    
    public function __construct($parent = 0) {
        parent::__construct($parent);
        
        $this->highlighter = new PQSyntaxHighlighter($this);
        $this->highlighter->rulesEnabled = false;
        
        $this->setFontFamily("Roboto Mono");
        $this->setFontPixelSize(12);
        
        $this->onTextChanged = function($sender, $event) {
            if( $sender->textLength == 0 ) {
                $sender->setFontFamily("Roboto Mono");
            }
        };
    }
    
    public function enableRules() {
        $this->highlighter->rulesEnabled = true;
    }
    
    public function disableRules() {
        $this->highlighter->rulesEnabled = false;
    }
    
    public function rehighlight() {
        $this->highlighter->rehighlight();
    }
    
    /* Редактор */
    public function eventListener($sender, $event) {
        $rx = new QRegExp('');
        $rx->lastIndex = 0;
        
        $cursor = $this->getTextCursor();
        $cpos = $cursor->position;
        $apos = $cursor->anchor;
        $hasSelection = $cursor->hasSelection;
        
        //if($hasSelection) {
        //    $text = $cursor->selectedText();
        //}
        //else {
            $text = $this->plainText;
        //}
        
        $lock = ($this->isIn_lockedConstructor($rx, $text, $apos, $cpos, $hasSelection)
                  || $this->isIn_initComponents($rx, $text, $apos, $cpos, $hasSelection)
                  || $this->isIn_loadEvents($rx, $text, $apos, $cpos, $hasSelection)
                  || $this->isIn_run($rx, $text, $apos, $cpos, $hasSelection)
                  || $this->isIn_privateVars($rx, $text, $apos, $cpos, $hasSelection)
                );
                
        $rx->free();
        
        if(!$lock) {
            switch($event->key) {
            case Qt::Key_Tab:
                if($event->type == QEvent::KeyPress) {
                    // добавляем таб в начало строки
                    if($hasSelection) {
                        $exBlockData = $this->getExBlockNums($cursor);
                        $diff = $exBlockData['eblock'] - $exBlockData['sblock'];
                        
                        $cursor->beginEditBlock();
                        for($i = 0; $i <= $diff; $i++)
                        {
                            $cursor->movePosition(4);
                            $cursor->insertText("    ");
                            $cursor->movePosition(16);
                        }
                        $cursor->endEditBlock();

                        $this->reselect($cursor, $exBlockData);
                    }
                    
                    // добавляем таб в конец или середину строки
                    else {
                        $sender->insertPlainText("    ");
                    }
                    
                    $lock = true;
                }
                break;
                
            case  Qt::Key_Backtab:
                if($event->type == QEvent::KeyPress) {
                    // удаляем табуляцию в начале строки
                    if($hasSelection) {
                        $exBlockData = $this->getExBlockNums($cursor);
                        $diff = $exBlockData['eblock'] - $exBlockData['sblock'];
                        
                        for($i = 0; $i <= $diff; $i++)
                        {
                            $this->dedent($cursor);
                            $cursor->movePosition(QTextCursor::Down);
                        }
                        
                        $this->reselect($cursor, $exBlockData);
                    }
                    else {
                        $this->dedent($cursor);
                    }
                    
                    $lock = true;
                }
                break;
                
            case Qt::Key_Backspace:
                if($event->type == QEvent::KeyPress) {
                    if(!$hasSelection) {
                    
                        $blockText = $cursor->blockText;
                        if(empty($blockText)) break;
                        
                        // Удаляет количество пробелов эквивалентное размеру табуляции
                        if($backspaceDedentType == 0) {
                            $positionInBlock = $cursor->positionInBlock;
                            $spos = $positionInBlock - $this->tabSpacesCount;
                            
                            if($spos < 0) {
                                break;
                            }
                            
                            $s = strpos($blockText, $this->tabChar, $spos);
                            
                            // удаляем табуляцию в середине строки
                            if($s === $spos) {
                                $cursor->setPosition($cursor->position - $this->tabSpacesCount);
                                
                                for($n = 0; $n < $this->tabSpacesCount; $n++) {
                                    $cursor->deleteChar();
                                }
                                
                                $lock = true;
                            }
                        }
                        
                        // Удаляет любое количество пробелов, 
                        // не превышающее размер табуляции
                        else {
                            for($i = 0; $i < $this->tabSpacesCount; $i++) {
                                $ci = $cursor->positionInBlock - 1;
                                
                                if($blockText[$ci] == " ") {
                                    $cursor->setPosition($cursor->position - 1);
                                    $cursor->deleteChar();
                                    $lock = true;
                                }
                                else break;
                            }
                        }
                    }
                }
                break;
                
            case Qt::Key_Return:
            case Qt::Key_Enter:
                if($event->type == QEvent::KeyPress) {
                    $blockText = $cursor->blockText;
                    preg_match_all('/^(\s)*/', $blockText, $matches);
                    $spaces = $matches[0][0];
                    
                    $lastChar = $blockText[strlen($blockText)-1];
                    if($lastChar == '{') {
                        $spaces .= $this->tabChar;
                    }
                    
                    $sender->insertPlainText(PHP_EOL . $spaces);
                    $lock = true;
                }
                break;
            }
            
            if(!$lock
                && $event->type == QEvent::KeyPress) 
            {
                if($event->text == '}') {
                    $this->dedent($cursor);
                    $sender->insertPlainText('}');
                    $lock = true;
                }
            }
        }
        
        return $lock;
    }
    
    private function isIn_lockedConstructor($rx, $text, $apos, $cpos, $hasSelection) {
        $rx->pattern = "\/\* \{\{\{ public __construct \*\/\n\s*public function __construct\(\) \{";
        $sindex = $rx->indexIn($text, $rx->lastIndex);
        
        $rx->pattern = "\}\n\/\* \}\}\} \*\/";
        $eindex = $rx->indexIn($text, $sindex) + 12;
        $rx->lastIndex = $eindex;
        
        if($sindex == -1) {
            $rx->pattern = "\/\* \{\{\{ end of __construct \*\/\n";
            $sindex = $rx->indexIn($text, $rx->lastIndex);
            
            $rx->pattern = "\}\n\/\* \}\}\} \*\/";
            $eindex = $rx->indexIn($text, $sindex) + 12;
            $rx->lastIndex = $eindex;
            
            if($sindex == -1) {
                $rx->pattern = "\/\* \{\{\{ end of __construct \*\/\n";
                $sindex = $rx->indexIn($text, $rx->lastIndex);
                
                $rx->pattern = "\}\n\/\* \}\}\} \*\/";
                $eindex = $rx->indexIn($text, $sindex) + 12;
                $rx->lastIndex = $eindex;
            }
            else if($hasSelection) {
                return true;
            }
            else return ($cpos > $sindex && $cpos < $eindex) && ($apos > $sindex && $apos < $eindex);
        }
        else if($hasSelection) {
            return true;
        }
        else return ($cpos > $sindex && $cpos < $eindex) && ($apos > $sindex && $apos < $eindex);
    }
    
    private function isIn_initComponents($rx, $text, $apos, $cpos, $hasSelection) {
        $rx->pattern = "\/\* \{\{\{ private initComponents \*\/\n\s*private function initComponents\(\) \{";
        $sindex = $rx->indexIn($text, $rx->lastIndex);
        
        $rx->pattern = "\}\n\/\* \}\}\} \*\/";
        $eindex = $rx->indexIn($text, $sindex) + 12;
        $rx->lastIndex = $eindex;
        
        if($sindex == -1) return false;
        else if($hasSelection) return true;
        else return ($cpos > $sindex && $cpos < $eindex) && ($apos > $sindex && $apos < $eindex);
    }
    
    private function isIn_loadEvents($rx, $text, $apos, $cpos, $hasSelection) {
        $rx->pattern = "\/\* \{\{\{ private loadEvents \*\/\n\s*private function loadEvents\(\) \{";
        $sindex = $rx->indexIn($text, $rx->lastIndex);
        
        $rx->pattern = "\}\n\/\* \}\}\} \*\/";
        $eindex = $rx->indexIn($text, $sindex) + 12;
        $rx->lastIndex = $eindex;
        
        if($sindex == -1) return false;
        else if($hasSelection) return true;
        else return ($cpos > $sindex && $cpos < $eindex) && ($apos > $sindex && $apos < $eindex);
    }
    
    private function isIn_run($rx, $text, $apos, $cpos, $hasSelection) {
        $rx->pattern = "\/\* \{\{\{ public run \*\/\n\s*public function run\(\) \{";
        $sindex = $rx->indexIn($text, $rx->lastIndex);
        
        $rx->pattern = "\}\n\/\* \}\}\} \*\/";
        $eindex = $rx->indexIn($text, $sindex) + 12;
        $rx->lastIndex = $eindex;
        
        if($sindex == -1) return false;
        else if($hasSelection) return true;
        else return ($cpos > $sindex && $cpos < $eindex) && ($apos > $sindex && $apos < $eindex);
    }
    
    
    private function isIn_privateVars($rx, $text, $apos, $cpos, $hasSelection) {
        $rx->pattern = "\/\* \{\{\{ private variables \*\/\n\s*private";
        $sindex = $rx->indexIn($text, $rx->lastIndex);
        
        $rx->pattern = ";\n\/\* \}\}\} \*\/";
        $eindex = $rx->indexIn($text, $sindex) + 12;
        $rx->lastIndex = $eindex;
        
        if($sindex == -1) return false;
        else if($hasSelection) return true;
        else return ($cpos > $sindex && $cpos < $eindex) && ($apos > $sindex && $apos < $eindex);
    }
    
    public function getExBlockNums($cursor) {
        $exBlockData = array();
        
        $spos = $cursor->anchor;
        $epos = $cursor->position;

        if($spos > $epos)
        {
            $hold = $spos;
            $spos = $epos;
            $epos = $hold;
        }

        $exBlockData['spos'] = $spos;
        $exBlockData['epos'] = $epos;
        
        $cursor->setPosition($spos);
        $exBlockData['sblock'] = $cursor->blockNumber;

        $cursor->setPosition($epos);
        $exBlockData['eblock'] = $cursor->blockNumber;
        
        $cursor->setPosition($spos);
        
        return $exBlockData;
    }
    
    public function reselect($cursor, $exBlockData) {
        $cursor->setPosition($exBlockData['spos']);
        $cursor->movePosition(4, 0);

        $cursor->beginEditBlock();
        while($cursor->blockNumber() < $exBlockData['eblock'])
        {
            $cursor->movePosition(16, 1);
        }
        $cursor->endEditBlock();

        $cursor->movePosition(15, 1);
    }
    
    public function dedent($cursor) {
        $cursor->movePosition(QTextCursor::StartOfLine);

        $line = $cursor->blockText;
        $tline = substr($line, 0, 4);
        for($n = 0; $n < 4; $n++) {
            if($tline[$n] != " ") break;
            $cursor->deleteChar();
        }
    }
}