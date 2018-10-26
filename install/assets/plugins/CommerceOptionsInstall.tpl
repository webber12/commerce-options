//<?php
/**
 * CommerceOptionsInstall
 *
 * Commerce Options installer
 *
 * @category    plugin
 * @author      mnoskov
 * @internal    @events OnWebPageInit,OnManagerPageInit,OnPageNotFound
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

$modx->clearCache('full');

$tableEventnames = $modx->getFullTablename('system_eventnames');
$tablePlugins    = $modx->getFullTablename('site_plugins');
$tableEvents     = $modx->getFullTablename('site_plugin_events');

$events = [
    'OnManagerCommerceOptionsRender',
];

$query  = $modx->db->select('*', $tableEventnames, "`groupname` = 'Commerce'");
$exists = [];

while ($row = $modx->db->getRow($query)) {
    $exists[$row['name']] = $row['id'];
}

foreach ($events as $event) {
    if (!isset($exists[$event])) {
        $modx->db->insert([
            'name'      => $event,
            'service'   => 6,
            'groupname' => 'Commerce',
        ], $tableEventnames);
    }
}

$modx->db->query("
    CREATE TABLE IF NOT EXISTS " . $modx->getFullTablename('commerce_options') . " (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `sort` smallint(5) unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("
    CREATE TABLE IF NOT EXISTS " . $modx->getFullTablename('commerce_option_values') . " (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `option_id` int(10) unsigned NOT NULL,
        `title` varchar(255) NOT NULL,
        `image` text NOT NULL,
        `sort` smallint(5) unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `option_id` (`option_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("
    CREATE TABLE IF NOT EXISTS " . $modx->getFullTablename('commerce_product_options') . " (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `product_id` int(10) unsigned NOT NULL,
        `code` varchar(32) NOT NULL,
        `title` varchar(255) NOT NULL,
        `image` text NOT NULL,
        `modifier` enum('add','subtract','multiply','equal') NOT NULL DEFAULT 'add',
        `amount` float NOT NULL DEFAULT '0',
        `count` float unsigned NOT NULL DEFAULT '1',
        `meta` text,
        `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
        `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00',
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("
    CREATE TABLE IF NOT EXISTS " . $modx->getFullTablename('commerce_product_option_values') . " (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `option_id` int(10) unsigned NOT NULL,
        `value_id` int(10) unsigned NOT NULL,
        PRIMARY KEY (`id`),
        KEY `option_id` (`option_id`),
        KEY `attr_id` (`value_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

// remove installer
$query = $modx->db->select('id', $tablePlugins, "`name` = 'CommerceOptionsInstall'");

if ($id = $modx->db->getValue($query)) {
   $modx->db->delete($tablePlugins, "`id` = '$id'");
   $modx->db->delete($tableEvents, "`pluginid` = '$id'");
};
