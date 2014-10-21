<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(CModule::IncludeModule('platina.conveadtracker'))
	cConveadTracker::productView($arResult, CUser::GetID());

?>