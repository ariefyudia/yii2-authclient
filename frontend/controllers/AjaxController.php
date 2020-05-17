<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Query;
use \yii\web\UploadedFile;
use yii\db\ActiveQuery;
use yii\helpers\Url;

/**
 * AjaxController implements the CRUD actions for AjaxItems model.
 */
class AjaxController extends \yii\web\Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    //'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * Lists all AjaxItems models.
     * @return mixed
     */
    public function actionIndex()
    {
        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        die;
    }

    /*
     Category List di add project
     */
    public function actionCategorylist($q = null, $id = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        if (!is_null($q)) {
            $query = new \yii\db\Query;
            $query->select('id, title AS text')
                ->from('master_category')
                ->where(['like', 'title', $q])
                ->limit(20);
            $command = $query->createCommand();
            $data = $command->queryAll();
            $out['results'] = array_values($data);
        }
        elseif(is_null($q) && !$id){
            $query = new \yii\db\Query;
            $query->select('id, title AS text')
                ->from('master_category')
                ->limit(20);
            $command = $query->createCommand();
            $data = $command->queryAll();
            $out['results'] = array_values($data);
        }
        elseif ($id > 0) {
            $out['results'] = ['id' => $id, 'text' => \frontend\models\MasterCategory::find($id)->title];
        }
        return $out;
    }

    

}
