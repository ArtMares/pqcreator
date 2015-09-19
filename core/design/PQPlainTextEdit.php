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
        
        $this->setPHPEventListener($this, eventListener);
        $this->addPHPEventListenerType(QEvent::KeyPress);
        
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
        $text = $this->plainText;
        
        $cpos = $this->getTextCursor()->position;
        
        $lock = ( $this->isIn_initComponents($rx, $text, $cpos)
                  || $this->isIn_loadEvents($rx, $text, $cpos)
                  || $this->isIn_run($rx, $text, $cpos)
                );
                
        $rx->free();
        
        if(!$lock) {
            switch($event->key) {
            case Qt::Key_Tab:
                if($event->type == QEvent::KeyPress) {
                    $cursor = $sender->getTextCursor();
                    
                    // добавляем таб в начало строки
                    if($cursor->hasSelection) {
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
                    $cursor = $sender->getTextCursor();
                    
                    // удаляем табуляцию в начале строки
                    if($cursor->hasSelection) {
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
                    $cursor = $sender->getTextCursor();
                    
                    if(!$cursor->hasSelection) {
                    
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
                $cursor = $sender->getTextCursor();
                
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
                    $cursor = $sender->getTextCursor();
                    $this->dedent($cursor);
                    $sender->insertPlainText('}');
                    $lock = true;
                }
            }
        }
        
        return $lock;
    }
    
    private function isIn_initComponents($rx, $text, $cpos) {
        $rx->pattern = "private function initComponents\(\) \{";
        $sindex = $rx->indexIn($text, $rx->lastIndex);
        
        $rx->pattern = "\}";
        $eindex = $rx->indexIn($text, $sindex) + 2;
        $rx->lastIndex = $eindex;
        
        if($sindex == -1) return false;
        else return ($cpos > $sindex && $cpos < $eindex);
    }
    
    private function isIn_loadEvents($rx, $text, $cpos) {
        $rx->pattern = "private function loadEvents\(\) \{";
        $sindex = $rx->indexIn($text);
        
        $rx->pattern = "\}";
        $eindex = $rx->indexIn($text, $sindex) + 2;
        $rx->lastIndex = $eindex;
        
        if($sindex == -1) return false;
        else return ($cpos > $sindex && $cpos < $eindex);
    }
    
    private function isIn_run($rx, $text, $cpos) {
        $rx->pattern = "public function run\(\) \{";
        $sindex = $rx->indexIn($text);
        
        $rx->pattern = "\}";
        $eindex = $rx->indexIn($text, $sindex) + 2;
        $rx->lastIndex = $eindex;
        
        if($sindex == -1) return false;
        else return ($cpos > $sindex && $cpos < $eindex);
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