<?php

header('content-type:text/css');

require '../../less/lessc.inc.php';

$less = new lessc;

echo $less->compileFile(dirname(__FILE__).'/ecowebscore.less');