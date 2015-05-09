<?php
    require_once 'key.php';
    require_once 'twitteroauth.php';

    $strRel = '../../../';
    include($strRel . 'config.php');
    include($DIR_LIBS . 'PLUGINADMIN.php');
    $oPluginAdmin = new PluginAdmin('twiitem');

    if (!($member->isLoggedIn() && $member->isAdmin())){
	    $oPluginAdmin->start();
	    echo '<p>' . _ERROR_DISALLOWED . '</p>';
	    $oPluginAdmin->end();
	    exit;
    }

    $memberid = $member->getID();
    session_start();

    $action = getVar('action');
    switch ($action) {
        case 'callback':
            if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
            if(isset($_GET['denied']) && !empty($_GET['denied'])){
                $message = '<p>Twitterアカウント情報の取得がキャンセルされました。</p>';
                break;
            }
            if(!isset($_SESSION['oauth_token']) && empty($_SESSION['oauth_token'])){
                $message = '<p>無効なアクセスです。最初からやり直してください。</p>';
                break;
            }

            $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
            $access_token = $connection->getAccessToken($_GET['oauth_verifier']);
            if (empty($access_token)){
                $message = '<p>Twitterアカウント情報の取得に失敗しました。</p>';
                break;
            }

            unset($_SESSION['oauth_token']);
            unset($_SESSION['oauth_token_secret']);

            $query =  'SELECT COUNT(*) FROM '. sql_table('plug_twiitem'). " WHERE member_id='".$memberid."'";
            $res = sql_query($query);
            list($result) = mysql_fetch_row($res);
            switch($result) {
                case 1:
                    $query =  'UPDATE '.sql_table('plug_twiitem');
                    $query .= " SET access_token='".$access_token['oauth_token']."', access_token_secret='".$access_token['oauth_token_secret']."', screen_name='".$access_token['screen_name']."' WHERE member_id='".$memberid."'";
                    break;
                default:
                    $query =  'INSERT INTO '.sql_table('plug_twiitem'). ' (member_id, access_token, access_token_secret, screen_name) ';
                    $query .= "VALUES ('".$memberid."', '".$access_token['oauth_token']. "', '".$access_token['oauth_token_secret']. "', '".$access_token['screen_name']."')";
                    break;
            }
            $res = sql_query($query);
            if($res){
                $message .= '<p>あなたのTwitterアカウント '.$access_token['screen_name'].' を このプラグインに登録しました。</p>';
            } else {
                $message .= '<p>Twitterアカウント情報の保存に失敗しました。</p>';
            }
            break;

        default:
            $query =  'SELECT screen_name FROM '. sql_table('plug_twiitem'). " WHERE member_id='".$memberid."'";
            $res = sql_query($query);
            $result = mysql_fetch_assoc($res);
            if($result) {
                $screenname = $result['screen_name'];
                $message .= '<p>すでにこのプラグインヘ登録済みのTwitterアカウントが見つかりました。</p><p>[アカウント名: '. $screenname.']</p>';
                $mes = '再';
            } else {
                $message .= '<p>現在 Twitterアカウントが登録されていません。</p>';
                $mes = '新規';
            }

            $url = $oPluginAdmin->plugin->getAdminURL().'?action=callback';
            $callbackUri = $manager->addTicketToUrl($url);

            $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
            $request_token = $connection->getRequestToken($callbackUri);

            $_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
            $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
            $authUrl = $connection->getAuthorizeURL($token);

            $message .= '<p><a style="border-top: 1px solid #ccc; border-right: 1px solid #999; border-bottom: 1px solid #999; border-left: 1px solid #ccc; padding: 5px 20px; font-weight: bold; color: #666; text-decoration:none;" href="'. $authUrl .'">登録ボタン</a>をクリックすると あなたのTwitterアカウントを このプラグインに'.$mes.'登録します。</p>';
            break;
    }
    $oPluginAdmin->start();
    echo '<h2>Twiitem</h2>';
    echo $message;
    echo '<p><a href="index.php?action=pluginlist">&larr; プラグイン一覧に戻る</a></p>';
    $oPluginAdmin->end();
?>