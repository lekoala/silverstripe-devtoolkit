<?php

/**
 * StrongPasswordValidator
 *
 * @author lekoala
 */
class StrongPasswordValidator extends PasswordValidator
{

    public function __construct()
    {
        parent::__construct();
        $this->minLength(8);
        $this->checkHistoricalPasswords(6);
        $this->characterStrength(4,
            array("lowercase", "uppercase", "digits", "punctuation"));
    }

    public static function applyToMember()
    {
        Member::set_password_validator(new StrongPasswordValidator);
    }
}
