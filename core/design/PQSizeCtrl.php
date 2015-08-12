<?php

class PQSizeCtrl extends QObject
{
    public $gridSize = 8;
    public $lt;
    public $lb;
    public $lm;
    public $rt;
    public $rb;
    public $rm;
    public $tm;
    public $bm;
    
    private $container;
    private $selobj;
    private $styleSheet = 'background:#000020; border:1px solid #0000a0;';
    private $size = 6;

    private $startx;
    private $starty;
    private $preWidth;
    private $preHeight;
    private $preX;
    private $preY;
    
    private $codegen;
    private $offset;
    
    public function __construct(&$codegen, $container, $object, $gridSize, $offset = 0)
    {
        parent::__construct();
        $this->offset = $offset;
        $this->gridSize = $gridSize;
        $this->codegen = &$codegen;
        $this->container = $container;
        $this->selobj = &$object;
        
        $this->lt = $this->createSel("lt", Qt::SizeFDiagCursor);
        $this->lb = $this->createSel("lb", Qt::SizeBDiagCursor);
        $this->lm = $this->createSel("lm", Qt::SizeHorCursor);
        $this->rt = $this->createSel("rt", Qt::SizeBDiagCursor);
        $this->rb = $this->createSel("rb", Qt::SizeFDiagCursor);
        $this->rm = $this->createSel("rm", Qt::SizeHorCursor);
        $this->tm = $this->createSel("tm", Qt::SizeVerCursor);
        $this->bm = $this->createSel("bm", Qt::SizeVerCursor);
        
        $this->updateSels();
    }

    public function __destruct()
    {
        $this->lt->free();
        $this->lb->free();
        $this->lm->free();
        $this->rt->free();
        $this->rb->free();
        $this->rm->free();
        $this->tm->free();
        $this->bm->free();
    }

    public function createSel($selName, $cursor)
    {
        $sel = new QLabel($this->container);
        $sel->setCursor($cursor);
        $sel->objectName = "___pq_creator_sizectrl_${selName}_";
        
        $sel->resize($this->size, $this->size);
        $sel->styleSheet = $this->styleSheet;
        
        if(strpos($this->selobj->disabledSels, $selName) === false) {
            $sel->enableResize = true;
        }
        
        $sel->setPHPEventListener($this, eventListener);
        $sel->addPHPEventListenerType(QEvent::MouseButtonPress);
        $sel->addPHPEventListenerType(QEvent::MouseButtonRelease);
        $sel->addPHPEventListenerType(QEvent::MouseMove);
        
        $sel->show();
        
        return $sel;
    }

    public function unmovable($sel)
    {
        $sel->enableResize = false;
        $sel->setCursor(Qt::ArrowCursor);
    }

    public function eventListener($sender, $event)
    {
        if (!$sender->enableResize) return true;
        switch ($event->type) {
        case QEvent::MouseButtonPress:
            $this->startResize($sender, $event->x, $event->y, $event->globalX, $event->globalY, $event->button);
            return true;

        case QEvent::MouseMove:
            $this->resize($sender, $event->x, $event->y, $event->globalX, $event->globalY);
            return true;
            
            
        case QEvent::MouseButtonRelease:
            return true;
        }
    }

    public function startResize($sender, $x, $y, $globalX, $globalY, $button)
    {
        $this->startx = $globalX;
        $this->starty = $globalY;
        $this->preWidth = $this->selobj->width;
        $this->preHeight = $this->selobj->height;
        $this->preX = $this->selobj->x;
        $this->preY = $this->selobj->y;
    }

    public function resize($sender, $x, $y, $globalX, $globalY)
    {
        $newx = $globalX - $this->startx;
        $newy = $globalY - $this->starty;
        $cursor = $sender->cursor;
        $selname = $sender->objectName;
        
        if ($cursor == Qt::SizeHorCursor || $cursor == Qt::SizeFDiagCursor || $cursor == Qt::SizeBDiagCursor) {
            if ($selname == "___pq_creator_sizectrl_lm_" || $selname == "___pq_creator_sizectrl_lt_" || $selname == "___pq_creator_sizectrl_lb_") {
                $this->preX+= $newx;
                if (abs($this->selobj->x - $this->preX) >= $this->gridSize) {
                    $newObjX = floor($this->preX / $this->gridSize) * $this->gridSize;
                    $this->selobj->width+= $this->selobj->x - $newObjX;
                    $this->selobj->x = $newObjX;
                }
            }
            else
            if ($selname == "___pq_creator_sizectrl_rm_" || $selname == "___pq_creator_sizectrl_rt_" || $selname == "___pq_creator_sizectrl_rb_") {
                $this->preWidth+= $newx;
                if (abs($this->selobj->width - $this->preWidth) >= $this->gridSize) {
                    $this->selobj->width = floor($this->preWidth / $this->gridSize) * $this->gridSize;
                }
            }
        }

        if ($cursor == Qt::SizeVerCursor || $cursor == Qt::SizeFDiagCursor || $cursor == Qt::SizeBDiagCursor) {
            if ($selname == "___pq_creator_sizectrl_tm_" || $selname == "___pq_creator_sizectrl_lt_" || $selname == "___pq_creator_sizectrl_rt_") {
                $this->preY+= $newy;
                if (abs($this->selobj->y - $this->preY) >= $this->gridSize) {
                    $newObjY = floor($this->preY / $this->gridSize) * $this->gridSize;
                    $this->selobj->height+= $this->selobj->y - $newObjY;
                    $this->selobj->y = $newObjY;
                }
            }
            else
            if ($selname == "___pq_creator_sizectrl_bm_" || $selname == "___pq_creator_sizectrl_lb_" || $selname == "___pq_creator_sizectrl_rb_") {
                $this->preHeight+= $newy;
                if (abs($this->selobj->height - $this->preHeight) >= $this->gridSize) {
                    $this->selobj->height = floor($this->preHeight / $this->gridSize) * $this->gridSize;
                }
            }
        }

        $this->startx = &$globalX;
        $this->starty = &$globalY;
        $this->updateSels();
        
        if ($this->codegen != null) {
            $this->codegen->updateCode();
        }
    }

    public function updateSels()
    {
        $object = $this->selobj;
        $size = $this->size;
        
        $this->lt->move($object->x - $size / 2 + $this->offset, $object->y - $size / 2 + $this->offset);
        $this->lb->move($object->x - $size / 2 + $this->offset, $object->y + $object->height - $size / 2 - $this->offset);
        $this->lm->move($object->x - $size / 2 + $this->offset, $object->y + $object->height / 2 - $size / 2);
        $this->rt->move($object->x + $object->width - $size / 2 - $this->offset, $object->y - $size / 2 + $this->offset);
        $this->rb->move($object->x + $object->width - $size / 2 - $this->offset, $object->y + $object->height - $size / 2 - $this->offset);
        $this->rm->move($object->x + $object->width - $size / 2 - $this->offset, $object->y + $object->height / 2 - $size / 2);
        $this->tm->move($object->x + $object->width / 2 - $size / 2, $object->y - $size / 2 + $this->offset);
        $this->bm->move($object->x + $object->width / 2 - $size / 2, $object->y + $object->height - $size / 2 - $this->offset);
    }
}
