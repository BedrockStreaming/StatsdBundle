<?php

$finder = PhpCsFixer\Finder::create()
    ->in(
        [
            __DIR__.'/src',
        ]
    );

$config = new M6Web\CS\Config\BedrockStreaming();
$config->setFinder($finder);

return $config;
