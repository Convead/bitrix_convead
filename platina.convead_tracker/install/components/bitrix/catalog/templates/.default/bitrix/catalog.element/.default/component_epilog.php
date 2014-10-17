<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(CModule::IncludeModule('platina.convead_tracker'))
	cConveadTracker::productView($arResult, CUser::GetID());

?>