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

use Glpi\Application\View\TemplateRenderer;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSccmConfig extends CommonDBTM {

   static private $_instance = null;

   static function canCreate() {
      return Session::haveRight('config', UPDATE);
   }

   static function canUpdate() {
      return Session::haveRight('config', UPDATE);
   }

   static function canView() {
      return Session::haveRight('config', UPDATE);
   }

   static function getTypeName($nb = 0) {
      return __("Setup", "sccm");
   }

   function getName($with_comment = 0) {
      return __("Configuration", "sccm");
   }

   public static function getIcon()
   {
      return 'ti ti-tool';
   }

   public static function getSearchURL($full = true)
   {
      return PluginSccmMenu::getSearchURL($full);
   }

   static function getInstance() {

      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }


   function prepareInputForUpdate($input) {
      if (isset($input["sccmdb_password"]) AND !empty($input["sccmdb_password"])) {
         $input["sccmdb_password"] = (new GLPIKey())->encrypt($input["sccmdb_password"]);
      }

      if (array_key_exists('inventory_server_url', $input) && !empty($input['inventory_server_url'])) {
          $input['inventory_server_url'] = trim($input['inventory_server_url'], '/ ');
      }

      return $input;
   }

   static function install(Migration $migration) {
      global $CFG_GLPI, $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = 'glpi_plugin_sccm_configs';

      if (!$DB->tableExists($table)) {

         $query = "CREATE TABLE `". $table."`(
                     `id` int {$default_key_sign} NOT NULL,
                     `sccmdb_host` VARCHAR(255) NULL,
                     `sccmdb_dbname` VARCHAR(255) NULL,
                     `sccmdb_user` VARCHAR(255) NULL,
                     `sccmdb_password` VARCHAR(255) NULL,
                     `inventory_server_url` VARCHAR(255) NULL,
                     `active_sync` tinyint NOT NULL default '0',
                     `verify_ssl_cert` tinyint NOT NULL default '0',
                     `use_auth_ntlm` tinyint NOT NULL default '0',
                     `unrestricted_auth` tinyint NOT NULL default '0',
                     `use_auth_info` tinyint NOT NULL default '0',
                     `auth_info` VARCHAR(255) NULL,
                     `is_password_sodium_encrypted` tinyint NOT NULL default '1',
                     `use_lasthwscan` tinyint NOT NULL default '0',
                     `date_mod` timestamp NULL default NULL,
                     `comment` text,
                     PRIMARY KEY  (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

         $DB->queryOrDie($query, __("Error when using glpi_plugin_sccm_configs table.", "sccm")
                              . "<br />".$DB->error());

         $query = "INSERT INTO `$table`
                         (id, date_mod, sccmdb_host, sccmdb_dbname,
                           sccmdb_user, sccmdb_password, inventory_server_url)
                   VALUES (1, NOW(), 'srv_sccm','bdd_sccm','user_sccm','',
                           NULL)";

         $DB->queryOrDie($query, __("Error when using glpi_plugin_sccm_configs table.", "sccm")
                                 . "<br />" . $DB->error());

      } else {

         if (!$DB->fieldExists($table, 'verify_ssl_cert')) {
            $migration->addField("glpi_plugin_sccm_configs", "verify_ssl_cert", "tinyint NOT NULL default '0'");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }

         if (!$DB->fieldExists($table, 'use_auth_ntlm')) {
            $migration->addField("glpi_plugin_sccm_configs", "use_auth_ntlm", "tinyint NOT NULL default '0'");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }

         if (!$DB->fieldExists($table, 'unrestricted_auth')) {
            $migration->addField("glpi_plugin_sccm_configs", "unrestricted_auth", "tinyint NOT NULL default '0'");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }

         if (!$DB->fieldExists($table, 'use_auth_info')) {
            $migration->addField("glpi_plugin_sccm_configs", "use_auth_info", "tinyint NOT NULL default '0'");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }

         if (!$DB->fieldExists($table, 'auth_info')) {
            $migration->addField("glpi_plugin_sccm_configs", "auth_info", "varchar(255)");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }

         if (!$DB->fieldExists($table, 'is_password_sodium_encrypted')) {
            $config = self::getInstance();
            if (!empty($config->fields['sccmdb_password'])) {
               $key = new GLPIKey();
               $migration->addPostQuery(
                  $DB->buildUpdate(
                     'glpi_plugin_sccm_configs',
                     [
                        'sccmdb_password' => $key->encrypt(
                           $key->decryptUsingLegacyKey(
                              $config->fields['sccmdb_password']
                           )
                        )
                     ],
                     [
                        'id' => 1,
                     ]
                     )
                  );
            }
            $migration->addField("glpi_plugin_sccm_configs", "is_password_sodium_encrypted", "tinyint NOT NULL default '1'");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }

         if (!$DB->fieldExists($table, 'use_lasthwscan')) {
            $migration->addField("glpi_plugin_sccm_configs", "use_lasthwscan", "tinyint NOT NULL default '0'");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }

         if (!$DB->fieldExists($table, 'fusioninventory_url')) {
            $migration->changeField("glpi_plugin_sccm_configs", "fusioninventory_url", "inventory_server_url", "string");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }

         $sccm_config = $DB->request(['FROM' => 'glpi_plugin_sccm_configs'])->current();
         $inventory_server_url = trim($sccm_config['inventory_server_url'] ?? '');
         $url_matches = [];
         if (
            $inventory_server_url !== ''
            && (
                preg_match('/^(?<base_url>.+)\/front\/inventory\.php$/', $inventory_server_url, $url_matches) === 1
                || preg_match('/^(?<base_url>.+)\/(marketplace|plugins)\/(fusioninventory)\//', $inventory_server_url, $url_matches) === 1
            )
         ){
            // Strip script path from base URL.
            $inventory_server_url = $url_matches['base_url'];
            if ($inventory_server_url === $CFG_GLPI['url_base']) {
                $inventory_server_url = '';
            }
            $sccm_config = $DB->update(
               'glpi_plugin_sccm_configs',
               [
                  'inventory_server_url' => $inventory_server_url,
               ],
               [
                  'id' => 1,
               ]
            );
         }

         if (!$DB->fieldExists($table, 'last_crontask_position')) {
            $migration->addField("glpi_plugin_sccm_configs", "last_crontask_position", "int NOT NULL DEFAULT '0'");
            $migration->migrationOneTable('glpi_plugin_sccm_configs');
         }
      }

      return true;
   }


   static function uninstall() {
      global $DB;

      if ($DB->tableExists('glpi_plugin_sccm_configs')) {

         $query = "DROP TABLE `glpi_plugin_sccm_configs`";
         $DB->queryOrDie($query, $DB->error());
      }
      return true;
   }


   static function showConfigForm($item) {
      global $CFG_GLPI;

      $config = self::getInstance();
      $inventory_url = [
         'placeholder'  => $CFG_GLPI['url_base'],
         'value'        => $config->getField('inventory_server_url'),
      ];

      echo TemplateRenderer::getInstance()->display('@sccm/config.html.twig', [
         'item'                  => $config,
         'password'              => Html::entities_deep((new GLPIKey())->decrypt($config->getField('sccmdb_password'))),
         'inventory_server_url'  => $inventory_url
      ]);

      return false;
   }

}
