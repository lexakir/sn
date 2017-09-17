<?php
namespace app\controllers;
use Yii;
use yii\web\Controller;
use app\models\user;
use app\models\Album;
use app\models\Photos;
use yii\web\UploadedFile;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

use yii\filters\AccessControl;

class AlbumController extends Controller
{
	//В этой переменной кешируется полученная модель альбома из бд
	private $_album=null;
	
	public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['edit','create','upload-photo','delete-photo','delete','update-photo-description','change-album-cover'],
                'rules' => [
					[
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => ['@'],
						'matchCallback' => function ($rule, $action) {
							if(!Yii::$app->request->isAjax)
								throw new ForbiddenHttpException('К методу ' . $action->id. ' возможен только ajax запрос');
							return true;
						}
                    ],
                    [
                        'allow' => true,
                        'actions' => ['edit'],
                        'roles' => ['@'],
						'matchCallback' => function ($rule, $action) {							
							return $this->userIsOwnerAlbum($_GET['id'],'Вы не можете редактировать чужой альбом');
						}
                    ],
					[
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['@'],
						'matchCallback' => function ($rule, $action) {
							if(!Yii::$app->request->isAjax)
								throw new ForbiddenHttpException('К методу ' . $action->id. ' возможен только ajax запрос');
							
							return $this->userIsOwnerAlbum(Yii::$app->request->post('albumid'),'Вы не можете удалить чужой альбом');
						}
                    ],
					//Как я понял к методу применяется только одно правило(которое описано первое)
					//Я хотел проверить метод upload-photo на ajax запрос в одном правиле
					//А в другом проверить наличие альбома с заданым id и принадлежность этого альбома авторизированному пользователю
					[
                        'allow' => true,
                        'actions' => ['upload-photo'],
                        'roles' => ['@'],
						'matchCallback' => function ($rule, $action) {
							if(!Yii::$app->request->isAjax)
								throw new ForbiddenHttpException('К методу ' . $action->id. ' возможен только ajax запрос');
							
							return $this->userIsOwnerAlbum(Yii::$app->request->post('albumid'),'Вы не можете загружать фотографии в чужой альбом');
						}
                    ],
					[
                        'allow' => true,
                        'actions' => ['delete-photo'],
                        'roles' => ['@'],
						'matchCallback' => function ($rule, $action) {
							if(!Yii::$app->request->isAjax)
								throw new ForbiddenHttpException('К методу ' . $action->id. ' возможен только ajax запрос');
							
							$photo=Photos::findByID(Yii::$app->request->post('id'));	
							return $this->userIsOwnerAlbum($photo->albumid,'Вы не можете удалять фотографии из чужого альбома');
						}
                    ],
					[
                        'allow' => true,
                        'actions' => ['update-photo-description'],
                        'roles' => ['@'],
						'matchCallback' => function ($rule, $action) {
							if(!Yii::$app->request->isAjax)
								throw new ForbiddenHttpException('К методу ' . $action->id. ' возможен только ajax запрос');
								
							$photo=Photos::findByID(Yii::$app->request->post('id'));				
							return $this->userIsOwnerAlbum($photo->albumid,'Вы не можете редактировать фотографии из чужого альбома');
						}
                    ],
					[
                        'allow' => true,
                        'actions' => ['change-album-cover'],
                        'roles' => ['@'],
						'matchCallback' => function ($rule, $action) {
							if(!Yii::$app->request->isAjax)
								throw new ForbiddenHttpException('К методу ' . $action->id. ' возможен только ajax запрос');
							
							return $this->userIsOwnerAlbum(Yii::$app->request->post('albumid'),'Вы не можете изменить обложку чужого альбома');
						}
                    ]
					
                ],		
            ],
        ];
    }
	//В правилах выше часто повторяются одни и теже действия
	//Можно их инкапсулировать в следующую функцию (я её ещё не использовал)
	//TODO: После проверки правил потом в методах контроллерах опять идет запрос к базе данных
	//чтобы получить нужную модель.Можно кешировать полученную при проверки модель во внутренней переменной,
	//плюс ещё этому что уменьшается количество кода в методах контроллера. 
	public function userIsOwnerAlbum($albumid,$error){
		//Проверим существует ли альбом с заданным идентификатором
		$album=$this->findAlbum($albumid);
		//Проверим, принадлежит ли альбом авторизированному пользователю
		if(!Yii::$app->user->can('editAlbum',['album' => $album]))
			throw new ForbiddenHttpException($error);
		return true;
	}
	//TODO:userIsOwnerPhoto?
	//Функция проверяет принадлежит ли альбом авторизированному пользователю
	//Устарело: нужно проверять принадлежность альбома пользователю через user->can
	//и потом самому выбрасывать исключение
	//Фишка использования rbac в том, что пользователь может быть админом, которому разрешено
	//редактировать любой альбом
	
	//Возвращает альбом с заданным идентификатором, если таковой существует.
	//Кеширует модель во внутренней переменной
	public function findAlbum($id){
		if(!$this->_album){
			$this->_album=Album::findByID($id);
		}
		return $this->_album;
	}
	
	public function actionCreate(){
		
		$model=new Album();
		
		if ($model->load(Yii::$app->request->post())){
			if($model->save()){
				//Перенаправляем на страницу просмотра только что созданного альбома
				Yii::$app->response->redirect("/album/view/$model->id");
			}	
		}
		
		return $this->renderAjax('createForm',['model' => $model]);
	}
	public function actionEdit($id){
		
		$model=$this->findAlbum($id);
		
		if ($model->load(Yii::$app->request->post())) {	
			$model->save();			
        }
		return $this->render('editForm',['model' => $model]);
	}
	
	public function actionView($id){
		
		/*$photos=photos::find()->all();
		//return count($photos);
		foreach($photos as $photo){
			
			$photo->delete();
		}*/
		$model=$this->findAlbum($id);
		return $this->render('view',['album' => $model]);
	}
	//Удаляет альбом и все его фотографии
	public function actionDelete(){
		$album=$this->findAlbum(Yii::$app->request->post('albumid'));
		$album->delete();
	}
	//Если не задан post параметр 'albumid' или альбом с этим идентификаторм не найден будет возвращена ошибка 404
	//Если альбом не принадлежит авторизированому пользователю будет возвращена ошибка 403
	public function actionUploadPhoto(){
		
		$imageFile=UploadedFile::getInstanceByName('image');
		
		$filename=time(). '_'. uniqid() . '.' . $imageFile->extension; //todo: generate unique name
		
		$album=$this->findAlbum(Yii::$app->request->post('albumid'));
		//Добавим в БД запись об фотографии
		$photo=$album->addPhoto($filename);
			
		$imagepath=$photo->getFilePath();
		$imageFile->saveAs(Yii::getAlias('@webroot'). $imagepath);
		
		//generate thumbnail todo:
		$im = imagecreatefromjpeg(Yii::getAlias('@webroot'). $imagepath);
		$w=imagesx($im);
		$h=imagesy($im);
		
		if( $w > 162 ){
			$width= 162;
			
			$ratio = $width / $w;
			$height = $h * $ratio;
	
			$new_image = imagecreatetruecolor($width, $height);
			imagecopyresampled($new_image, $im, 0, 0, 0, 0, $width, $height, $w, $h);
				
			imagejpeg($new_image, Yii::getAlias('@webroot'). $photo->getThumbPath());
		}	
		
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		
		$photo=photos::findOne(['filename' => $photo->filename]);
		
		return [ 'id' => $photo->id,'description' => $photo->description,
				 'date' => $photo->getDate(), 'path' => $photo->getFilePath(), 'thumbpath' => $photo->getThumbPath()  ];
	}
	public function actionDeletePhoto(){
		
		//В behaviors проверяется существует ли фотография с таким названием
		//Также проверяется находится ли эта фотография в альбоме, который принадлежит авторизированному пользователю
		//Стоит заметить что делаются дублирующие запросы к БД чтобы получить модели фотографии и альбома
		//(по одному разу в behaviors и тут)
		$photo=Photos::findOne(Yii::$app->request->post('id'));
		$album=$this->findAlbum($photo->albumid);
		$filename=$photo->filename;
		$photo->delete();
		
		$tmp=explode('/',$album->cover);
		if($tmp[count($tmp)-1] == $filename){
			if($album->getPhotoCount() > 0){
				$coverPhoto=Photos::find()->where(['albumid'=> $album->id])->one();
				$album->cover=$coverPhoto->getFilePath();
			}else{
				$album->cover='/images/empty_album.gif';
			}
			$album->save();
		}
		
		return $filename;
	}
	//Присваивает альбому новую обложку и заносит изменения в БД
	public function actionChangeAlbumCover(){
		$album=Album::findOne(Yii::$app->request->post('albumid'));
		//TODO: Не мешало бы сделать проверку принадлежит ли фотография пользователю
		//TODO: может сделать проверку существует ли такой файл?
		$photo=Photos::findByID(Yii::$app->request->post('id'));
		$album->cover=$photo->getFilePath();
		$album->save();
		return $album->cover;
	}
	//TODO: убрать возможность редактировать описание в чужом альбоме
	public function actionUpdatePhotoDescription(){
		$photo=Photos::findOne(Yii::$app->request->post('id'));
		$photo->description=Yii::$app->request->post('description');
		$photo->save(false);
	}
	public function actionGetPhotoDescription(){
		$photo=Photos::findOne(Yii::$app->request->post('id'));
		return $photo->description;
	}
}

