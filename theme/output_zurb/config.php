<?php

$THEME->name = 'output_zurb';
$THEME->parents = array('base');

$THEME->doctype = 'html5';
$THEME->sheets = array('normalize', 'foundation');
$THEME->javascripts = array('modernizr');
$THEME->javascripts_footer = array('jquery', 'fastclick', 'foundation.min', 'init');

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
