<?php

/**
 * A simple extension that opens in target blank
 *
 * @author LeKoala <thomas@lekoala.be>
 */
class BetterButtonNewWindowAction extends BetterButtonCustomAction
{

    public function __construct($actionName, $text, $redirectType = null)
    {
        parent::__construct($actionName, $text, $redirectType);

        $this->setAttribute('target', '_blank');
    }

    /**
     * Gets the HTML representing the button
     * @return string
     */
    public function getButtonHTML()
    {
        return sprintf(
            '<a class="ss-ui-button %s" target="_blank" href="%s">%s</a>',
            $this->extraClass(), $this->getButtonLink(), $this->getButtonText()
        );
    }
}
