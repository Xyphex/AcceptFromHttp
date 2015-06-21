<?php
/*
    Reimplementation of PHP's Locale::acceptFromHttp() which uses the HTTP
    Accep-Language header, an array of supported locales and a default locale to
    determine the most suitable locale; returned as a IETF language tag.

    Copyright © 2014, 2015 Aram Nap

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
    Example usage:

    <?php
        $supportedLocales = [
            0 => 'de-DE',
            1 => 'en-US',
            2 => 'es-ES',
            3 => 'fr-CA',
            4 => 'fr-FR',
            5 => 'nl_BE',
            6 => 'nl-NL',
            7 => 'pt-BR',
            8 => 'pt-PT',
            9 => 'ru-RU'
        ];

        $defaultLocale = $supportedLocales[7];

        $locale = acceptFromHttp(
            $_SERVER['HTTP_ACCEPT_LANGUAGE'],
            $supportedLocales,
            $defaultLocale
        );

        echo $locale;
    ?>
*/

function acceptFromHttp(
    $httpAcceptLanguage,
    array $supportedLocales,
    $defaultLocale
) {
    if (empty($supportedLocales)) {
        throw new LogicException(
            'The array of supported locales for '
            . __FUNCTION__ . ' can\'t be empty.'
        );
    }

    if (empty($defaultLocale)) {
        throw new LogicException(
            'The default locale for '
            . __FUNCTION__ . ' can\'t be empty.'
        );
    }

    if (empty($httpAcceptLanguage)) {
        return $defaultLocale;
    }

    $httpAcceptLanguage = explode(
        ',',
        str_replace(' ', '', $httpAcceptLanguage)
    );

    $qualityFactors = [];
    $locales = [];

    for ($i = 0, $max = count($httpAcceptLanguage); $i < $max; ++$i) {
        // Check if the locale contains a quality factor.
        $separatorPosition = strpos($httpAcceptLanguage[$i], ';');

        if (false !== $separatorPosition) {
            $qualityFactors[$i] = (float) substr(
                $httpAcceptLanguage[$i],
                strpos($httpAcceptLanguage[$i], '=') + 1
            );

            $locale = substr(
                $httpAcceptLanguage[$i],
                0,
                $separatorPosition
            );
        } else {
            $qualityFactors[$i] = 1.0;
            $locale = $httpAcceptLanguage[$i];
        }

        // Transform the language and the country (if available)
        // to lower and upper case respectively.
        $separatorPosition = strrpos($locale, '-');

        if (false !== $separatorPosition) {
            $locales[$i] = strtolower(substr($locale, 0, $separatorPosition))
                . strtoupper(substr($locale, $separatorPosition));
        } else {
            $locales[$i] = strtolower($locale);
        }
    }

    $httpAcceptLanguage = array_combine($locales, $qualityFactors);
    arsort($httpAcceptLanguage, SORT_NUMERIC);

    // Return the most suitable locale.
    foreach ($httpAcceptLanguage as $locale => $qualityFactor) {
        $separatorPosition = strrpos($locale, '-');

        if (
            // Check if the locale isn't just a language.
            false !== $separatorPosition
            && ctype_upper(substr($locale, $separatorPosition + 1))
        ) {
            for (
                $i = 0, $max = count($supportedLocales);
                $i < $max;
                ++$i
            ) {
                if ($locale === $supportedLocales[$i]) {
                    return $supportedLocales[$i];
                }
            }
        } else {
            for (
                $i = 0, $max = count($supportedLocales);
                $i < $max;
                ++$i
            ) {
                if (0 === strpos($supportedLocales[$i], $locale)) {
                    return $supportedLocales[$i];
                }
            }
        }
    }

    // Return the most suitable locale based on the language of the locale.
    foreach ($httpAcceptLanguage as $locale => $qualityFactor) {
        $separatorPosition = strrpos($locale, '-');

        if (false !== $separatorPosition) {
            $language = substr($locale, 0, $separatorPosition);
        } else {
            $language = $locale;
        }

        for (
            $i = 0, $max = count($supportedLocales);
            $i < $max;
            ++$i
        ) {
            if (0 === strpos($supportedLocales[$i], $language)) {
                return $supportedLocales[$i];
            }
        }
    }

    // Fallback to the default locale.
    return $defaultLocale;
}
