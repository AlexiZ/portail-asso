<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver;
use SourceBroker\DeployerLoader\Load;

require 'recipe/symfony.php';
require_once __DIR__.'/vendor/autoload.php';

new Load([
    ['path' => 'vendor/sourcebroker/deployer-instance/deployer'],
    ['path' => 'vendor/sourcebroker/deployer-extended-database/deployer'],
]);

// Charger les secrets depuis un fichier local qui n’est pas versionné
if (file_exists(__DIR__.'/deploy_secrets.php')) {
    require __DIR__.'/deploy_secrets.php';
}

// Config
set('application', 'portail-asso');
set('repository', 'git@github.com:AlexiZ/portail-asso.git');
set('branch', 'main');
set('keep_releases', 5);
set('composer_path', '{{deploy_path}}/.dep/composer.phar');
add('shared_dirs', ['public/uploads']);

localhost('local')
    ->set('deploy_path', getcwd())
    ->set('bin/php', 'php')
    ->set('db_databases', [
        'database_default' => [
            (new EnvDriver())->getDatabaseConfig(),
        ],
    ]);

// Hosts
foreach (['preprod', 'prod'] as $env) {
    host($env)
        ->setHostname(getenv('DEPLOY_HOST'))
        ->setRemoteUser(getenv('DEPLOY_USER'))
        ->set('http_user', getenv('DEPLOY_USER'))
        ->set('writable_mode', 'chmod')
        ->setDeployPath(getenv('DEPLOY_PATH'))
        ->set('db_databases', [
            'database_default' => [
                (new EnvDriver())->getDatabaseConfig(),
            ],
        ])
        ->setIdentityFile(getenv('DEPLOY_IDENTITY_FILE'))
        ->setForwardAgent(true)
        ->set('symfony_env', 'preprod')
        ->setSshArguments(['-p '.getenv('DEPLOY_PORT'), '-o UserKnownHostsFile='.getenv('DEPLOY_KNOWN_HOSTS')]);
}

// Tasks
task('deploy:cleanup-repo', function () {
    run('rm -rf {{deploy_path}}/.dep/repo');
});

task('upload_composer', function () {
    $composerPath = get('composer_path');

    run('mkdir -p ' . escapeshellarg(dirname($composerPath)));

    // Copie le fichier composer.phar depuis le workspace local du projet
    run('cp {{release_path}}/composer.phar ' . escapeshellarg($composerPath));
});

task('deploy:vendors', function () {
    // Installer les dépendances dans release_path
    run("cd {{release_path}} && {{bin/composer}} install --no-interaction --prefer-dist --optimize-autoloader --verbose");
});

task('deploy:assets', function () {
    $host = currentHost()->getHostname();
    $user = currentHost()->getRemoteUser();
    $path = get('release_path').'/public';

    // Arguments SSH dynamiques
    $sshArgs = [];
    if ($port = getenv('DEPLOY_PORT')) {
        $sshArgs[] = '-P '.escapeshellarg($port);
    }
    if ($knownHosts = getenv('DEPLOY_KNOWN_HOSTS')) {
        $sshArgs[] = '-o UserKnownHostsFile='.escapeshellarg($knownHosts);
    }
    $scpArgs = implode(' ', $sshArgs);

    // Build local
    runLocally('make build');

    // Archive locale
    runLocally('tar czf build.tar.gz -C public build');

    // SCP avec arguments dynamiques
    runLocally("scp $scpArgs build.tar.gz {$user}@{$host}:{$path}/build.tar.gz");

    // Extraction côté serveur
    run("cd {$path} && tar xzf build.tar.gz && rm build.tar.gz");

    // Nettoyage local
    runLocally('rm -f build.tar.gz');
});

task('download_medias', function () {
    download('{{current_path}}/public/uploads/', 'public/uploads/');
});

task('upload_medias', function () {
    $host = currentHost()->getHostname();
    $user = currentHost()->getRemoteUser();
    $port = getenv('DEPLOY_PORT');
    $knownHosts = getenv('DEPLOY_KNOWN_HOSTS');
    $sharedPath = get('current_path').'/../../shared';

    // Construction des options SSH dynamiques
    $sshOptions = [];
    if ($port) {
        $sshOptions[] = '-p '.escapeshellarg($port);
    }
    if ($knownHosts) {
        $sshOptions[] = '-o UserKnownHostsFile='.escapeshellarg($knownHosts);
    }
    $sshArgs = implode(' ', $sshOptions);

    $cmd = "tar -C public -czf - uploads | ssh $sshArgs {$user}@{$host} \"tar -xzf - -C {$sharedPath}/public\"";

    runLocally($cmd);
});

// Main deploy flow
task('deploy', [
    'deploy:unlock',
    'deploy:cleanup-repo',
    'deploy:prepare',
    'upload_composer',
    'deploy:vendors',
    'database:migrate',
    'deploy:cache:clear',
    'deploy:assets',
    'deploy:publish',
]);

after('deploy:failed', 'deploy:unlock');
after('db:pull', 'download_medias');
