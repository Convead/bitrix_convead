<?php
CModule::IncludeModule("platina.conveadtracker");

$arClasses = array(
  'ConveadTracker'=>'classes/ConveadTracker.php',
  'ConveadApi'=>'classes/ConveadApi.php',
  'cConveadTracker'=>'classes/cConveadTracker.php',
);

CModule::AddAutoloadClasses("platina.conveadtracker", $arClasses);

?>