<?php
global $G_TMP_MENU;
global $RBAC;

if ($RBAC->userCanAccess('PS_AZUREAD') == 1) {
    $G_TMP_MENU->AddIdRawOption("ID_AZUREAD_MENU_01", "../psAzureAD/azureADSettings", "Azure Active Directory", "", "", "settings");
}
?>