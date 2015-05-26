<?php
if (!function_exists('d')) {

    function d()
    {
        SS_Log::log('A call was made to '.__FUNCTION__.'() outside of dev mode',
            SS_Log::INFO);
    }
}

if (!function_exists('dd')) {

    function dd()
    {
        SS_Log::log('A call was made to '.__FUNCTION__.'() outside of dev mode',
            SS_Log::INFO);
    }
}

if (!function_exists('ddd')) {

    function ddd()
    {
        SS_Log::log('A call was made to '.__FUNCTION__.'() outside of dev mode',
            SS_Log::INFO);
    }
}

if (!function_exists('s')) {


    function s()
    {
        SS_Log::log('A call was made to '.__FUNCTION__.'() outside of dev mode',
            SS_Log::INFO);
    }
}

if (!function_exists('sd')) {

    function sd()
    {
        SS_Log::log('A call was made to '.__FUNCTION__.'() outside of dev mode',
            SS_Log::INFO);
    }
}
