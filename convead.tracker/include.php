<?php
CModule::IncludeModule("convead.tracker");

$arClasses=array(
    'cConveadTracker'=>'classes/cConveadTracker.php',
    'ConveadTracker'=>'classes/ConveadTracker.php',
);

CModule::AddAutoloadClasses("convead.tracker",$arClasses);

?>