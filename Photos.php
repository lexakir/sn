<?php

namespace app\models;

use Yii;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "photos".
 *
 * @property string $id
 * @property string $filename
 * @property string $albumid
 * @property string $description
 * @property string $timestamp
 */
class Photos extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'photos';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['albumid', 'filename'], 'required'],
            [['albumid'], 'integer'],
            [['filename'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 32],
        ];
    }

  
	public function beforeDelete()
	{
		if (parent::beforeDelete()) {
			//Удалим файлы
			unlink(Yii::getAlias('@webroot'). $this->getFilePath());
			unlink(Yii::getAlias('@webroot'). $this->getThumbPath());
			return true;
		} else {
			return false;
		}
	}
	
	public function getFilePath(){
		return '/images/albums/'. $this->filename;
	}
	public function getThumbPath(){
		return '/images/albums/'. 'thumb_'.$this->filename;
	}
	public function getDate(){
		date_default_timezone_set('UTC');
		return date('d.m.Y',$this->timestamp);
	}
	
	public static function findByID($id){
		$photo=Photos::findOne($id);
		if(!$photo)
			throw new NotFoundHttpException('Фотографии с ID'. $id . ' не существует');
		return $photo;
	}
	
}
