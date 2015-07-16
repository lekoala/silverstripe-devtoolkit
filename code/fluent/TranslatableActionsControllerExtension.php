<?php

/**
 * Try to make translatable actions
 * 
 * @author Koala
 */
class TranslatableActionsControllerExtension extends DataExtension
{

    public function onBeforeInit()
    {
        /* @var $owner ContentController */
        $owner = $this->owner;
        /* @var $request SS_HTTPRequest */
        $request   = $owner->getRequest();

        $action = $request->param('Action');

        $class                = get_class($this->owner);
        $translatable_actions = Config::inst()->get($class,
            'translatable_actions');
        $actions              = Config::inst()->get($class, 'allowed_actions');
        foreach ($translatable_actions as $translated_action => $base_action) {
            if ($action == $translated_action) {
                $actions[] = $translated_action;
                Config::inst()->update(get_class($this->owner),
                    'allowed_actions', $actions);

                $params           = $request->routeParams();
                $params['Action'] = $base_action;
                $request->setRouteParams($params);
                $owner->action = $base_action;

                // We should be able to overwrite "allParams" and "latestParams" as well for this to work
            }
        }
    }

    public function allMethodNames($custom = false)
    {
        // A friendly hack to make hasMethod returns true
        if (Controller::has_curr()) {
            return array(Controller::curr()->getRequest()->param('Action'));
        }
    }

    /**
     *
     * @param SS_HTTPRequest $request
     * @param string $action
     */
    public function beforeCallActionHandler(&$request, &$action)
    {
        $class                = get_class($this->owner);
        $translatable_actions = Config::inst()->get($class,
            'translatable_actions');

        if (isset($translatable_actions[$action])) {
            $action = $translatable_actions[$action];
        }
    }
}