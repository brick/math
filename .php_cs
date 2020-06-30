<?php

return \PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
    ])
    ->setFinder(PhpCsFixer\Finder::create()->files()->in([__DIR__ . '/src', __DIR__ . '/tests'])->name('*.php'));
