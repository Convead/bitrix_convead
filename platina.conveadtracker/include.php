<?php
CModule::IncludeModule("platina.conveadtracker");

$arClasses=array(
  'ConveadTracker'=>'classes/ConveadTracker.php',
  'ConveadApi'=>'classes/ConveadTracker.php',
  'ConveadBrowser'=>'classes/ConveadTracker.php',
  'cConveadTracker'=>'classes/cConveadTracker.php',
);

CModule::AddAutoloadClasses("platina.conveadtracker",$arClasses);

?>