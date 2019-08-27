<?php


namespace api\controllers;


use api\models\RequestSessions;
use api\models\Subscribers;
use api\services\TwitterPlugin;
use yii\rest\Controller;
use yii\web\Response;

class ApiController extends Controller
{
    /**
     * @var \yii\console\Request|\yii\web\Request
     */
    private $request;

    /**
     * @var Subscribers
     */
    private $subscribers;

    /**
     * @var RequestSessions
     */
    private $sessions;


    /**
     * ApiController constructor.
     * @param $id
     * @param $module
     * @param Subscribers $subscribers
     * @param RequestSessions $sessions
     * @param array $config
     */
    public function __construct($id, $module, Subscribers $subscribers, RequestSessions $sessions, $config = [])
    {
        $this->request = \Yii::$app->request;

        $this->subscribers = $subscribers;
        $this->sessions = $sessions;

        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\filters\ContentNegotiator::className(),
                'only'  => ['add', 'feed', 'remove'],
                'formats' => [
                    'application/json' => \yii\web\Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * add user to subscribers table
     * @return array|null
     */
    public function actionAdd() : ? array
    {
        try {
            // get and check request parameters

            $params = $this->getParameters(['id', 'user', 'secret']);


            if (empty($params)) {
                $this->setStatus(500);
                return [
                    'error' => 'missing parameter'
                ];
            }

            if ( !$this->checkId($params['id']) )
            {
                $this->setStatus(500);
                return [
                    'error' => 'error id'
                ];
            }

            if( !$this->checkSecret( $params['secret'], [ $params['id'], $params['user'] ] ) )
            {
                $this->setStatus(500);
                return [
                    'error' => 'access denied'
                ];
            }

            $existing = $this->subscribers::find()->where(['user' => $params['user']])->count();

            if($existing)
            {
                $this->setStatus(500);
                return [
                    'error' => 'user already exists in database'
                ];
            }

            $this->subscribers->user = $params['user'];
            $this->subscribers->save();

            \Yii::$app->response->content = '';
            return null;
        }catch (\Exception $exception)
        {
            $this->setStatus(500);
            return [
                'error' => 'internal error'
            ];
        }
    }

    /**
     * return a list of tweets
     * @return array
     */
    public function actionFeed() : array
    {
        try
        {
            $params = $this->getParameters(['id', 'secret', 'count']);

            if(empty($params))
            {
                $this->setStatus(500);
                return [
                    'error' => 'missing parameter'
                ];
            }

            if ( !$this->checkId($params['id']) )
            {
                $this->setStatus(500);
                return [
                    'error' => 'error id'
                ];
            }

            if(!$this->checkSecret($params['secret'], [$params['id']]))
            {
                $this->setStatus(500);
                return [
                    'error' => 'access denied'
                ];
            }

            $subscribers = $this->subscribers::find()->select('user')->all();

            $tweets = [];

            foreach ($subscribers as $subscriber) {
                $data = TwitterPlugin::getTweets($subscriber->user, $params['count']);
                $tweets[] = [
                    'user'      => $subscriber->user,
                    'tweet'     => $data->text,
                    'hashtag'   => $data->entities->hashtags,
                ];
            }

            $this->setStatus(200);
            return $tweets;
        }catch (\Exception $exception)
        {
            $this->setStatus(500);
            return [
                'error' => 'internal error'
            ];
        }
    }

    /**
     * remove user from subscribers table
     * @return array|null
     * @throws \Throwable
     */
    public function actionRemove() : ? array
    {
        try
        {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $this->request = \Yii::$app->request;

            $params = $this->getParameters(['id', 'user', 'secret']);

            if( empty($params) )
            {
                $this->setStatus(500);
                return [
                    'error' => 'missing parameter'
                ];
            }

            if ( !$this->checkId($params['id']) )
            {
                $this->setStatus(500);
                return [
                    'error' => 'error id'
                ];
            }

            if( !$this->checkSecret($params['secret'], [ $params['id'], $params['user'] ]) )
            {
                $this->setStatus(500);
                return [
                    'error' => 'access denied'
                ];
            }

            $subscriber = Subscribers::
                find()
                ->where([ 'user' => $params['user'] ])
                ->one();

            if(!$subscriber)
            {
                $this->setStatus(500);
                return [
                    'error' => 'user not found'
                ];
            }

            $subscriber->delete();
            \Yii::$app->response->content = '';
            return null;
        }catch (\Exception $exception)
        {
            $this->setStatus(500);
            return [
                'error' => 'internal error'
            ];
        }
    }

    /**
     * Checks and gets parameters from a request
     * @param array $params
     * @return array
     */
    protected function getParameters(array $params) : array
    {
        $data = [];

        foreach ($params as $param) {

            $value = $this->request->get($param);

            if(!$value)
            {
                return [];
            }
            $data[$param] = $value;
        }

        return $data;
    }

    /**
     * Checks if secret parameter is right
     * @param string $secret
     * @param array $params
     * @return bool
     */
    private function checkSecret(string $secret, array $params) : bool
    {
        $check_string = implode('', $params);
        $check_revers_string = implode('',array_reverse($params));

        return $secret === sha1($check_string) || $secret === sha1($check_revers_string);
    }

    /**
     * @param int $status
     * @return \yii\console\Response|Response
     */
    private function setStatus(int $status)
    {
        return \Yii::$app->response->setStatusCode($status);
    }

    /**
     * Check if id is valid
     * @param string $id
     * @return bool
     */
    private function checkId(string $id) : bool
    {
        if (strlen($id) !== 32) {
            return false;
        }

        $user_session = $this->sessions::find()->where(['key' => $id])->count();

        if ($user_session)
        {
            return false;
        }

        $this->sessions->key =  $id;
        $this->sessions->save();

        return true;
    }
}