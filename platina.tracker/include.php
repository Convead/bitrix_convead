<?php
CModule::IncludeModule("platina.tracker");

$arClasses=array(
    'cConveadTracker'=>'classes/cConveadTracker.php',
    'ConveadTracker'=>'classes/ConveadTracker.php',
);

CModule::AddAutoloadClasses("platina.tracker",$arClasses);

?>