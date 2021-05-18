<?php

namespace Torann\LocalizationHelpers\Concerns;

use Exception;

trait MangeFiles
{
    /**
     * Encode the key so that the array set function doesn't
     * go crazy when it sets the values.
     *
     * @param string $string
     *
     * @return string
     */
    protected function encodeKey(string $string): string
    {
        return preg_replace_callback('/(\.\s|\.\||\.$)/', function ($matches) {
            return str_replace('.', '&#46;', $matches[0]);
        }, $string);
    }

    /**
     * Decode the key so it looks normal again.
     *
     * @param string $string
     *
     * @return string
     */
    protected function decodeKey(string $string): string
    {
        return str_replace('&#46;', '.', $string);
    }

    /**
     * Get the lang directory path
     *
     * @param string|null $path
     *
     * @return string
     * @throws Exception
     */
    protected function getLangPath(string $path = null): string
    {
        $lang_folder_path = config('localization-helpers.lang_folder_path') ?: base_path('resources/lang');

        if (is_dir($lang_folder_path)) {
            return $lang_folder_path . ($path ? "/{$path}" : '');
        }

        throw new Exception(
            "No lang folder found in your custom path: \"{$lang_folder_path}\""
        );
    }

    /**
     * Convert the arrays to the shorthand syntax.
     *
     * @param string $source
     * @param string $code
     *
     * @return string
     */
    protected function dumpLangArray(string $source, string $code = ''): string
    {
        // Use array short syntax
        if (config('localization-helpers.array_shorthand', true) === false) {
            return $source;
        }

        // Split given source into PHP tokens
        $tokens = token_get_all($source);

        $brackets = [];

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if ($token === '(') {
                $brackets[] = false;
            } elseif ($token === ')') {
                $token = array_pop($brackets) ? ']' : ')';
            } elseif (is_array($token) && $token[0] === T_ARRAY) {
                $a = $i + 1;
                if (isset($tokens[$a]) && $tokens[$a][0] === T_WHITESPACE) {
                    $a++;
                }
                if (isset($tokens[$a]) && $tokens[$a] === '(') {
                    $i = $a;
                    $brackets[] = true;
                    $token = '[';
                }
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        // Fix indenting
        $code = preg_replace('/^  |\G  /m', '    ', $code);

        // Fix weird new line breaks at the beginning of arrays
        $code = preg_replace('/=\>\s\n\s{4,}\[/m', '=> [', $code);

        return $code;
    }
}
