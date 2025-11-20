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
set('application', 'portail-plab');
set('repository', 'git@github.com:AlexiZ/portail-plab.git');
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
host('preprod')
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
    ->set('symfony_env', 'prod')
    ->setSshArguments(['-p '.getenv('DEPLOY_PORT'), '-o UserKnownHostsFile='.getenv('DEPLOY_KNOWN_HOSTS')]);

// Tasks
task('deploy:cleanup-repo', function () {
    run('rm -rf {{deploy_path}}/.dep/repo');
});

task('upload_composer', function () {
    upload('composer.phar', get('composer_path'));
});

task('upload_assets', function () {
    upload('public/build/', '{{release_path}}/public/build/');
});

task('download_medias', function () {
    download('{{current_path}}/public/uploads/', 'public/uploads/');
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
    'upload_assets',
    'deploy:publish',
]);

after('deploy:failed', 'deploy:unlock');
after('db:pull', 'download_medias');
