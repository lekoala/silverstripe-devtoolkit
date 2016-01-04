<?php

/**
 * A simple extension that opens in target blank
 *
 * @author LeKoala <thomas@lekoala.be>
 */
class BetterButtonNewWindowLink extends BetterButtonLink
{

    public function getButtonHTML()
    {
        return sprintf(
            '<a class="ss-ui-button %s" href="%s" target="_blank">%s</a>',
            $this->extraClass(), $this->getButtonLink(), $this->getButtonText()
        );
    }
}
