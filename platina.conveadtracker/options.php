<?php

use Bitrix\Main\Localization\Loc;

if (!$USER->IsAdmin()) {
	return;
}

define('ADMIN_MODULE_NAME', 'platina.conveadtracker');

$RIGHT = $APPLICATION->GetGroupRight(ADMIN_MODULE_NAME);
if ($RIGHT >= 'R') :
	Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/options.php");
	Loc::loadMessages(__FILE__);

	$tabControl = new CAdminTabControl("tabControl", array(
		array(
			"DIV" => "edit1",
			"TAB" => GetMessage("MAIN_TAB_SET"),
			"TITLE" => GetMessage("MAIN_TAB_TITLE_SET")
		),
	));

	if ((!empty($save) || !empty($restore)) && $REQUEST_METHOD == "POST" && check_bitrix_sessid()) {
		if (!empty($restore)) {
			COption::RemoveOption(ADMIN_MODULE_NAME);
			CAdminMessage::ShowMessage(array("MESSAGE" => Loc::getMessage("OPTIONS_RESTORED"), "TYPE" => "OK"));
		} elseif (
			!empty($_REQUEST["tracker_code"])
			&& $_REQUEST["tracker_code"] != ""
		) {


			COption::SetOptionString(
				ADMIN_MODULE_NAME,
				"tracker_code",
				$_REQUEST["tracker_code"],
				Loc::getMessage("TRACKER_CODE")
			);
			CAdminMessage::ShowMessage(array("MESSAGE" => Loc::getMessage("OPTIONS_SAVED"), "TYPE" => "OK"));

		} else {
			CAdminMessage::ShowMessage(Loc::getMessage("ERROR_TRACKER_CODE_EMPTY"));
		}

		if (
			!empty($_REQUEST["phone_code"])
			&& $_REQUEST["phone_code"] != ""
		) {


			COption::SetOptionString(
				ADMIN_MODULE_NAME,
				"phone_code",
				$_REQUEST["phone_code"],
				Loc::getMessage("PHONE_CODE")
			);
			CAdminMessage::ShowMessage(array("MESSAGE" => Loc::getMessage("OPTIONS_SAVED"), "TYPE" => "OK"));

		}

	}

	$tabControl->Begin();
	?>

	<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($mid) ?>&amp;lang=<?= LANGUAGE_ID ?>">
		<? $tabControl->BeginNextTab(); ?>


		<tr>
			<td width="40%">
				<label for="tracker_code"><?= Loc::getMessage("TRACKER_CODE") ?>:</label>
			<td width="60%">
				<input type="text" size="50"  name="tracker_code"
					   value="<?= htmlspecialcharsbx(
						   COption::GetOptionString(ADMIN_MODULE_NAME, "tracker_code", '')
					   ) ?>">
			</td>
		</tr>

		<tr>
			<td width="40%">
				<label for="phone_code"><?= Loc::getMessage("PHONE_CODE") ?>:</label>
			<td width="60%">
				<input type="text" size="50"  name="phone_code"
					   value="<?= htmlspecialcharsbx(
						   COption::GetOptionString(ADMIN_MODULE_NAME, "phone_code", '')
					   ) ?>">
			</td>
		</tr>
		<? $tabControl->Buttons(); ?>
		<input type="submit" name="save" value="<?= GetMessage("MAIN_SAVE") ?>"
			   title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>" class="adm-btn-save">
		<input type="submit" name="restore" title="<?= GetMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
			   OnClick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
			   value="<?= GetMessage("MAIN_RESTORE_DEFAULTS") ?>">
		<?= bitrix_sessid_post(); ?>
		<? $tabControl->End(); ?>
	</form>
<?endif;?>