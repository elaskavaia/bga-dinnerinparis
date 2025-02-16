#!/usr/bin/env php
<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

$gitCommands = [
	'update-index --chmod=+x FILE',
];

echo "\nGit common commands\n\n";

foreach( $gitCommands as $subCommand ) {
	$command = sprintf('git %s', $subCommand);
	echo $command . "\n";
}

$npmCommands = [
	'install'   => 'Install node modules',
	'run build' => 'Build app for production',
	'run dev'   => 'Watch for changes by running development watcher',
];

echo "\nNPM common commands\n\n";

foreach( $npmCommands as $subCommand => $description ) {
	$command = sprintf("npm %s\t%s", $subCommand, $description);
	echo $command . "\n";
}
