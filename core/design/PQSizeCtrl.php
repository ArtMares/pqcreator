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
		
		$this->lt = $this->create_sel("___pq_creator__lt_", Qt::SizeFDiagCursor);
		$this->lb = $this->create_sel("___pq_creator__lb_", Qt::SizeBDiagCursor);
		$this->lm = $this->create_sel("___pq_creator__lm_", Qt::SizeHorCursor);
		$this->rt = $this->create_sel("___pq_creator__rt_", Qt::SizeBDiagCursor);
		$this->rb = $this->create_sel("___pq_creator__rb_", Qt::SizeFDiagCursor);
		$this->rm = $this->create_sel("___pq_creator__rm_", Qt::SizeHorCursor);
		$this->tm = $this->create_sel("___pq_creator__tm_", Qt::SizeVerCursor);
		$this->bm = $this->create_sel("___pq_creator__bm_", Qt::SizeVerCursor);
		
		$this->selobj = &$object;
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

	public function create_sel($objectName, $cursor)
	{
		$sel = new QLabel($this->container);
		$sel->setCursor($cursor);
		$sel->objectName = $objectName;
		
		$sel->resize($this->size, $this->size);
		$sel->styleSheet = $this->styleSheet;
		$sel->enableResize = true;
		
		$sel->setPHPEventListener($this, eventListener);
		$sel->addPHPEventListenerType(QEvent::MouseButtonPress);
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
			$this->start_resize($sender, $event->x, $event->y, $event->globalX, $event->globalY, $event->button);
			return true;
			break;

		case QEvent::MouseMove:
			$this->resize($sender, $event->x, $event->y, $event->globalX, $event->globalY);
			return true;
			break;
		}
	}

	public function start_resize($sender, $x, $y, $globalX, $globalY, $button)
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
		$width = $this->selobj->width;
		$height = $this->selobj->height;
		
		if ($cursor == Qt::SizeHorCursor || $cursor == Qt::SizeFDiagCursor || $cursor == Qt::SizeBDiagCursor) {
			if ($selname == "___pq_creator__lm_" || $selname == "___pq_creator__lt_" || $selname == "___pq_creator__lb_") {
				$this->preX+= $newx;
				if (abs($this->selobj->x - $this->preX) >= $this->gridSize) {
					$newObjX = floor($this->preX / $this->gridSize) * $this->gridSize;
					$this->selobj->width+= $this->selobj->x - $newObjX;
					$this->selobj->x = $newObjX;
				}
			}
			else
			if ($selname == "___pq_creator__rm_" || $selname == "___pq_creator__rt_" || $selname == "___pq_creator__rb_") {
				$this->preWidth+= $newx;
				if (abs($this->selobj->width - $this->preWidth) >= $this->gridSize) {
					$this->selobj->width = floor($this->preWidth / $this->gridSize) * $this->gridSize;
				}
			}
		}

		if ($cursor == Qt::SizeVerCursor || $cursor == Qt::SizeFDiagCursor || $cursor == Qt::SizeBDiagCursor) {
			if ($selname == "___pq_creator__tm_" || $selname == "___pq_creator__lt_" || $selname == "___pq_creator__rt_") {
				$this->preY+= $newy;
				if (abs($this->selobj->y - $this->preY) >= $this->gridSize) {
					$newObjY = floor($this->preY / $this->gridSize) * $this->gridSize;
					$this->selobj->height+= $this->selobj->y - $newObjY;
					$this->selobj->y = $newObjY;
				}
			}
			else
			if ($selname == "___pq_creator__bm_" || $selname == "___pq_creator__lb_" || $selname == "___pq_creator__rb_") {
				$this->preHeight+= $newy;
				if (abs($this->selobj->height - $this->preHeight) >= $this->gridSize) {
					$this->selobj->height = floor($this->preHeight / $this->gridSize) * $this->gridSize;
				}
			}
		}


		$this->startx = & $globalX;
		$this->starty = & $globalY;
		$this->updateSels();
		
		if ($this->codegen != null) {
			$this->codegen->update_code();
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
