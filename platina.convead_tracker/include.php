<?php
CModule::IncludeModule("platina.convead_tracker");

$arClasses=array(
    'cConveadTracker'=>'classes/cConveadTracker.php',
    'ConveadTracker'=>'classes/ConveadTracker.php',
);

CModule::AddAutoloadClasses("platina.convead_tracker",$arClasses);

?>