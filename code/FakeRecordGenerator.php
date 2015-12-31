<?php

/**
 * FakeRecordGenerator
 *
 * @author lekoala
 */
class FakeRecordGenerator
{
    protected static $latitude    = 50.7802;
    protected static $longitude   = 4.4269;
    protected static $avatarsPath = 'resources/avatars';
    protected static $imageRss    = 'http://backend.deviantart.com/rss.xml?q=boost%3Apopular+meta%3Aall+max_age%3A24h&type=deviation';
    protected static $firstNames  = array(
        'Caecilius', 'Quintus', 'Horatius', 'Flaccus', 'Clodius',
        'Metellus', 'Flavius', 'Hortensius', 'Julius', 'Decimus', 'Gaius'
    );
    protected static $lastNames   = array(
        'Gracchus', 'Antonius', 'Brutus', 'Cassius', 'Casca', 'Lepidus',
        'Crassus', 'Cinna'
    );
    protected static $addresses   = array(
        array('Address' => '4880 Glory Rd', 'City' => 'Ponchatoula', 'Postcode' => 'LA 70454',
            'Country' => 'US'),
        array('Address' => '4363 Willow Oaks Lane', 'City' => 'Harrison Township',
            'Postcode' => 'NJ 08062', 'Country' => 'US'),
        array('Address' => '3471 Chipmunk Ln', 'City' => 'Clifton Heights', 'Postcode' => 'PA 19018 ‎',
            'Country' => 'US'),
        array('Address' => '666 Koala Ln', 'City' => 'Mt Laurel', 'Postcode' => 'NJ 08054‎',
            'Country' => 'US'),
        array('Address' => '3339 Little Acres Ln', 'City' => 'Woodford', 'Postcode' => 'VA 22580',
            'Country' => 'US'),
        array('Address' => '15 Anthony Avenue', 'City' => 'Essex', 'Postcode' => 'MD 21221',
            'Country' => 'US'),
        array('Address' => '2942 Kelly Ave', 'City' => 'Baltimore', 'Postcode' => 'MD 21209',
            'Country' => 'US'),
        array('Address' => '687 Burke Rd', 'City' => 'Delta', 'Postcode' => 'PA 17314',
            'Country' => 'US'),
        array('Address' => '1196 Court St', 'City' => 'York', 'Postcode' => 'PA 17404 ‎',
            'Country' => 'US'),
        array('Address' => 'Barnes St', 'City' => 'Bel Air', 'Postcode' => 'MD 21014',
            'Country' => 'US'),
    );
    protected static $domains     = array('perdu.com', 'silverstripe.org', 'google.be');
    protected static $words       = array(
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing',
        'elit', 'curabitur', 'vel', 'hendrerit', 'libero', 'eleifend',
        'blandit', 'nunc', 'ornare', 'odio', 'ut', 'orci',
        'gravida', 'imperdiet', 'nullam', 'purus', 'lacinia', 'a',
        'pretium', 'quis', 'congue', 'praesent', 'sagittis', 'laoreet',
        'auctor', 'mauris', 'non', 'velit', 'eros', 'dictum',
        'proin', 'accumsan', 'sapien', 'nec', 'massa', 'volutpat',
        'venenatis', 'sed', 'eu', 'molestie', 'lacus', 'quisque',
        'porttitor', 'ligula', 'dui', 'mollis', 'tempus', 'at',
        'magna', 'vestibulum', 'turpis', 'ac', 'diam',
        'tincidunt', 'id', 'condimentum', 'enim', 'sodales', 'in',
        'hac', 'habitasse', 'platea', 'dictumst', 'aenean', 'neque',
        'fusce', 'augue', 'leo', 'eget', 'semper', 'mattis',
        'tortor', 'scelerisque', 'nulla', 'interdum', 'tellus',
        'malesuada', 'rhoncus', 'porta', 'sem', 'aliquet',
        'et', 'nam', 'suspendisse', 'potenti', 'vivamus', 'luctus',
        'fringilla', 'erat', 'donec', 'justo', 'vehicula',
        'ultricies', 'varius', 'ante', 'primis', 'faucibus', 'ultrices',
        'posuere', 'cubilia', 'curae', 'etiam', 'cursus',
        'aliquam', 'quam', 'dapibus', 'nisl', 'feugiat', 'egestas',
        'class', 'aptent', 'taciti', 'sociosqu', 'ad', 'litora',
        'torquent', 'per', 'conubia', 'nostra', 'inceptos', 'himenaeos',
        'phasellus', 'nibh', 'pulvinar', 'vitae', 'urna', 'iaculis',
        'lobortis', 'nisi', 'viverra', 'arcu', 'morbi', 'pellentesque',
        'metus', 'commodo', 'ut', 'facilisis', 'felis',
        'tristique', 'ullamcorper', 'placerat', 'aenean', 'convallis',
        'sollicitudin', 'integer', 'rutrum', 'duis', 'est',
        'etiam', 'bibendum', 'donec', 'pharetra', 'vulputate', 'maecenas',
        'mi', 'fermentum', 'consequat', 'suscipit', 'aliquam',
        'habitant', 'senectus', 'netus', 'fames', 'quisque',
        'euismod', 'curabitur', 'lectus', 'elementum', 'tempor',
        'risus', 'cras'
    );

    /**
     * A random firstname
     * @return string
     */
    public static function firstname()
    {
        return self::$firstNames[array_rand(self::$firstNames)];
    }

    /**
     * A random lastname
     * @return string
     */
    public static function lastname()
    {
        return self::$lastNames[array_rand(self::$lastNames)];
    }

    /**
     * A random name
     * @return string
     */
    public static function name()
    {
        return self::firstname().' '.self::lastname();
    }

    /**
     * A random address
     * @return string
     */
    public static function address()
    {
        return self::$addresses[array_rand(self::$addresses)];
    }

    protected static function fprand($intMin, $intMax, $intDecimals)
    {
        if ($intDecimals) {
            $intPowerTen = pow(10, $intDecimals);
            return rand($intMin, $intMax * $intPowerTen) / $intPowerTen;
        } else {
            return rand($intMin, $intMax);
        }
    }

    /**
     * A randomized position
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @return array
     */
    public static function latLon($latitude = null, $longitude = null,
                                  $radius = 20)
    {
        if ($latitude === null) {
            $latitude = self::$latitude;
        }
        if ($longitude === null) {
            $longitude = self::$longitude;
        }
        $lng_min = $longitude - $radius / abs(cos(deg2rad($latitude)) * 69);
        $lng_max = $longitude + $radius / abs(cos(deg2rad($latitude)) * 69);
        $lat_min = $latitude - ($radius / 69);
        $lat_max = $latitude + ($radius / 69);

        $rand = self::fprand(0, ($lng_max - $lng_min), 3);
        $lng  = $lng_min + $rand;
        $rand = self::fprand(0, ($lat_max - $lat_min), 3);
        $lat  = $lat_min + $rand;

        return compact('lat', 'lng', 'lng_min', 'lat_min', 'lng_max', 'lat_max');
    }

    /**
     * A random domain
     * @return string
     */
    public static function domain()
    {
        return self::$domains[array_rand(self::$domains)];
    }

    /**
     * A random website
     * @return string
     */
    public static function website()
    {
        return 'http://www'.self::domain();
    }

    /**
     * A random avatar
     * @param type $gender
     * @return type
     */
    public static function avatar($gender = null)
    {
        $images = DataObject::get('Image', "Filename LIKE 'assets/Avatars/%'");

        // If no avatars copy the default ones
        if (!$images->count()) {
            $path          = Director::baseFolder().'/devtoolkit/'.self::$avatarsPath;
            $folder        = Folder::find_or_make('Avatars');
            $folderFemales = Folder::find_or_make('Avatars/Females');
            $folderMales   = Folder::find_or_make('Avatars/Males');
            $dir           = $folder->getFullPath();
            $files         = glob($path.'/*.png');
            foreach ($files as $file) {
                $file_to_go = str_replace("devtoolkit/images/".self::$avatarsPath,
                    "assets/Avatars", $file);
                copy($file, $file_to_go);
            }
            $folder->syncChildren();
            $images = DataObject::get('Image',
                    "Filename LIKE 'assets/Avatars/%'");
        }

        $genders = array('Females', 'Males');
        if (!$gender) {
            $gender = $genders[array_rand($genders)];
        }

        $images = DataObject::get('Image',
                "Filename LIKE 'assets/Avatars/{$gender}/%'")->sort('RAND()');
        return $images->First();
    }

    /**
     * Get a random image
     * @return Image
     */
    public static function image()
    {
        $images = DataObject::get('Image', "Filename LIKE 'assets/Faker/%'");
        if (!count($images)) {
            $rss   = file_get_contents(self::$imageRss);
            $xml   = simplexml_load_string($rss);
            $nodes = $xml->xpath("//media:content");
            $i     = 0;

            $folder = Folder::find_or_make('Faker');
            $dir    = $folder->getFullPath();
            foreach ($nodes as $node) {
                $i++;
                $image    = file_get_contents($node['url']);
                $filename = $dir.'/'.basename($node['url']);

                file_put_contents($filename, $image);
            }
            $folder->syncChildren();
            $images = DataObject::get('Image', "Filename LIKE 'assets/Faker/%'");
        }
        $rand = rand(0, count($images));
        foreach ($images as $key => $image) {
            if ($key == $rand) {
                return $image;
            }
        }
        return $images->First();
    }

    /**
     * Get random words
     * @param int $num
     * @param int $num2
     * @return string
     */
    public static function words($num, $num2 = null)
    {
        $res   = array();
        $i     = 0;
        $total = $num;
        if ($num2 !== null) {
            $i     = rand(0, $num);
            $total = $num2;
        }
        $req = $total - $i;
        foreach (array_rand(self::$words, $req) as $key) {
            $res[] = self::$words[$key];
        }
        return implode(' ', $res);
    }

    /**
     * Get random sentences
     * @param int $num
     * @param int $num2
     * @return string
     */
    public static function sentences($num, $num2 = null)
    {
        $res   = array();
        $i     = 0;
        $total = $num;
        if ($num2 !== null) {
            $i     = rand(0, $num);
            $total = $num2;
        }
        $req = $total - $i;
        while ($req--) {
            $res[] = self::words(5, 10);
        }
        return implode(".\n", $res);
    }

    /**
     * Get random paragraphs
     * @param int $num
     * @param int $num2
     * @return string
     */
    public static function paragraphs($num, $num2 = null)
    {
        $res   = array();
        $i     = 0;
        $total = $num;
        if ($num2 !== null) {
            $i     = rand(0, $num);
            $total = $num2;
        }
        $req = $total - $i;
        while ($req--) {
            $res[] = "<p>".self::sentences(3, 7)."</p>";
        }
        return implode("\n", $res);
    }

    /**
     * Get a date between two dates
     * @param string $num
     * @param string $num2
     * @param string $format
     * @return string
     */
    public static function date($num, $num2, $format = 'Y-m-d H:i:s')
    {
        if (is_string($num)) {
            $num = strtotime($num);
        }
        if (is_string($num2)) {
            $num2 = strtotime($num2);
        }
        $rand = rand($num, $num2);
        return date($format, $rand);
    }

    public static function pick(array $arr)
    {
        return $arr[array_rand($arr)];
    }

    /**
     * Get a list of countries
     * @param string $locale
     * @return array
     */
    public static function getCountryList($locale = null)
    {
        if (!$locale) {
            $locale = i18n::get_locale();
        }
        $countries = Zend_Locale::getTranslationList('territory', $locale, 2);
        asort($countries, SORT_LOCALE_STRING);
        unset($countries['SU'], $countries['ZZ'], $countries['VD'],
            $countries['DD']);
        return $countries;
    }

    /**
     * Get a random country
     * @return string
     */
    public static function country()
    {
        $countries = array_values(self::getCountryList());
        return $countries[array_rand($countries)];
    }

    /**
     * Get a random country code
     * @return string
     */
    public static function countryCode()
    {
        $countries = array_keys(self::getCountryList());
        return $countries[array_rand($countries)];
    }
}
