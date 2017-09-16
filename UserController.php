<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\user;
use app\models\Album;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException; 
use yii\web\UnauthorizedHttpException;

class UserController extends Controller
{
	//Возвращает пользователя с заданным идентификатором, если таковой существует.
	//Иначе выбрасывает исключение.
	public function findUser($id){
		$user=user::findOne($id);
		
		if(!$user){
			throw new NotFoundHttpException('Пользователь с id='. $id . ' не существует');		
		}	
		return $user;
	}
	//Отображает профиль пользователя с заданным идентификатором
	public function actionProfile($id = null){
			
		$user=$this->findUser($id);
			
		return $this->render('profile',['user' => $user]);
	}
	//Отображает альбомы, пользователя c заданным id
	public function actionAlbums($id = null){
		//Сперва проверить, существует ли пользователь с заданным идентификатором
		$user=$this->findUser($id);
		//Найти все альбомы пользователя
		$albums=Album::find()->where(['userid'=> $id])->all();
		//узнаем просматривает пользователь свою страницу или чужую
		$userOwner=((!Yii::$app->user->isGuest && Yii::$app->user->identity->id == $id)?1:0);
	
		return $this->render('albums',['userOwner' => $userOwner,'albums' => $albums]);
	}		
	
	public function actionUpdateAvatar(){
		if(Yii::$app->user->isGuest)
			return;
		
		 if (Yii::$app->request->isAjax) {
			$imgBase64=$_POST['imgBase64'];
			$imgBase64=substr($imgBase64,strpos($imgBase64,',')+1);
			$imgBase64=str_replace(' ', '+', $imgBase64);
			$imageName='/avatars/cropped_'.uniqid().'.jpg';
			
			file_put_contents(Yii::getAlias('@webroot'). $imageName, base64_decode($imgBase64));
			
			$user=user::findOne(Yii::$app->user->identity->id);
			//TODO: удалить старый аватар
			$user->avatarImage=$imageName;		
			$user->save(false);
			
			return $imageName;
			/*
			$width=$_POST['width'];
			$height=$_POST['height'];
			$x1=$_POST['x1'];
			$y1=$_POST['y1'];
				
			$imageFile=	UploadedFile::getInstanceByName('image');
			
			
			$im = imagecreatefromjpeg($imageFile->tempName);
		
			$im2 = imagecrop($im, ['x' => $x1, 'y' => $y1, 'width' => $width, 'height' => $height]);
			if ($im2 !== FALSE) {
				
				imagejpeg($im2, $_SERVER['DOCUMENT_ROOT']. $imagepath);
			}
			return $imagepath;*/
		}	
	}
	
	public function actionUpload(){
		
		 if (Yii::$app->request->isPost) {
			
			 
            $imageFile = UploadedFile::getInstanceByName('image');
			
			$imagepath='/basic/web/images/' . $imageFile->baseName . '.' . $imageFile->extension;
			
			$imageFile->saveAs($_SERVER['DOCUMENT_ROOT']. $imagepath);
			
			$im = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT']. $imagepath);
			$w=imagesx($im);
			$h=imagesy($im);
			
			if( $w >550 ){
				$width=550;
				
				$ratio = $width / $w;
				$height = $h * $ratio;
		
		
				$new_image = imagecreatetruecolor($width, $height);
				imagecopyresampled($new_image, $im, 0, 0, 0, 0, $width, $height, $w, $h);
							
				imagejpeg($new_image, $_SERVER['DOCUMENT_ROOT']. $imagepath);
			}
			
			return $imagepath;
            //if ($model->upload()) {
                // file is uploaded successfully
                //return;
           // }
        }
		return 'upload';
	}
	
}

?>