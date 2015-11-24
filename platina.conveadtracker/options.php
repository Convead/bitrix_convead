<?php

use Bitrix\Main\Localization\Loc;

if (!$USER->IsAdmin()) {
	return;
}

define('ADMIN_MODULE_NAME', 'platina.conveadtracker');


if ($APPLICATION->GetGroupRight(ADMIN_MODULE_NAME) >= 'R') {

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
		
		} else {

			$is_saved = false;

			$rsSites = CSite::GetList($by="sort", $order="desc");
			while ($arSite = $rsSites->Fetch()) {

				$tracker_code_name = "tracker_code_".$arSite['ID'];
				$phone_code_name = "phone_code_".$arSite['ID'];

				if (!empty($_REQUEST[$tracker_code_name])) {
					COption::SetOptionString(
						ADMIN_MODULE_NAME,
						$tracker_code_name,
						$_REQUEST[$tracker_code_name],
						Loc::getMessage("TRACKER_CODE")
					);
					$is_saved = true;
				} else {
					CAdminMessage::ShowMessage(Loc::getMessage("ERROR_TRACKER_CODE_EMPTY"));
				}

				if (!empty($_REQUEST[$phone_code_name])) {
					COption::SetOptionString(
						ADMIN_MODULE_NAME,
						$phone_code_name,
						$_REQUEST[$phone_code_name],
						Loc::getMessage("PHONE_CODE")
					);
					$is_saved = true;
				}

			}

			if ($is_saved) CAdminMessage::ShowMessage(array("MESSAGE" => Loc::getMessage("OPTIONS_SAVED"), "TYPE" => "OK"));

		}

	}

	$tabControl->Begin();

?>

	<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($mid) ?>&amp;lang=<?= LANGUAGE_ID ?>">

		<?php if (!function_exists('curl_exec')): ?>
			<div class="adm-info-message-wrap">
				<div class="adm-info-message">
					<span class="required"><?= Loc::getMessage("CURL_DISABLED_MESSAGE") ?></span><br />
					<?= Loc::getMessage("HOSTING_SUPPORT") ?>
				</div>
			</div>
		<?php endif; ?>

		<? $tabControl->BeginNextTab(); ?>

		<?php

		$single_tracker_code = COption::GetOptionString(ADMIN_MODULE_NAME, "tracker_code", '');
		$single_phone_code = COption::GetOptionString(ADMIN_MODULE_NAME, "phone_code", '');

		$rsSites = CSite::GetList($by="sort", $order="desc");
		while ($arSite = $rsSites->Fetch()):
			$tracker_code_name = "tracker_code_".$arSite['ID'];
			$phone_code_name = "phone_code_".$arSite['ID'];

			$domain_tracker_code = COption::GetOptionString(ADMIN_MODULE_NAME, $tracker_code_name, '');
			$domain_phone_code = COption::GetOptionString(ADMIN_MODULE_NAME, $phone_code_name, '');
		?>

			<tr class="heading">
				<td colspan="2"><b><?=$arSite['NAME']?></b></td>
			</tr>


			<tr>
				<td width="40%">
					<label for="<?=$tracker_code_name?>"><?= Loc::getMessage("TRACKER_CODE") ?>:</label>
				<td width="60%">
					<input type="text" size="50"  name="<?=$tracker_code_name?>" value="<?= htmlspecialcharsbx( $domain_tracker_code ? $domain_tracker_code : $single_tracker_code ) ?>">
				</td>
			</tr>

			<tr>
				<td width="40%">
					<label for="<?=$phone_code_name?>"><?= Loc::getMessage("PHONE_CODE") ?>:</label>
				<td width="60%">
					<input type="text" size="50"  name="<?=$phone_code_name?>" value="<?= htmlspecialcharsbx( $domain_phone_code ? $domain_phone_code : $single_phone_code ) ?>">
				</td>
			</tr>

		<?php endwhile; ?>

		<? $tabControl->Buttons(); ?>

		<input type="submit" name="save" value="<?= GetMessage("MAIN_SAVE") ?>"
			   title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>" class="adm-btn-save">
		<input type="submit" name="restore" title="<?= GetMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
			   OnClick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
			   value="<?= GetMessage("MAIN_RESTORE_DEFAULTS") ?>">
		<?= bitrix_sessid_post(); ?>

		<? $tabControl->End(); ?>

	</form>

<?php

}

?>