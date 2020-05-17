<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],

        'authClientCollection' => [
            'class' => 'yii\authclient\Collection',
            'clients' => [
                'google' => [ 
                    'class' => 'yii\authclient\clients\Google', 
                    'clientId' => '1052016610898-sdu0f3rroumtj7oiicc81sdbg359g15c.apps.googleusercontent.com',
                    'clientSecret' => 'JwnFJoQiryLgHdS-He7pIxxw',
                ],
                'facebook' => [
                  'class' => 'yii\authclient\clients\Facebook',
                  'authUrl' => 'https://www.facebook.com/dialog/oauth?display=popup',
                  'clientId' => '2694227744179529',
                  'clientSecret' => '0c55ec38fa693074f797972e7af56729',
                  'attributeNames' => ['name', 'email', 'first_name', 'last_name'],
                ],
            ],
        ],




    ],
    
];
