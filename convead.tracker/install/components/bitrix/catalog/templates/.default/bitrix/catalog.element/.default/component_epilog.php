<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(CModule::IncludeModule('convead.tracker'))
	cConveadTracker::productView($arResult, CUser::GetID());

?>