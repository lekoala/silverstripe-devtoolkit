<?php

/**
 * A simple extension that opens in target blank
 *
 * @author LeKoala <thomas@lekoala.be>
 */
class BetterButtonNewWindowLink extends BetterButtonLink
{

    public function __construct($actionName, $text, $redirectType = null)
    {
        parent::__construct($actionName, $text, $redirectType);

        $this->newWindow();
    }
}
