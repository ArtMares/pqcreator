<?php

class PQCreatorLogo extends QLabel {
    private $timer;
    private $frame;
    private $frames;
    private $screenGeometry;
    
    private $labelVersion;
    private $logoPaw1;
    private $logoPaw2;
    private $logoPaw3;
    private $logoPaw4;
    private $logoPaw5;
    
    public $canBeClosed;
    
    public function __construct($version) {
        parent::__construct();
        
        /* Init */
        $this->frame = 0;
        $this->frames = 150;
        $this->canBeClosed = true;
        
        $this->windowFlags = Qt::Tool
                            | Qt::WindowStaysOnTopHint
                            | Qt::FramelessWindowHint;
                            
        $this->setAttribute(Qt::WA_TranslucentBackground);
        $this->setAttribute(Qt::WA_DeleteOnClose);
        $this->setAttribute(Qt::WA_QuitOnClose);
        
        $this->resize(256, 256);
        
        $logoPath = dirname(__FILE__);
        
        $logoCircle = new QLabel($this);
        $logoCircle->resize(256, 256);
        $logoCircle->icon = $logoPath . '/data/logo-circle-256.png';
        
        $this->logoPaw2 = $this->createPaw($logoPath . '/data/paw-256-2.png', $desktopWidth, $desktopHeight);
        $this->logoPaw3 = $this->createPaw($logoPath . '/data/paw-256-3.png', $desktopWidth, $desktopHeight);
        $this->logoPaw4 = $this->createPaw($logoPath . '/data/paw-256-4.png', $desktopWidth, $desktopHeight);
        $this->logoPaw5 = $this->createPaw($logoPath . '/data/paw-256-5.png', $desktopWidth, $desktopHeight);
        $this->logoPaw1 = $this->createPaw($logoPath . '/data/paw-256-1.png', $desktopWidth, $desktopHeight);
        
        
        $this->labelVersion = new QLabel($this);
        $this->labelVersion->text = 'Version: ' . $version;
        $this->labelVersion->styleSheet = 'font-size:10px; color:#002040; qproperty-alignment:AlignCenter;';
        
        /* Timer */
        $this->timer = new QTimer(12);
        $this->timer->running = false;
        $this->timer->onTimer = function() {
            $this->logoPaw1->y = -$this->screenGeometry["height"]/2 
                                    + ($this->bounceOut($this->y+$this->screenGeometry["height"]/2, 
                                                        $this->frame/$this->frames));
        
            if($this->frame > 10) 
                $this->logoPaw5->y = -$this->screenGeometry["height"]/2 
                                        + ($this->bounceOut($this->y+$this->screenGeometry["height"]/2, 
                                                            ($this->frame - 10)/$this->frames ));
            
            if($this->frame > 20) 
                $this->logoPaw4->y = -$this->screenGeometry["height"]/2 
                                        + ($this->bounceOut($this->y+$this->screenGeometry["height"]/2, 
                                                            ($this->frame - 20)/$this->frames ));
            
            if($this->frame > 30) 
                $this->logoPaw3->y = -$this->screenGeometry["height"]/2 
                                        + ($this->bounceOut($this->y+$this->screenGeometry["height"]/2,
                                                            ($this->frame - 30)/$this->frames));
                                        
            if($this->frame > 40) 
                $this->logoPaw2->y = -$this->screenGeometry["height"]/2 
                                        + ($this->bounceOut($this->y+$this->screenGeometry["height"]/2, 
                                                            ($this->frame - 40)/$this->frames));
            
            if($this->frame > 220 
                && $this->canBeClosed) {
                $this->timer->stop();
                $this->timer->free();
                $this->logoPaw1->close();
                $this->logoPaw2->close();
                $this->logoPaw3->close();
                $this->logoPaw4->close();
                $this->logoPaw5->close();
                $this->close();
            }
            
            $this->frame++;
        };
    }
    
    public function createPaw($icon, $desktopWidth, $desktopHeight) {
        $paw = new QLabel;
        $paw->resize(256, 256);
        $paw->icon = $icon;
        
        $paw->windowFlags = Qt::Tool
                            | Qt::WindowStaysOnTopHint
                            | Qt::FramelessWindowHint;
                            
        $paw->setAttribute(Qt::WA_TranslucentBackground);
        $paw->setAttribute(Qt::WA_DeleteOnClose);
        $paw->setAttribute(Qt::WA_QuitOnClose);
        
        return $paw;
    }
    
    public function show() {
        parent::show();
        $desktop = new QDesktopWidget;
        $screenNumber = $desktop->screenNumber($this);
        
        $this->screenGeometry = $desktop->screenGeometry($screenNumber);
        
        $this->logoPaw1->move($this->x, -$this->screenGeometry["height"]/2);
        $this->logoPaw1->show();
        
        $this->logoPaw2->move($this->x, -$this->screenGeometry["height"]/2);
        $this->logoPaw2->show();
        
        $this->logoPaw3->move($this->x, -$this->screenGeometry["height"]/2);
        $this->logoPaw3->show();
        
        $this->logoPaw4->move($this->x, -$this->screenGeometry["height"]/2);
        $this->logoPaw4->show();
        
        $this->logoPaw5->move($this->x, -$this->screenGeometry["height"]/2);
        $this->logoPaw5->show();
        
        
        $this->timer->start();
        
        $this->labelVersion->move($this->width/2 - $this->labelVersion->width/2, 210);
        return qApp::exec();
    }
  
    public function bounceOut($d, $n) {
        $s = 7.5625;
        $p = 2.75;
        $l;
        if ($n < (1 / $p)) {
            $l = $s * $n * $n;
        } else {
            if ($n < (2 / $p)) {
                $n -= (1.5 / $p);
                $l = $s * $n * $n + 0.75;
            } else {
                if ($n < (2.5 / $p)) {
                    $n -= (2.25 / $p);
                    $l = $s * $n * $n + 0.9375;
                } else {
                    $n -= (2.625 / $p);
                    $l = $s * $n * $n + 0.984375;
                }
            }
        }
        
        $r = $d*$l;
        
        return $r >= $d ? $d : $r;
    }
}
