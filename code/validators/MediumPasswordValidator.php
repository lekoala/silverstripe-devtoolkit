<?php

/**
 * MediumPasswordValidator
 *
 * @author lekoala
 */
class MediumPasswordValidator extends PasswordValidator
{

    function __construct()
    {
        parent::__construct();
        $this->minLength(6);
        $this->checkHistoricalPasswords(2);
        $this->characterStrength(2, array("lowercase", "uppercase", "digits"));
    }

    static function applyToMember() {
        Member::set_password_validator(new MediumPasswordValidator);
    }
}