<?php

set_tr_lang('ru');

$pq_globals = new QObject;
$pq_globals->objectName = '___pq_globals_object_';
$pq_globals->iconpath = __DIR__ . '/core/design/faenza-icons/';

require_once('core/design/Designer.php');
require_once('core/design/ProjectsWindow.php');
$projects = new ProjectsWindow;
$projects->show();
