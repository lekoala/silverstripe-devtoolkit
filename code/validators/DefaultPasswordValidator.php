<?php

/**
 * DefaultPasswordValidator
 * 
 * A configurable password validator
 *
 * @author lekoala
 */
class DefaultPasswordValidator extends PasswordValidator
{

    public function __construct()
    {
        parent::__construct();

        $config = self::config();
        if ($config->min_length) {
            $this->minLength($config->min_length);
        }
        if ($config->historical_passwords) {
            $this->checkHistoricalPasswords($config->historical_passwords);
        }
        $characters = array_unique($config->characters);
        $count = $config->characters_count;
        if (!empty($characters)) {
            if (!$count) {
                $count = count($characters);
            }
            $this->characterStrength($count, $characters);
        }
    }

    public static function applyToMember()
    {
        $class = get_called_class();
        Member::set_password_validator(new $class);
    }
}
