<?php
/**
 * IndexController
 */
require_once 'Zend/Controller/Action.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Session.php';

require_once '../application/modules/default/models/MainModel.php';

class IndexController extends Zend_Controller_Action
{
    private $_config;                         // 設定情報
    private $_session;                        // セッションのインスタンス
    private $_lang;                           // 言語設定
    private $_contents;                       // 言語データ
    private $_main;                           // モデルのインスタンス

    public function init()
    {
        /**
         * ドメイン設定とスペースの確認
         */
        // 基本セッションを構築

        $this->_session = new Zend_Session_Namespace('data');

        // 設定情報をロードする
        $this->_config = new Zend_Config_Ini('../application/modules/default/lib/config.ini', null);

        /**
         * DBの接続
         */

        $db_rand = rand(1,2);
        if ($db_rand == 2) {
            $db_read = $this->_config->datasourceread2->database->toArray();
        } else {
            $db_read = $this->_config->datasourceread1->database->toArray();
        }

        $db_write = $this->_config->datasource->database->toArray();

        // モデルのインスタンスを生成する
        $this->_main = new MainModel($db_read,$db_write);

        /**
         * 言語データを取得する
         */
        // 言語指定を確認

        if ($this->getRequest()->getParam('la')) {
            $this->_session->lang = $this->getRequest()->getParam('la');
        } elseif(!$this->_session->lang) {
            $this->_session->lang = 'ja';
        }
        $this->_lang = $this->_session->lang;

        /**
         * Viewに必要データを渡す
         */

        // メッセージがあればviewに渡す
        $this->view->successMsg = $this->_session->successMsg;
        $this->view->errMsg = $this->_session->errMsg;
        unset($this->_session->successMsg);
        unset($this->_session->errMsg);

        // pathとユーザー情報をviewに渡す
        $this->view->path       = $this->getRequest()->getPathInfo();
        $this->view->lang       = $this->_session->lang;

        //$this->_helper->layout->setLayout('index');

    }


    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    private function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Calendar API PHP Quickstart');
        $client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
        $client->setAuthConfig('client_secret.json');
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentialsPath = expandHomeDirectory('credentials.json');
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    private function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

    public function indexAction()
    {
        $starttime = array("13:00", "15:00", "17:00");
        $endtime = array("15:00", "17:00", "19:00");
        $date = array();
        for ($i=7; $i<15; $i++) {
            for ($j=0; $j<count($starttime); $j++) {
                $_date = array();
                $t =  new DateTime("2018-5-".(string)$i." ".$starttime[$j]);
                array_push($_date, $t->format('Y-m-d H:i'));
                $t =  new DateTime("2018-5-".(string)$i." ".$endtime[$j]);
                array_push($_date, $t->format('Y-m-d H:i'));
                array_push($date, $_date);
            }
        }
//        echo "<pre>";
//        var_dump($date);
//        echo "</pre>";
        $this->view->date = $date;
        $this->view->dDate = json_encode($date);

        $this->view->startTime = array(
            "year" => substr($date[0][0], 0, 4),
            "month" => substr($date[0][0], 5, 2),
            "day" => substr($date[0][0], 8, 2),
        );
        $num = count($date[count($date)-1])-1;

        $this->view->endTime = array(
            "year" => substr($date[count($date)-1][$num], 0, 4),
            "month" => substr($date[count($date)-1][$num], 5, 2),
            "day" => substr($date[count($date)-1][$num], 8, 2),
        );
//        var_dump($this->view->endTime);


// Get the API client and construct the service object.
//        $client = $this->getClient();
//        $service = new Google_Service_Calendar($client);
//
//// Print the next 10 events on the user's calendar.
//        $calendarId = 'primary';
//        $optParams = array(
//            'maxResults' => 10,
//            'orderBy' => 'startTime',
//            'singleEvents' => true,
//            'timeMin' => date('c'),
//        );
//        $results = $service->events->listEvents($calendarId, $optParams);
//
//        if (empty($results->getItems())) {
//            print "No upcoming events found.\n";
//        } else {
//            print "Upcoming events:\n";
//            foreach ($results->getItems() as $event) {
//                $start = $event->start->dateTime;
//                if (empty($start)) {
//                    $start = $event->start->date;
//                }
//                printf("%s (%s)\n", $event->getSummary(), $start);
//            }
//        }

    }




}