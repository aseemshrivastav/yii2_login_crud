<?php
namespace app\components;

use app\models\Auth;
use app\models\User;
use Yii;
use yii\authclient\ClientInterface;
use yii\helpers\ArrayHelper;


/**
 * AuthHandler handles successful authentication via Yii auth component
 */
class AuthHandler
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function handle()
    {
        $attributes = $this->client->getUserAttributes();
        
        $email = $id = $fullname = $firstname = $lastname = "";
        $source = $this->client->getId();
        if($source == "google"){
            $email = $attributes['email'];
            $id = $attributes['id'];
            $fullname = $attributes['name'];
            $firstname = $attributes['given_name'];
            $lastname = $attributes['family_name'];
        }else if($source == "live"){
            $email = $attributes['emails']['account'];
            $id = $attributes['id'];
            $fullname = $attributes['name'];
            $firstname = $attributes['first_name'];
            $lastname = $attributes['last_name'];
        }else if($source == "facebook"){
            $email = $attributes['email'];
            $id = $attributes['id'];
            $fullname = $attributes['name'];
            $nameArr = explode(" ", $attributes['name']);
            $firstname = $nameArr[0];
            $lastname = $nameArr[1];
        }

        
        $user = User::find()->where(['email' => $email])->one();
        if($user){
            $auth = Auth::find()->where([
                'source' => $source,
                'source_id' => $id,
            ])->one();
            
            if ($auth && !Yii::$app->user->isGuest) {
                $user = $auth->user;
                Yii::$app->user->login($user);
            }else{
                $user->updated_at = date('Y-m-d H:i:s');
                $user->status = '1';
                $transaction = User::getDb()->beginTransaction();
                if ($user->save()) {
                    $auth = new Auth([
                        'user_id' => $user->id,
                        'source' => $source,
                        'source_id' => (string)$id,
                    ]);
                    if ($auth->save()) {
                        $transaction->commit();
                        Yii::$app->user->login($user);
                        setcookie("auToken", $user->auth_key, time()+86400, '/', 'myloapp.in');
                    } else {
                        print_R($auth->getErrors()); exit;
                        Yii::$app->getSession()->setFlash('error', [
                            Yii::t('app', 'Unable to save {client} account: {errors}', [
                                'client' => $this->client->getTitle(),
                                'errors' => json_encode($auth->getErrors()),
                            ]),
                        ]);
                    }
                }else{
                    print_R($user->getErrors()); exit;
                }
            }
        }else{
            $user = new User();
            $user->first_name = $firstname;
            $user->last_name = $lastname;
            $user->email = $email;
            $user->password = md5($email);
            $user->auth_key = Yii::$app->security->generateRandomString(12);
            $transaction = User::getDb()->beginTransaction();
            if($user->save()){
                $auth = new Auth([
                    'user_id' => $user->id,
                    'source' => $source,
                    'source_id' => (string)$id,
                ]);
                if ($auth->save()) {
                    $transaction->commit();
                    Yii::$app->user->login($user);
                    setcookie("auToken", $user->auth_key, time()+86400, '/', 'myloapp.in');
                } else {
                    print_R($auth->getErrors()); exit;
                    Yii::$app->getSession()->setFlash('error', [
                        Yii::t('app', 'Unable to save {client} account: {errors}', [
                            'client' => $this->client->getTitle(),
                            'errors' => json_encode($auth->getErrors()),
                        ]),
                    ]);
                }
            }else{
                print_R($user->getErrors()); exit;
            }
        }
    }
}