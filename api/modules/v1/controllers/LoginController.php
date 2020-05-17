<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class ActivationController
 * @package rest\versions\v1\controllers
 */
class LoginController extends Controller
{
    public function actionIndex()
    {
        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        die;
    }

	public function actionCreate(){
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		$post = \Yii::$app->request->post();

        $response = ['results' => ['status' => '', 'message' => '']];
        if(isset($post['socialid'])) {
            $response = ['results' => [
                    'status' => 200, 
                    'message' => 'Login success'
                ]
            ];
        } else {
            
            $response = ['results' => [
                    'status' => 200, 
                    'message' => $post
                ]
            ];
        }

        return $response;
	}
}