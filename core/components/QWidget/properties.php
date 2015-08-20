<?php

$r = array();
$r[] = array(
              'title'         => tr('X'),
              'property'      => 'x',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Y'),
              'property'      => 'y',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Size policy'),
              'property'      => 'setSizePolicy(%0,%1)',
              'type'          => 'combo-list',
              'list'          => array(
                                    array(
                                        'title'         => tr('Horisontal size policy'),
                                        'property'      => '%0',
                                        'type'          => 'combo',
                                        'list'          => array(
                                                                array( 'title' => 'Fixed', 'value' => QSizePolicy::Fixed, 'qvalue' => 'QSizePolicy::Fixed' ),
                                                                array( 'title' => 'Minimum', 'value' => QSizePolicy::Minimum, 'qvalue' => 'QSizePolicy::Minimum'),
                                                                array( 'title' => 'Maximum', 'value' => QSizePolicy::Maximum, 'qvalue' => 'QSizePolicy::Maximum'),
                                                                array( 'title' => 'Preferred', 'value' => QSizePolicy::Preferred, 'qvalue' => 'QSizePolicy::Preferred'),
                                                                array( 'title' => 'Expanding', 'value' => QSizePolicy::Expanding, 'qvalue' => 'QSizePolicy::Expanding'),
                                                                array( 'title' => 'Ignored', 'value' => QSizePolicy::Ignored, 'qvalue' => 'QSizePolicy::Ignored')
                                                            ),
                                        'defaultIndex'  => 1
                                        ),
                                        
                                    array(
                                        'title'         => tr('Vertical size policy'),
                                        'property'      => '%1',
                                        'type'          => 'combo',
                                        'list'          => array(
                                                                array( 'title' => 'Fixed', 'value' => QSizePolicy::Fixed, 'qvalue' => 'QSizePolicy::Fixed' ),
                                                                array( 'title' => 'Minimum', 'value' => QSizePolicy::Minimum, 'qvalue' => 'QSizePolicy::Minimum'),
                                                                array( 'title' => 'Maximum', 'value' => QSizePolicy::Maximum, 'qvalue' => 'QSizePolicy::Maximum'),
                                                                array( 'title' => 'Preferred', 'value' => QSizePolicy::Preferred, 'qvalue' => 'QSizePolicy::Preferred'),
                                                                array( 'title' => 'Expanding', 'value' => QSizePolicy::Expanding, 'qvalue' => 'QSizePolicy::Expanding'),
                                                                array( 'title' => 'Ignored', 'value' => QSizePolicy::Ignored, 'qvalue' => 'QSizePolicy::Ignored')
                                                            ),
                                        'defaultIndex'  => 0
                                    )
                                )
              );
              
$r[] = array(
              'title'         => tr('Width'),
              'property'      => 'width',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Height'),
              'property'      => 'height',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Minimum width'),
              'property'      => 'minimumWidth',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Maximum width'),
              'property'      => 'maximumWidth',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Minimum height'),
              'property'      => 'minimumHeight',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Maximum height'),
              'property'      => 'maximumHeight',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Enabled'),
              'property'      => 'enabled',
              'type'          => 'bool',
              'value'         => true
              );
              
$r[] = array(
              'title'         => tr('Visible'),
              'property'      => 'visible',
              'type'          => 'bool',
              'value'         => true
              );
              
$r[] = array(
              'title'         => tr('Tooltip duration'),
              'property'      => 'toolTipDuration',
              'type'          => 'int'
              );
              
$r[] = array(
              'title'         => tr('Tooltip'),
              'property'      => 'toolTip',
              'type'          => 'mixed'
              );
              
$r[] = array(
              'title'         => tr('Stylesheet'),
              'property'      => 'styleSheet',
              'type'          => 'mixed'
              );
              
              
              