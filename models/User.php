<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $status
 * @property string $updated_at
 * @property string|null $created_at
 * @property string $auth_key
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'email', 'password', 'auth_key'], 'required'],
            [['status'], 'string'],
            [['updated_at', 'created_at'], 'safe'],
            [['first_name', 'last_name'], 'string', 'max' => 150],
            [['email'], 'string', 'max' => 200],
            [['password', 'auth_key'], 'string', 'max' => 250],
            [['email'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'password' => 'Password',
            'status' => 'Status',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
            'auth_key' => 'Auth Key',
        ];
    }

    public static function findIdentity($id){
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null){
        return static::findOne(['access_token' => $token]);
    }

    public function getId(){
        return $this->id;
    }

    public function getAuthKey(){
        return $this->auth_key;
    }

    public function validateAuthKey($auth_key){
        return $this->auth_key === $auth_key;
    }

    public static function findByEmail($email) {
        return self::findOne(['email' => $email]);
    }

    public function validatePassword($email) {
        return $this->password === md5($email);
    }
}
