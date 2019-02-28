<?php

use Alfred\Workflows\Workflow;

require __DIR__ . '/vendor/autoload.php';

$workflow = new Workflow;
$parsedown = new Parsedown;
$plist = new PlistParser;

$query = $argv[1];

preg_match('/^\h*?v?(master|(?:[\d]+)(?:\.[\d]+)?(?:\.[\d]+)?)?\h*?(.*?)$/', $query, $matches);

$connections = $plist->plistToArray($_SERVER['HOME'] . '/Library/Application Support/com.tinyapp.TablePlus/Data/Connections.plist');
$groups = $plist->plistToArray($_SERVER['HOME'] . '/Library/Application Support/com.tinyapp.TablePlus/Data/ConnectionGroups.plist');

$results = array_filter($connections, function ($connection) use ($query) {
    return strpos($connection['ConnectionName'], $query) !== false;
});

$urls = [];

foreach ($results as $result) {
    $connection = $result;

    $groupKey = array_search($connection['GroupID'], array_column($groups, 'ID'));
    $group = $groups[$groupKey];

    $url = 'tableplus://?id=' . sprintf($connection['ID']);

    if (in_array($url, $urls)) {
        continue;
    }

    $urls[] = $url;

    $title = "{$connection['ConnectionName']} » {$connection['Driver']}";

    $text = "{$group['Name']} » {$connection['Enviroment']}";

    $title = strip_tags(html_entity_decode($title, ENT_QUOTES, 'UTF-8'));

    $text = $parsedown->line($text);
    $text = strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));

    $workflow->result()
        ->uid($connection['ID'])
        ->title($title)
        ->autocomplete($title)
        ->subtitle($text)
        ->arg($url)
        ->quicklookurl($url)
        ->valid(true);
}

echo $workflow->output();
