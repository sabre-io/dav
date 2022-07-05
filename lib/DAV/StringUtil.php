<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * String utility.
 *
 * This class is mainly used to implement the 'text-match' filter, used by both
 * the CalDAV calendar-query REPORT, and CardDAV addressbook-query REPORT.
 * Because they both need it, it was decided to put it in Sabre\DAV instead.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class StringUtil
{
    /**
     * Checks if a needle occurs in a haystack ;).
     *
     * @param string $haystack
     * @param string $needle
     * @param string $collation
     * @param string $matchType
     *
     * @return bool
     */
    public static function textMatch($haystack, $needle, $collation, $matchType = 'contains')
    {
        switch ($collation) {
            case 'i;ascii-casemap':
                // default strtolower takes locale into consideration
                // we don't want this.
                $haystack = str_replace(range('a', 'z'), range('A', 'Z'), $haystack);
                $needle = str_replace(range('a', 'z'), range('A', 'Z'), $needle);
                break;

            case 'i;octet':
                // Do nothing
                break;

            case 'i;unicode-casemap':
                $haystack = mb_strtoupper($haystack, 'UTF-8');
                $needle = mb_strtoupper($needle, 'UTF-8');
                break;

            default:
                throw new Exception\BadRequest('Collation type: '.$collation.' is not supported');
        }

        switch ($matchType) {
            case 'contains':
                return false !== strpos($haystack, $needle);
            case 'equals':
                return $haystack === $needle;
            case 'starts-with':
                return 0 === strpos($haystack, $needle);
            case 'ends-with':
                return strrpos($haystack, $needle) === strlen($haystack) - strlen($needle);
            default:
                throw new Exception\BadRequest('Match-type: '.$matchType.' is not supported');
        }
    }

    /**
     * This method takes an input string, checks if it's not valid UTF-8 and
     * attempts to convert it to UTF-8 if it's not.
     *
     * Note that currently this can only convert ISO-8859-1 to UTF-8 (latin-1),
     * anything else will likely fail.
     *
     * @param string $input
     *
     * @return string
     */
    public static function ensureUTF8($input)
    {
        if (!mb_check_encoding($input, 'UTF-8') && mb_check_encoding($input, 'ISO-8859-1')) {
            return mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1');
        } else {
            return $input;
        }
    }

    /**
     * substitution function to parse a given line using the
     * given username, using cyrus-sasl style replacements.
     *
     * %u   - gets replaced by full username
     * %U   - gets replaced by user part when the
     *        username is an email address
     * %d   - gets replaced by domain part when the
     *        username is an email address
     * %%   - gets replaced by %
     * %1-9 - gets replaced by parts of the the domain
     *        split by '.' in reverse order
     *
     * full example for jane.doe@mail.example.org:
     *        %u = jane.doe@mail.example.org
     *        %U = jane.doe
     *        %d = mail.example.org
     *        %1 = org
     *        %2 = example
     *        %3 = mail
     *
     * @param string username
     * @param string line
     *
     * @return string
     */
    public static function parseCyrusSasl($username, $line)
    {
        $user_split = [$username];
        $user = $username;
        $domain = '';
        try {
            $user_split = explode('@', $username, 2);
            $user = $user_split[0];
            if (2 == count($user_split)) {
                $domain = $user_split[1];
            }
        } catch (Exception $ignored) {
        }
        $domain_split = [];
        try {
            $domain_split = array_reverse(explode('.', $domain));
        } catch (Exception $ignored) {
            $domain_split = [];
        }

        $parsed_line = '';
        for ($i = 0; $i < strlen($line); ++$i) {
            if ('%' == $line[$i]) {
                ++$i;
                $next_char = $line[$i];
                if ('u' == $next_char) {
                    $parsed_line .= $username;
                } elseif ('U' == $next_char) {
                    $parsed_line .= $user;
                } elseif ('d' == $next_char) {
                    $parsed_line .= $domain;
                } elseif ('%' == $next_char) {
                    $parsed_line .= '%';
                } else {
                    for ($j = 1; $j <= count($domain_split) && $j <= 9; ++$j) {
                        if ($next_char == ''.$j) {
                            $parsed_line .= $domain_split[$j - 1];
                        }
                    }
                }
            } else {
                $parsed_line .= $line[$i];
            }
        }

        return $parsed_line;
    }
}
