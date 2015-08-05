<?php

class PQSizeCtrl extends QObject {
  public $gridSize = 8;
  
  private $formarea;
  private $selobj;
  private $styleSheet = 'background:#000020; border:1px solid #0000a0;';
  private $size = 6;
  
  public $lt;
  public $lb;
  public $lm;
  public $rt;
  public $rb;
  public $rm;
  public $tm;
  public $bm;
  
  private $startx;
  private $starty;
  
  private $preWidth;
  private $preHeight;
  private $preX;
  private $preY;
  
  private $codegen;
  
  public function __construct(&$codegen, $formarea, $object, $gridSize) {
    parent::__construct();
    $this->gridSize = $gridSize;
    $this->codegen = &$codegen;
    $size = $this->size;
    
    $this->lt = new QLabel($formarea);
    $this->lt->resize($size, $size);
    $this->lt->styleSheet = $this->styleSheet;
    $this->lt->setCursor(Qt::SizeFDiagCursor);
    $this->lt->objectName = "___pq_creator__lt_";
    $this->lt->show();
    
    $this->lb = new QLabel($formarea);
    $this->lb->resize($size, $size);
    $this->lb->styleSheet = $this->styleSheet;
    $this->lb->setCursor(Qt::SizeBDiagCursor);
    $this->lb->objectName = "___pq_creator__lb_";
    $this->lb->show();
    
    $this->lm = new QLabel($formarea);
    $this->lm->resize($size, $size);
    $this->lm->styleSheet = $this->styleSheet;
    $this->lm->setCursor(Qt::SizeHorCursor);
    $this->lm->objectName = "___pq_creator__lm_";
    $this->lm->show();
    
    $this->rt = new QLabel($formarea);
    $this->rt->resize($size, $size);
    $this->rt->styleSheet = $this->styleSheet;
    $this->rt->setCursor(Qt::SizeBDiagCursor);
    $this->rt->objectName = "___pq_creator__rt_";
    $this->rt->show();
    
    $this->rb = new QLabel($formarea);
    $this->rb->resize($size, $size);
    $this->rb->styleSheet = $this->styleSheet;
    $this->rb->setCursor(Qt::SizeFDiagCursor);
    $this->rb->objectName = "___pq_creator__rb_";
    $this->rb->show();
    
    $this->rm = new QLabel($formarea);
    $this->rm->resize($size, $size);
    $this->rm->styleSheet = $this->styleSheet;
    $this->rm->setCursor(Qt::SizeHorCursor);
    $this->rm->objectName = "___pq_creator__rm_";
    $this->rm->show();
    
    $this->tm = new QLabel($formarea);
    $this->tm->resize($size, $size);
    $this->tm->styleSheet = $this->styleSheet;
    $this->tm->setCursor(Qt::SizeVerCursor);
    $this->tm->objectName = "___pq_creator__tm_";
    $this->tm->show();
    
    $this->bm = new QLabel($formarea);
    $this->bm->resize($size, $size);
    $this->bm->styleSheet = $this->styleSheet;
    $this->bm->setCursor(Qt::SizeVerCursor);
    $this->bm->objectName = "___pq_creator__bm_";
    $this->bm->show();
    
    $this->make_movable($this->lt);
    $this->make_movable($this->lm);
    $this->make_movable($this->lb);
    $this->make_movable($this->rt);
    $this->make_movable($this->rb);
    $this->make_movable($this->rm);
    $this->make_movable($this->tm);
    $this->make_movable($this->bm);
    
    $this->formarea = &$formarea;
    $this->selobj = &$object;
    $this->updateSels();
  }
  
  public function __destruct() {
    $this->lt->free();
    $this->lb->free();
    $this->lm->free();
    $this->rt->free();
    $this->rb->free();
    $this->rm->free();
    $this->tm->free();
    $this->bm->free();
  }
  
  public function make_movable($sel) {
    connect($sel, SIGNAL('mousePressed(int,int,int)'), $this, SLOT('start_resize(int,int,int)'));
    connect($sel, SIGNAL('mouseMoved(int,int)'), $this, SLOT('resize(int,int)'));
    $sel->lockParentClassEvents = true;
  }
  
  public function start_resize($sender, $x, $y, $button) {
    $this->startx = &$x;
    $this->starty = &$y;
    $this->preWidth = $this->selobj->width;
    $this->preHeight = $this->selobj->height;
    $this->preX = $this->selobj->x;
    $this->preY = $this->selobj->y;
  }
  
  public function resize($sender, $x, $y) {
    $newx = $x - $this->startx;
    $newy = $y - $this->starty;
    
    $cursor = $sender->cursor;
    $selname = $sender->objectName;
    if($cursor == Qt::SizeHorCursor
        || $cursor == Qt::SizeFDiagCursor
        || $cursor == Qt::SizeBDiagCursor) {
      
      if($selname == "___pq_creator__lm_"
          || $selname == "___pq_creator__lt_"
          || $selname == "___pq_creator__lb_") {
        $this->preX += $newx;
        if(abs($this->selobj->x - $this->preX) >= $this->gridSize) {
          $newObjX = floor( $this->preX / $this->gridSize ) * $this->gridSize;
          $this->selobj->width += $this->selobj->x - $newObjX;
          $this->selobj->x = $newObjX;
        }
      }
      else if($selname == "___pq_creator__rm_"
          || $selname == "___pq_creator__rt_"
          || $selname == "___pq_creator__rb_") {
        $this->preWidth += $newx;
        if(abs($this->selobj->width - $this->preWidth) >= $this->gridSize) {
          $this->selobj->width = floor( $this->preWidth / $this->gridSize ) * $this->gridSize;
        }
      }
    }
    
    if($cursor == Qt::SizeVerCursor
        || $cursor == Qt::SizeFDiagCursor
        || $cursor == Qt::SizeBDiagCursor) {
        
      if($selname == "___pq_creator__tm_"
          || $selname == "___pq_creator__lt_"
          || $selname == "___pq_creator__rt_") {
        $this->preY += $newy;
        if(abs($this->selobj->y - $this->preY) >= $this->gridSize) {
          $newObjY = floor( $this->preY / $this->gridSize ) * $this->gridSize;
          $this->selobj->height += $this->selobj->y - $newObjY;
          $this->selobj->y = $newObjY;
        }
      }
      else if($selname == "___pq_creator__bm_"
          || $selname == "___pq_creator__lb_"
          || $selname == "___pq_creator__rb_") {
        $this->preHeight += $newy;
        if(abs($this->selobj->height - $this->preHeight) >= $this->gridSize) {
          $this->selobj->height = floor( $this->preHeight / $this->gridSize ) * $this->gridSize;
        }
      }
    }
    
    $this->startx = &$x;
    $this->starty = &$y;
    
    $this->updateSels();
    
    if($this->codegen != null) {
      $this->codegen->update_code();
    }
  }
  
  public function updateSels() {
    $object = $this->selobj;
    $size = $this->size;
    
    $this->lt->move($object->x-$size/2, $object->y-$size/2);
    $this->lb->move($object->x-$size/2, $object->y+$object->height-$size/2);
    $this->lm->move($object->x-$size/2, $object->y+$object->height/2-$size/2);
    $this->rt->move($object->x+$object->width-$size/2, $object->y-$size/2);
    $this->rb->move($object->x+$object->width-$size/2, $object->y+$object->height-$size/2);
    $this->rm->move($object->x+$object->width-$size/2, $object->y+$object->height/2-$size/2);
    $this->tm->move($object->x+$object->width/2-$size/2, $object->y-$size/2);
    $this->bm->move($object->x+$object->width/2-$size/2, $object->y+$object->height-$size/2);
  }
}