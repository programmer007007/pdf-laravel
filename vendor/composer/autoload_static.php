<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb564a17d0ae0f9dd65ba21bd58216e75
{
    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'mikehaertl\\tmp\\' => 15,
            'mikehaertl\\shellcommand\\' => 24,
            'mikehaertl\\pdftk\\' => 17,
        ),
        'A' => 
        array (
            'Andrew\\PdfFillerLaravel\\' => 24,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'mikehaertl\\tmp\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/php-tmpfile/src',
        ),
        'mikehaertl\\shellcommand\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/php-shellcommand/src',
        ),
        'mikehaertl\\pdftk\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/php-pdftk/src',
        ),
        'Andrew\\PdfFillerLaravel\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb564a17d0ae0f9dd65ba21bd58216e75::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb564a17d0ae0f9dd65ba21bd58216e75::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb564a17d0ae0f9dd65ba21bd58216e75::$classMap;

        }, null, ClassLoader::class);
    }
}
