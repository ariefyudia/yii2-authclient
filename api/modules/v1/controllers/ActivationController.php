<?php

namespace api\modules\v1\controllers;

use yii\rest\Controller;
use Yii;
use api\modules\v1\models\AppInstalled;
use common\models\UserSetting;

/**
 * Class ActivationController
 * @package rest\versions\v1\controllers
 */
class ActivationController extends Controller
{

    /**
     * This method implemented to demonstrate the receipt of the token.
     * @return string ActivationCode
     */

    public function actionCreate(){
        $post = \Yii::$app->request->post();

        $response = [];

        $serialnumber = !empty($post['serialnumber'])?$post['serialnumber']:'';

        if(empty($serialnumber)) {
            $response = [
                'status' => '404',
                'message' => 'Serial Number tidak boleh kosong',
                'data' => ''
            ];
        } else {
            $user = \common\models\User::findByUsername($serialnumber);

            if(!empty($user)) {
                $setparameter = Yii::$app->db->createCommand(
                  " UPDATE settingparameters SET Value = '".$post['namaperpustakaan']."' WHERE Name = 'NamaPerpustakaan';
                    UPDATE settingparameters SET Value = '".$post['alamatperpustakaan']."' WHERE Name = 'NamaLokasiPerpustakaan';
                    UPDATE settingparameters SET Value = '".$post['jenisperpustakaan']."' WHERE Name = 'JenisPerpustakaan';
                    UPDATE settingparameters SET Value = '".$post['domain']."' WHERE Name = 'Domain';
                    UPDATE settingparameters SET Value = '".$post['serialnumber']."' WHERE Name = 'SerialNumber';
                    UPDATE settingparameters SET Value = '".$post['authkey']."' WHERE Name = 'AuthKey';
                    UPDATE settingparameters SET Value = '".$post['ftphost']."' WHERE Name = 'FtpHost';
                    UPDATE settingparameters SET Value = '".$post['ftpusername']."' WHERE Name = 'FtpUsername';
                    UPDATE settingparameters SET Value = '".$post['ftppassword']."' WHERE Name = 'FtpPassword';
                    UPDATE settingparameters SET Value = '".$post['ftpfolder']."' WHERE Name = 'FtpFolder';
                  "
                )->execute();
                
                $response = [
                    'status' => '400',
                    'message' => 'Data sudah terdaftar',
                    'data' => ''
                ];
            } else {
                $model = new UserSetting;
                $model->username = $serialnumber;
                $model->password = sha1($serialnumber);
                $model->Fullname = $serialnumber;
                $model->IsCanResetUserPassword = 1;
                
                if($model->save()) {
                    $assignment = Yii::$app->db->createCommand(
                        "SELECT count(1) as assign FROM auth_assignment
                        WHERE (item_name = 'superadmin' AND user_id = '".$model->ID."') OR (item_name = 'opac' AND user_id = '".$model->ID."')"
                    )->queryOne();
                    if($assignment['assign'] == 0) {
                        $insert = Yii::$app->db->createCommand("
                            INSERT INTO auth_assignment SET item_name = 'superadmin', user_id = '".$model->ID."';
                            INSERT INTO auth_assignment SET item_name = 'Opac', user_id = '".$model->ID."';
                        ")->execute();
                    }

                    $setparameter = Yii::$app->db->createCommand(
                      " UPDATE settingparameters SET Value = '".$post['namaperpustakaan']."' WHERE Name = 'NamaPerpustakaan';
                        UPDATE settingparameters SET Value = '".$post['alamatperpustakaan']."' WHERE Name = 'NamaLokasiPerpustakaan';
                        UPDATE settingparameters SET Value = '".$post['jenisperpustakaan']."' WHERE Name = 'JenisPerpustakaan';
                        UPDATE settingparameters SET Value = '".$post['domain']."' WHERE Name = 'Domain';
                        UPDATE settingparameters SET Value = '".$post['serialnumber']."' WHERE Name = 'SerialNumber';
                        UPDATE settingparameters SET Value = '".$post['authkey']."' WHERE Name = 'AuthKey';
                        UPDATE settingparameters SET Value = '".$post['ftphost']."' WHERE Name = 'FtpHost';
                        UPDATE settingparameters SET Value = '".$post['ftpusername']."' WHERE Name = 'FtpUsername';
                        UPDATE settingparameters SET Value = '".$post['ftppassword']."' WHERE Name = 'FtpPassword';
                        UPDATE settingparameters SET Value = '".$post['ftpfolder']."' WHERE Name = 'FtpFolder';
                      "
                    )->execute();
                    
                    $response = [
                        'status' => '201',
                        'message' => 'Data berhasil disimpan ke database',
                        'data' => ''
                    ];
                } else {
                    $response = [
                        'status' => '400',
                        'message' => 'Data gagal disimpan ke database',
                        'data' => ''
                    ];
                }
            }
        }

        return $response;
    }

    
}