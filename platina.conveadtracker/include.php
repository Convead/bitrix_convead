<?php
CModule::IncludeModule("platina.conveadtracker");

$arClasses=array(
    'cConveadTracker'=>'classes/cConveadTracker.php',
    'ConveadTracker'=>'classes/ConveadTracker.php',
);

CModule::AddAutoloadClasses("platina.conveadtracker",$arClasses);

?>