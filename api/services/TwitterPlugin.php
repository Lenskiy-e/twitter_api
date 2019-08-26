<?php

namespace api\services;

use yii\authclient\clients\Twitter;
use yii\authclient\OAuthToken;

class TwitterPlugin
{

    /**
     * @var yii\authclient\OAuthToken
     */
    protected static $token;

    /**
     * @var yii\authclient\clients\Twitter
     */
    protected static $twitter;

    private function __construct(){}

    /**
     * Initialize connect with twitter
     * @return Twitter
     */
    protected static function init() : Twitter
    {
        if(!self::$token)
        {
            self::$token = new OAuthToken([
                'token'         => \Yii::$app->params['twitterAccessToken'],
                'tokenSecret'   => \Yii::$app->params['twitterAccessTokenSecret']
            ]);
        }

        if(!self::$twitter)
        {
            self::$twitter = new Twitter([
                'accessToken'   => self::$token,
                'consumerKey' => \Yii::$app->params['twitterApiKey'],
                'consumerSecret' => \Yii::$app->params['twitterApiSecret']
            ]);
        }

        return self::$twitter;
    }

    /**
     * @param string $screen_name
     * @param int $count
     * @return \stdClass
     */
    public static function getTweets(string $screen_name, int $count = 5) : \stdClass
    {
        $data = self::init()->api('statuses/user_timeline', 'GET', [
            'screen_name'       => $screen_name,
            'count'             => $count,
            'exclude_replies'   => false,
        ]);

        return json_decode($data);
    }
}