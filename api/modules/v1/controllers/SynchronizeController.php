<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\rest\Controller;

use common\components\DirectoryHelpers;

set_time_limit(0);
ini_set("memory_limit", "-1");

class SynchronizeController extends Controller
{
	public function actionCreate() {
		$post = \Yii::$app->request->post();

		$response = [];

		if($post['serialnumber'] == '') {
			$response = [
	            'status' => '404',
	            'message' => 'Not Found',
	            'data' => ''
	        ];
		} else {
			if($post['serialnumber'] == Yii::$app->config->get('SerialNumber')) {
				if(isset($post['uploadid'])) {
					$check_upload = Yii::$app->db->createCommand('SELECT id FROM upload WHERE id = :uploadid');
					$check_upload->bindValue(':uploadid', $post['uploadid']);
					$data = $check_upload->queryOne();

					if($data) {
						$count = Yii::$app->db->createCommand('
							SELECT COUNT(1) AS total FROM catalogs
							UNION ALL
							SELECT COUNT(1) AS total FROM collections
							UNION ALL 
							SELECT COUNT(1) AS total FROM serial_articles
						')->queryAll();


						// count total file cover
						$dir = Yii::getAlias('@uploaded_files/sampul_koleksi/original');
	        
				        // $cover = 0; $bytes = 0;
				        // DirectoryHelpers::find($dir, function($file) use (&$cover, &$bytes) {
				        //     // the closure updates count and bytes so far
				        //     ++$cover;
				        //     $bytes += filesize($file);
				        // }, 1);

				        $countFile = Yii::$app->db->createCommand('SELECT SUM(totalfile) AS total FROM upload WHERE tipe = "file" OR tipe = "client_cover"')->queryAll();

				        // $totalCover = 0;
				        if($countFile[0]['total'] == NULL) {
				        	$totalCover = 0;
				        } else {
				        	$totalCover = $countFile[0]['total'];
				        }

				        // set all total in table total
				        $updateTotal = Yii::$app->db->createCommand('UPDATE total SET total_catalog = "'.$count[0]['total'].'", total_collection = "'.$count[1]['total'].'", total_article = "'.$count[2]['total'].'", total_cover = "'.$totalCover.'" WHERE `name` = "Total"')->execute();

				        // check status service elasticsearch
				        $ch = curl_init(); 

				        // set url 
				        curl_setopt($ch, CURLOPT_URL, "127.0.0.1:9200");

				        // return the transfer as a string 
				        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

				        // $output contains the output string 
				        $output = curl_exec($ch); 

				        // tutup curl 
				        curl_close($ch);      

				        if($output) {
				            $path = dirname(dirname(dirname(dirname(__DIR__))));

							$start_php = dirname(dirname($_SERVER['MIBDIRS'])).'/php.exe';
			                // $exec = $start_php.' '.$path. '/yii indexing/start '.$post['uploadid'].' '.$post['indexing'];
			                // $exec = exec($start_php.' '.$path. '/yii indexing/start '.$post['uploadid'].' '.$post['indexing']);
			                $exec = exec($start_php.' '.$path. '/yii indexing');

			                $response = [
					            'status' => '200',
					            'message' => 'ok',
					            'data' => $totalCover
					        ];
				        } else {
				            $response = [
					            'status' => '400',
					            'message' => 'Data berhasil disimpan, Servis Elastic stop',
					            'data' => ''
					        ];
				        }

					} else {
						$response = [
				            'status' => '404',
				            'message' => 'Failed',
				            'data' => ''
				        ];
					}
				}
				
				if(isset($post['firstsynchronize'])) {
					if($post['firstsynchronize'] == '1') {
						$this->createTrigger();

						$response = [
				            'status' => '200',
				            'message' => 'Create Trigger Success',
				            'data' => ''
				        ];
					} else {
						$response = [
				            'status' => '400',
				            'message' => 'Not Execute Create Trigger',
				            'data' => ''
				        ];
					}
					
				}
				
			} else {
				$response = [
		            'status' => '404',
		            'message' => 'Not Found',
		            'data' => $post
		        ];
			}
			
		}

		

        return $response;
	}

	public function createTrigger() 
	{
		$err=[];

		$trans = Yii::$app->db->beginTransaction();
        try { 
        	// catalog_before_insert
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `catalogs_before_insert`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
				    /*!50017 DEFINER = 'root'@'localhost' */
				    TRIGGER `catalogs_before_insert` BEFORE INSERT ON `catalogs` 
				    FOR EACH ROW BEGIN
					IF (EXISTS(SELECT 1 FROM catalogs WHERE ID = NEW.ID)) THEN
					   INSERT INTO upload_catalogs
					   ( catID, uploadID, `status`, tipe, CreateDate, CreateBy)
					   VALUES
					   ( NEW.ID, @uploadID, '0', 'insert', SYSDATE(), @userID );
					END IF;
				    END;
        		")->execute();

        	// catalog_after_insert
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `catalogs_after_insert`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `catalogs_after_insert` AFTER INSERT ON `catalogs` 
			    FOR EACH ROW BEGIN
				INSERT INTO upload_catalogs ( catID, uploadID, `status`, tipe, CreateDate, CreateBy)
				VALUES
				( NEW.ID, @uploadID, '1', 'insert', SYSDATE(), @userID );
				     
				
				 
			    END;
        		")->execute();

        	// catalog_after_update
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `catalog_after_update`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `catalog_after_update` AFTER UPDATE ON `catalogs` 
			    FOR EACH ROW BEGIN
				INSERT INTO upload_catalogs ( catID, uploadID, `status`, tipe, CreateDate, CreateBy)
				VALUES
				( NEW.ID, @uploadID, '1', 'update', SYSDATE(), @userID );
				     
				
				 
			    END;
        		")->execute();

        	// total_catalogs
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `total_catalogs`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `total_catalogs` AFTER INSERT ON `upload_catalogs` 
			    FOR EACH ROW BEGIN
				-- CHECK TOTAL ID EXISTS OR NOT.
			        DECLARE totals_success INT;
			        DECLARE totals_failed INT;
			        DECLARE total INT;
			        
			        
			        SELECT COUNT(id) INTO total FROM upload_total_catalog WHERE upload_total_catalog.`uploadID`=@uploadID;
			        IF total=0 THEN
					INSERT INTO upload_total_catalog (uploadID) VALUES (@uploadID);
			        ELSE
					-- INSERT
					SELECT COUNT(id) INTO totals_success FROM upload_catalogs WHERE upload_catalogs.uploadID=@uploadID AND upload_catalogs.status = 1 AND upload_catalogs.tipe = 'insert';
					IF totals_success = 0 THEN
						UPDATE upload_total_catalog SET insert_success=totals_success WHERE upload_total_catalog.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_catalog SET insert_success=totals_success WHERE upload_total_catalog.uploadID=@uploadID;
					END IF;
					
					SELECT COUNT(id) INTO totals_failed FROM upload_catalogs WHERE upload_catalogs.uploadID=@uploadID AND upload_catalogs.status = 0  AND upload_catalogs.tipe = 'insert';
					IF totals_failed = 0 THEN
						UPDATE upload_total_catalog SET insert_failed=totals_failed WHERE upload_total_catalog.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_catalog SET insert_failed=totals_failed WHERE upload_total_catalog.uploadID=@uploadID;
					END IF;
					
					
					-- UPDATE
					SELECT COUNT(id) INTO totals_success FROM upload_catalogs WHERE upload_catalogs.uploadID=@uploadID AND upload_catalogs.status = 1 AND upload_catalogs.tipe = 'update';
					IF totals_success = 0 THEN
						UPDATE upload_total_catalog SET update_success=totals_success WHERE upload_total_catalog.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_catalog SET update_success=totals_success WHERE upload_total_catalog.uploadID=@uploadID;
					END IF;
					
					SELECT COUNT(id) INTO totals_failed FROM upload_catalogs WHERE upload_catalogs.uploadID=@uploadID AND upload_catalogs.status = 0  AND upload_catalogs.tipe = 'update';
					IF totals_failed = 0 THEN
						UPDATE upload_total_catalog SET update_failed=totals_failed WHERE upload_total_catalog.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_catalog SET update_failed=totals_failed WHERE upload_total_catalog.uploadID=@uploadID;
					END IF;
					
			        END IF;
			        
			    END;
        		")->execute();

        	// collection_before_insert
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `collection_before_insert`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `collection_before_insert` BEFORE INSERT ON `collections` 
			    FOR EACH ROW BEGIN
				IF (EXISTS(SELECT 1 FROM collections WHERE ID = NEW.ID)) THEN
				   INSERT INTO upload_collections
				   ( colID, uploadID, `status`, tipe, CreateDate, CreateBy)
				   VALUES
				   ( NEW.ID, @uploadID, '0', 'insert', SYSDATE(), @userID );
				END IF;
			    END;
        		")->execute();

        	// collection_after_insert
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `collection_after_insert`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `collection_after_insert` AFTER INSERT ON `collections` 
			    FOR EACH ROW BEGIN
				INSERT INTO upload_collections
				   ( colID, uploadID, `status`, tipe, CreateDate, CreateBy)
				   VALUES
				   ( NEW.ID, @uploadID, '1', 'insert', SYSDATE(), @userID );
			    END;
        		")->execute();

        	// collection_after_update
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `collection_after_update`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `collection_after_update` AFTER UPDATE ON `collections` 
			    FOR EACH ROW BEGIN
				INSERT INTO upload_collections ( colID, uploadID, `status`, tipe, CreateDate, CreateBy)
				VALUES
				( NEW.ID, @uploadID, '1', 'update', SYSDATE(), @userID );
				     
				
				 
			    END;
        		")->execute();

        	// total_collections
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `total_collections`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `total_collections` AFTER INSERT ON `upload_collections` 
			    FOR EACH ROW BEGIN
				-- CHECK TOTAL ID EXISTS OR NOT.
			        DECLARE totals_success INT;
			        DECLARE totals_failed INT;
			        DECLARE total INT;
			        
			        SELECT COUNT(id) INTO total FROM upload_total_collection WHERE upload_total_collection.`uploadID`=@uploadID;
			        IF total=0 THEN
					INSERT INTO upload_total_collection (uploadID) VALUES (@uploadID);
			        ELSE
					-- INSERT
					SELECT COUNT(id) INTO totals_success FROM upload_collections WHERE upload_collections.uploadID=@uploadID AND upload_collections.status = 1 AND upload_collections.tipe = 'insert';
					IF totals_success = 0 THEN
						UPDATE upload_total_collection SET insert_success=totals_success WHERE upload_total_collection.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_collection SET insert_success=totals_success WHERE upload_total_collection.uploadID=@uploadID;
					END IF;
					
					SELECT COUNT(id) INTO totals_failed FROM upload_collections WHERE upload_collections.uploadID=@uploadID AND upload_collections.status = 0 AND upload_collections.tipe = 'insert';
					IF totals_failed = 0 THEN
						UPDATE upload_total_collection SET insert_failed=totals_failed WHERE upload_total_collection.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_collection SET insert_failed=totals_failed WHERE upload_total_collection.uploadID=@uploadID;
					END IF;
					
					-- UPDATE
					SELECT COUNT(id) INTO totals_success FROM upload_collections WHERE upload_collections.uploadID=@uploadID AND upload_collections.status = 1 AND upload_collections.tipe = 'update';
					IF totals_success = 0 THEN
						UPDATE upload_total_collection SET update_success=totals_success WHERE upload_total_collection.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_collection SET update_success=totals_success WHERE upload_total_collection.uploadID=@uploadID;
					END IF;
					
					SELECT COUNT(id) INTO totals_failed FROM upload_collections WHERE upload_collections.uploadID=@uploadID AND upload_collections.status = 0 AND upload_collections.tipe = 'update';
					IF totals_failed = 0 THEN
						UPDATE upload_total_collection SET update_failed=totals_failed WHERE upload_total_collection.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_collection SET update_failed=totals_failed WHERE upload_total_collection.uploadID=@uploadID;
					END IF;
			        END IF;
			    END;
        		")->execute();

        	// article_before_insert
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `article_before_insert`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `article_before_insert` BEFORE INSERT ON `serial_articles` 
			    FOR EACH ROW BEGIN
				IF (EXISTS(SELECT 1 FROM serial_articles WHERE id = NEW.id)) THEN
				   INSERT INTO upload_articles
				   ( artikelID, uploadID, `status`, tipe, CreateDate, CreateBy)
				   VALUES
				   ( NEW.id, @uploadID, '0', 'insert', SYSDATE(), @userID );
				END IF;
			    END;
        		")->execute();

        	// article_after_insert
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `article_after_insert`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `article_after_insert` AFTER INSERT ON `serial_articles` 
			    FOR EACH ROW BEGIN
				INSERT INTO upload_articles
				   ( artikelID, uploadID, `status`, tipe, CreateDate, CreateBy)
				   VALUES
				   ( NEW.id, @uploadID, '1', 'insert', SYSDATE(), @userID );
			    END;
        		")->execute();

        	// article_after_update
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `article_after_update`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `article_after_update` AFTER UPDATE ON `serial_articles` 
			    FOR EACH ROW BEGIN
				INSERT INTO upload_articles ( artikelID, uploadID, `status`, tipe, CreateDate, CreateBy)
				VALUES
				( NEW.ID, @uploadID, '1', 'update', SYSDATE(), @userID );
			    END;
        		")->execute();

        	// total_articles
        	$command = Yii::$app->db->createCommand("
                DROP TRIGGER IF EXISTS `total_articles`;
            ")->execute();

        	$command = Yii::$app->db->createCommand("
        		CREATE
			    /*!50017 DEFINER = 'root'@'localhost' */
			    TRIGGER `total_articles` AFTER INSERT ON `upload_articles` 
			    FOR EACH ROW BEGIN
				DECLARE totals_success INT;
			        DECLARE totals_failed INT;
			        DECLARE total INT;
			        
			        SELECT COUNT(id) INTO total FROM upload_total_article WHERE upload_total_article.`uploadID`=@uploadID;
			        IF total=0 THEN
					INSERT INTO upload_total_article (uploadID) VALUES (@uploadID);
			        ELSE
					-- INSERT
					SELECT COUNT(id) INTO totals_success FROM upload_articles WHERE upload_articles.uploadID=@uploadID AND upload_articles.status = 1 AND upload_articles.`tipe` = 'insert';
					IF totals_success = 0 THEN
						UPDATE upload_total_article SET insert_success=totals_success WHERE upload_total_article.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_article SET insert_success=totals_success WHERE upload_total_article.uploadID=@uploadID;
					END IF;
					
					SELECT COUNT(id) INTO totals_failed FROM upload_articles WHERE upload_articles.uploadID=@uploadID AND upload_articles.status = 0 AND upload_articles.`tipe` = 'insert';
					IF totals_failed = 0 THEN
						UPDATE upload_total_article SET insert_failed=totals_failed WHERE upload_total_article.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_article SET insert_failed=totals_failed WHERE upload_total_article.uploadID=@uploadID;
					END IF;
					
					-- UPDATE
					SELECT COUNT(id) INTO totals_success FROM upload_articles WHERE upload_articles.uploadID=@uploadID AND upload_articles.status = 1 AND upload_articles.`tipe` = 'update';
					IF totals_success = 0 THEN
						UPDATE upload_total_article SET update_success=totals_success WHERE upload_total_article.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_article SET update_success=totals_success WHERE upload_total_article.uploadID=@uploadID;
					END IF;
					
					SELECT COUNT(id) INTO totals_failed FROM upload_articles WHERE upload_articles.uploadID=@uploadID AND upload_articles.status = 0 AND upload_articles.`tipe` = 'update';
					IF totals_failed = 0 THEN
						UPDATE upload_total_article SET update_failed=totals_failed WHERE upload_total_article.uploadID=@uploadID;
					ELSE 
						UPDATE upload_total_article SET update_failed=totals_failed WHERE upload_total_article.uploadID=@uploadID;
					END IF;
					
			        END IF;
			    END;
        		")->execute();

        } catch (\Exception $e) {
            if ($e->errorInfo[2]) {
                array_push($err, $e->errorInfo[2]);
            }

            $trans->rollback();
        }
	}
}