<?php

/**
 * -------------------------------------------------------------------------
 * SCCM plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of SCCM.
 *
 * SCCM is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * SCCM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SCCM. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @author    François Legastelois
 * @copyright Copyright (C) 2014-2023 by SCCM plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/sccm
 * -------------------------------------------------------------------------
 */

use Glpi\Inventory\Inventory;
use Glpi\Inventory\Request;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

Session::haveRight("config", UPDATE);

Html::header(__('SCCM - TEST', 'sccm'),
             $_SERVER["PHP_SELF"],
             "plugins",
             "sccm",
             "Test");

echo "<div class='center spaced'>";
echo "<form method=\"POST\" action=\"".$_SERVER["PHP_SELF"]."?task=test\">";
echo '<input type="hidden" name="go_test" />';
echo "<table class='tab_cadrehov' style='width:20%;'>";
echo "<tr class='tab_bg_1'><td colspan='2' class='center b'>";
   echo '<select name="type_test">';
      echo '<option value="view">Voir HTML</option>';
      echo '<option value="add">Injecter PC</option>';
      echo '<option value="nbPcToInject">Nombre de PC à injecter</option>';
      echo '<option value="viewList">Liste des PC à injecter</option>';
   echo '</select>';
echo "</td></tr>";
echo "<tr class='tab_bg_1'>";
echo "<td class='center b'>";
   echo 'Limit : <input type="text" name="limit" size="10" />';
echo "</td>";
echo "<td class='center b'>";
   echo 'Where : <input type="text" name="where" size="10" />';
echo "</td>";
echo "</tr>";
echo "<tr class='tab_bg_1'><td colspan='2' class='center b'>";
   echo '<input type="submit" class="submit" value="Lancer le test" name="submit" />';
echo "</td></tr>";
echo "</table>";
Html::closeForm();
echo "</div>";


echo "<div class='center spaced'>";
echo "<table class='tab_glpi'>";
echo "<tr class='tab_bg_1'><td class='center b'>";
if (isset($_POST["go_test"])) {

   $limit = isset($_POST['limit']) ? $_POST['limit'] : 99999999;
   $where = isset($_POST['where']) ? $_POST['where'] : 0;

   switch ($_POST["type_test"]) {
      case 'view'       : testViewHtml($limit, $where);   break;
      case 'add'        : testAdd($where);            break;
      case 'nbPcToInject' : nbPcToInject();           break;
      case 'viewList' : viewList();             break;
   }
}
echo "</td></tr>";
echo "</table>";
echo "</div>";

Html::footer();


function viewList() {
   global $PluginSccmSccm;

   $PluginSccmSccm->getDevices();

   echo "<table class='tab_glpi'>";
   foreach ($PluginSccmSccm->devices as $device_values) {
      echo "<tr><td>".$device_values['MD-SystemName']."</td></tr>";
   }
   echo "</table>";
}

function nbPcToInject() {
   global $PluginSccmSccm;

   $PluginSccmSccm->getDevices();

   echo count($PluginSccmSccm->devices);
}

function testViewHtml($limit, $where) {
   global $PluginSccmSccm;

   $PluginSccmSccm->getDevices($where);

   foreach ($PluginSccmSccm->devices as $device_values) {
      $PluginSccmSccmxml = new PluginSccmSccmxml($device_values);

      $PluginSccmSccmxml->setAccessLog();
      $PluginSccmSccmxml->setAccountInfos();
      $PluginSccmSccmxml->setHardware();
      $PluginSccmSccmxml->setOS();
      $PluginSccmSccmxml->setBios();
      $PluginSccmSccmxml->setProcessors();
      $PluginSccmSccmxml->setSoftwares();
      $PluginSccmSccmxml->setUsers();
      $PluginSccmSccmxml->setNetworks();
      $PluginSccmSccmxml->setStorages();

      $SXML = $PluginSccmSccmxml->sxml;

      Html::printCleanArray($PluginSccmSccmxml->object2array($SXML));
   }
}

function testAdd($where) {
   global $PluginSccmSccm;

   $PluginSccmSccm->getDevices($where);

   foreach ($PluginSccmSccm->devices as $device_values) {
      $PluginSccmSccmxml = new PluginSccmSccmxml($device_values);

      $PluginSccmSccmxml->setAccessLog();
      $PluginSccmSccmxml->setAccountInfos();
      $PluginSccmSccmxml->setHardware();
      $PluginSccmSccmxml->setOS();
      $PluginSccmSccmxml->setBios();
      $PluginSccmSccmxml->setProcessors();
      $PluginSccmSccmxml->setSoftwares();
      $PluginSccmSccmxml->setUsers();
      $PluginSccmSccmxml->setNetworks();
      $PluginSccmSccmxml->setStorages();

      $SXML = $PluginSccmSccmxml->sxml;
      $invlogs = $searchinvlog = new PluginSccmInventoryLog();
      try {
         $inventory = new Inventory();
         $inventory->setData($SXML, Request::XML_MODE);
         Toolbox::logInFile('sccm', "Collect completed \n", true);
         $inventory->doInventory();
         Toolbox::logInFile('sccm', "Inventory done \n", true);
         $compt = $inventory->getItem();
         $computerid = $compt->getID();
         // Add or update this on inventorylogs
         $fields = [
            'name'         => $compt->getName(),
            'computers_id' => $computerid,
            'state'        => 'sccm-done',
            'error'        => '',
            'date_mod'     => date('Y-m-d H:i:s'),
         ];
         if ($searchinvlog->getFromDBByCrit(['name' => $SXML->xpath('//NAME')[0]->__toString()])){
            $invlogs->update(['id' => $searchinvlog->getID()] + $fields);
         } else {
            $invlogs->add($fields);
         }
         echo "Test add function succesfull";
      } catch (Throwable $e) {
         if (!empty($inventory->getErrors())) {
            $error = print_r($inventory->getErrors(), true);
         } else {
            $error = $e->getMessage();
         }
         $computername = $SXML->xpath('//NAME')[0]->__toString();
         $fields = [
            'name'         => $computername,
            'computers_id' => null,
            'state'        => 'sccm-fail',
            'error'        => $error,
            'date_mod'         => date('Y-m-d H:i:s'),
         ];
         if ($searchinvlog->getFromDBByCrit(['name' => $computername])){
            $invlogs->update(['id' => $searchinvlog->getID()] + $fields);
         } else {
            $invlogs->add($fields);
         }
         echo "test add function failed";
      }
   }

}
