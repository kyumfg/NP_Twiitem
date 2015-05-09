<?php/* NP_Twiitem v0.4 *  * 記事投稿時にTwitterへも投稿 * spikaonwork氏作「NP_TwitterLt0.3」(http://uemura.kir.jp/spispo.php)改造 *  * v0.1  2014.12.15 初版 kyu * v0.2  2015.02.07 プラグイン名変更。アイテムの追加/編集にツイートするしないのラジオボタン設置 kyu * v0.3  2015.02.09 プラグインオプション作成 kyu * v0.4  2015.02.18 プラグインオプション廃止。管理画面でTwitter認証の自動化 kyu */require_once dirname(__FILE__) . '/twiitem/twitteroauth.php';require_once dirname(__FILE__) . '/twiitem/key.php';class NP_Twiitem extends NucleusPlugin{    function getName()       {return 'Twiitem';}    function getAuthor()     {return 'kyu';}    function getURL()        {return 'mailto:kyumfg@gmail.com';}    function getVersion()    {return '0.4[2015.02.18]';}    function supportsFeature($w) {return in_array($w, array('SqlTablePrefix','SqlApi'));}    function getMinNucleusVersion(){return '350';}    function getEventList()  {return array('AddItemFormExtras','EditItemFormExtras','PostAddItem','PostUpdateItem');}    function hasAdminArea()  {return 1;}	function getTableList()  {return array(sql_table('plug_twiitem'));}    function getDescription(){        global $member;        $message = 'アイテムを追加/編集すると Twitterへも投稿します。';        $memberid = $member->getID();        $query =  'SELECT COUNT(*) FROM '. sql_table('plug_twiitem'). " WHERE member_id='".$memberid."'"; 		$res = sql_query($query);        list($result) = mysql_fetch_row($res);        if($result == 0){            $message .= '【Twititerアカウントが登録されていません。[管理] から登録してください】';        }else{            $message .= '【現在 このプラグインは有効です】';        }        return $message;}    function install() {		$query =  'CREATE TABLE IF NOT EXISTS '. sql_table('plug_twiitem'). '(';		$query .= ' member_id int(11) NOT NULL,';        $query .= ' access_token varchar(255) not null,';        $query .= ' access_token_secret varchar(255) not null,';        $query .= ' screen_name varchar(255) not null,';		$query .= ' PRIMARY KEY  (member_id)';		$query .= ') ENGINE=MyISAM;';		sql_query($query);    }    function uninstall() {		$query =  'DROP TABLE IF EXISTS '. sql_table('plug_twiitem'). ';';        sql_query($query);    }    function event_AddItemFormExtras() {        global $member;        $memberid = $member->getID();        $query =  'SELECT COUNT(*) FROM '. sql_table('plug_twiitem'). " WHERE member_id='".$memberid."'"; 		$res = sql_query($query);        list($result) = mysql_fetch_row($res);        if($result != 0){            $host = $_SERVER["HTTP_HOST"];            if(strstr($host, 'localhost')){                ?>                <h3>Twiitem</h3>                <input type="radio" name="twiitem" id="on" value="on" /><label for="on">この記事をツイートする</label><br />                <input type="radio" name="twiitem" id="off" value="off" checked="checked" /><label for="off">この記事をツイートしない</label>                <?php            } else {                ?>                <h3>Twiitem</h3>                <input type="radio" name="twiitem" id="on" value="on" checked="checked" /><label for="on">この記事をツイートする</label><br />                <input type="radio" name="twiitem" id="off" value="off" /><label for="off">この記事をツイートしない</label>                <?php            }        }    }    function event_EditItemFormExtras() {        global $member;        $memberid = $member->getID();        $query =  'SELECT COUNT(*) FROM '. sql_table('plug_twiitem'). " WHERE member_id='".$memberid."'"; 		$res = sql_query($query);        list($result) = mysql_fetch_row($res);        if($result != 0){            ?>            <h3>Twiitem</h3>            <input type="radio" name="twiitem" id="on" value="on" /><label for="on">この記事をツイートする</label><br />            <input type="radio" name="twiitem" id="off" value="off" checked="checked" /><label for="off">この記事をツイートしない</label>            <?php        }    }    function event_PostAddItem($data) {        $twiitem = requestVar('twiitem');        if ($twiitem =="on"){            $this->PostItemToTwitter($data['itemid']);        } else {            return;        }    }    function event_PostUpdateItem($data) {        $twiitem = requestVar('twiitem');        if ($twiitem =="on"){            $this->PostItemToTwitter($data['itemid']);        } else {            return;        }    }    function PostItemToTwitter($itemid){        global $CONF, $member, $manager;        $query =  'SELECT ititle FROM '. sql_table('item'). " WHERE inumber='".$itemid."'";		$res = sql_query($query);        $result = mysql_fetch_assoc($res);        $itemtitle = $result['ititle'];        $blogid = getBlogIDFromItemID($itemid);        $blogname = getBlogNameFromID($blogid);//        $url = createItemLink($itemid);        if ($blogid == $CONF['DefaultBlog']) {            $baseurl = $CONF['IndexURL'];        } else {            $b =& $manager->getBlog($blog_id);            $baseurl = $b->getURL();        }        $url = $baseurl.'?itemid='.$itemid;        $message = $itemtitle.' : '.$blogname.'  '.$url;        $memberid = $member->getID();        $query =  'SELECT * FROM '. sql_table('plug_twiitem'). " WHERE member_id='".$memberid."'";		$res = sql_query($query);        $result = mysql_fetch_assoc($res);        $ck = CONSUMER_KEY;        $cs = CONSUMER_SECRET;        $at = $result['access_token'];        $ats = $result['access_token_secret'];        $cert = array('ck' => $ck, 'cs' => $cs, 'at' => $at, 'ats'=> $ats);        foreach ($cert as $_){            if (empty($_)){                return;            }        }        $posturl = "https://api.twitter.com/1.1/statuses/update.json";        $method = "POST";        $connection = new TwitterOAuth($ck,$cs,$at,$ats);        $res = $connection->OAuthRequest($posturl,$method,array("status"=>"$message"));    }}?>