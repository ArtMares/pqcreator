<?php

function ___pq_prepare_path_to_win($path) {
  return str_replace('/', '\\', $path); // windows path
}

function ___pq_prepare_path($winpath) {
  return str_replace('\\', '/', $winpath); // normal path
}

set_tr_lang('ru');

$pq_globals = new QObject;
$pq_globals->objectName = '___pq_globals_object_';
$pq_globals->iconpath = __DIR__ . '/core/design/faenza-icons/';
$pq_globals->qticonpath = __DIR__ . '/core/design/qt-icons/';
$pq_globals->exepath = __DIR__;

// Components
$pq_globals->csPath = __DIR__ . '/core/components/';
$pq_globals->csName = 'component.php';
$pq_globals->csProperties = 'properties.php';
$pq_globals->csIcon = 'icon.png';


require_once('core/design/PQDesigner.php');
require_once('core/design/ProjectsWindow.php');
$projects = new ProjectsWindow;
$projects->show();
