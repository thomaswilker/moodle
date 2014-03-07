<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CLI script to run multiple behats in parallel and summarize the output.
 *
 * @package    tool_behat
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (isset($_SERVER['REMOTE_ADDR'])) {
    die(); // No access from web!
}

// Is not really necessary but adding it as is a CLI_SCRIPT.
define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

// Basic functions.
require_once(__DIR__ . '/../../../../lib/clilib.php');
require_once(__DIR__ . '/../../../../lib/behat/lib.php');

// CLI options.
list($options, $unrecognized) = cli_get_params(
    array(
        'parallel'    => 1
    ),
    array(
        'j' => 'parallel'
    )
);

$start = time();
$suffix = '';
$handles = array();
$logs = array();
$alldone = false;

/*
// Changing the cwd to dirroot.
chdir(__DIR__ . "/../../../..");
$rdi = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("."), RecursiveIteratorIterator::SELF_FIRST);
$features = array();
for ($i = 0; $i < $options['parallel']; $i++) {
    $features[$i] = array();
}
$index = 0;
foreach ($rdi as $name => $object) {
    if (strpos($name, ".feature")) {
        array_push($features[$index++ % $options['parallel']], $name);
    }
}
*/

for ($i = 0; $i < $options['parallel']; $i++) {
    $logs[$i] = '';
    if ($options['parallel'] > 1) {
        $suffix = '_' . $i;
        echo "Start tests for behat site $suffix\n";
    }
    // Changing the cwd to admin/tool/behat/cli.
    chdir(__DIR__);
    $handles[$i] = popen("php util.php --run --suffix=$suffix --options=\"--format=html\"", "r");
}

function print_summary($logs, $count, &$errors, &$lastfail) {
    // If there are any errors then fail immediately.
    $total = 0;
    for ($i = 0; $i < $count; $i++) {
        $failedpos = strrpos($logs[$i], "class=\"failed\"");
        if ($failedpos && $failedpos != $lastfail && strpos(substr($logs[$i], $failedpos), "<div class=\"scenario\">")) {
            $errors++;
            print("\nFailed scenario...\n");
            $endpos = strrpos($logs[$i], "<div class=\"scenario\">");
            $startpos = strrpos(substr($logs[$i], 0, $endpos), "<div class=\"scenario\">");
            print(preg_replace("/\n+/", "\n", strip_tags(substr($logs[$i], $startpos, $endpos - $startpos))));
            print("\n");
            $lastfail = $failedpos;
        }
        $total += substr_count($logs[$i], "<div class=\"scenario\">");
    }
    $b = "\x08";
    print("$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b$b");
    print ("Finished $total scenarios, $errors errors found");
}

$errors = 0;
$lastfail = 0;
while (!$alldone) {
    $alldone = true;
    for ($i = 0; $i < $options['parallel']; $i++) {
        if (!feof($handles[$i])) {
            $alldone = false;
            $output = fread($handles[$i], 1024);
            $logs[$i] .= $output;
        }
    }

    print_summary($logs, $options['parallel'], $errors, $lastfail);

}
print("\n\n");

for ($i = 0; $i < $options['parallel']; $i++) {
    print($logs[$i]);
    pclose($handles[$i]);
}

$elapsed = time() - $start;
$hours = floor($elapsed / (60*60));
$mins = (floor($elapsed / (60)) % 60);
$secs = ($elapsed % 60);
print("Finished in $hours hours $mins mins $secs secs\n");

exit(0);
