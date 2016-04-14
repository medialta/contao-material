<?php

namespace ContaoMaterial;

class Image extends \Contao\Image
{
    /**
     * Generate an image tag and return it as string
     *
     * @param string $src        The image path
     * @param string $alt        An optional alt attribute
     * @param string $attributes A string of other attributes
     *
     * @return string The image HTML tag
     */
    public static function getHtml($src, $alt='', $attributes='')
    {
        return \Helper::getHtml($src, $alt, $attributes);
    }
}