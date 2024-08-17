<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use DataDocumentBackup\Contract\BackupTarget;

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function integrationEnvWebDavConfig(): array
{
    return [
        'baseUri' => getenv('WEBDAV_BASE_URI'), // must end with slash
        'userName' => getenv('WEBDAV_USERNAME'),
        'password' => getenv('WEBDAV_PASSWORD'),
        'authType' => getenv('WEBDAV_AUTH_TYPE') ?? 1,
    ];
}

function localBackupTarget(string $filename): BackupTarget
{
    return new BackupTarget($filename, 'Local', [
        'root' => './tests/.dist',
    ]);
}

function expectLocalOutputFileToExist(string $filename): \Pest\Expectation
{
    return expect('./tests/.dist/' . $filename)->toBeFile();
}

function removeLocalOutputFile(string $filename): void
{
    unlink('./tests/.dist/' . $filename);
}