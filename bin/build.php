<?php

$tasks = [

    'buildzip' => [
        'init', 'test', 'clean',
    ],
    'markrelease' => [
        'init', 'test', 'clean',
    ],
    'clean' => [],
    'test' => [
        'composerupdate',
    ],
    'init' => [],
    'composerupdate' => [],
 ];

$default = 'buildzip';

$baseDir = __DIR__ . '/../';
chdir($baseDir);

$currentTask = $default;
if ($argc > 1) $currentTask = $argv[1];
$version = null;
if ($argc > 2) $version = $argv[2];

if (!isset($tasks[$currentTask])) {
    echo "Task not found: ",  $currentTask, "\n";
    die(1);
}

// Creating the dependency graph
$newTaskList = [];
$oldTaskList = [$currentTask => true];

while(count($oldTaskList)>0) {

    foreach($oldTaskList as $task=>$foo) {

        if (!isset($tasks[$task])) {
            echo "Dependency not found: " . $task, "\n";
            die(1);
        }
        $dependencies = $tasks[$task];

        $fullFilled = true;
        foreach($dependencies as $dependency) {
            if (isset($newTaskList[$dependency])) {
                // Already in the fulfilled task list.
                continue;
            } else {
                $oldTaskList[$dependency] = true;
                $fullFilled = false;
            }
           
        }
        if ($fullFilled) {
            unset($oldTaskList[$task]);
            $newTaskList[$task] = 1;
        }

    }

}

foreach(array_keys($newTaskList) as $task) {

    echo "task: " . $task, "\n";
    call_user_func($task);
    echo "\n";

}

function init() {

    global $version;
    if (!$version) {
        include __DIR__ . '/../vendor/autoload.php';
        $version = Sabre\DAV\Version::VERSION;
    }

    echo "  Building sabre/dav " . $version, "\n";

}

function clean() {

    global $baseDir;
    echo "  Removing build files\n";
    $outputDir = $baseDir . '/build/SabreDAV';
    if (is_dir($outputDir)) {
        system('rm -r ' . $baseDir . '/build/SabreDAV');
    }

}

function composerupdate() {

    global $baseDir;
    echo "  Updating composer packages to latest version\n\n";
    system('cd ' . $baseDir . '; composer update --dev');
}

function test() {

    global $baseDir;

    echo "  Running all unittests.\n";
    echo "  This may take a while.\n\n";
    system(__DIR__ . '/phpunit --configuration ' . $baseDir . '/tests/phpunit.xml --stop-on-failure', $code);
    if ($code != 0) {
        echo "PHPUnit reported error code $code\n";
        die(1);
    }
   
}

function buildzip() {

    global $baseDir, $version;
    echo "  Asking composer to download sabre/dav $version\n\n";
    system("composer create-project -n --no-dev sabre/dav build/SabreDAV $version", $code);
    if ($code!==0) {
        echo "Composer reported error code $code\n";
        die(1);
    }
    // <zip destfile="build/SabreDAV-${sabredav.version}.zip" basedir="build/SabreDAV" prefix="SabreDAV/" />

    echo "\n";
    echo "Zipping the sabredav distribution\n\n";
    system('cd build; zip -qr sabredav-' . $version . '.zip SabreDAV');

    echo "Done.";

}
