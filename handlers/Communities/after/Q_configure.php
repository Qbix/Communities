<?php

function Communities_after_Q_configure($params)
{
    if ($layout = Q_Config::get('Communities', 'layout', null)) {
        Q_Config::merge(array(
            'Q' => array(
                'response' => array(
                    'layout' => $layout
                )
            )
        ));
    }
}