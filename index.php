<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = '41.77.117.162';
$username = 'ingroupe_matin';
$password = 'kr){?{pWGlvZ';

try
{
    $conn = new PDO("mysql:host=$servername;dbname=ingroupe_lematin", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}
catch(PDOException $e)
{
    echo "Erreur : " . $e->getMessage();
}


// select videos non traite
$sth = $conn->prepare("SELECT videos.id, videos.youtubeid FROM videos join video_rubrique_relation on video_rubrique_relation.video_id=videos.id LEFT JOIN article_texttospeech_videos as text ON text.video_id = videos.id Where text.id Is NULL and video_rubrique_relation.rubrique_id=11 order by videos.id desc limit 1");
$sth->execute();
$resultat = $sth->fetch(PDO::FETCH_ASSOC);
// check if audio is downloaded
              
if (!file_exists('videos/' . $resultat['youtubeid'] . '.webm')&& !file_exists('videos/' . $resultat['youtubeid'] . '.m4a'))
{
    echo $resultat['youtubeid'];
    $url = 'http://www.youtube.com/watch?v=' . $resultat['youtubeid'];
    $template = 'videos/%(id)s.%(ext)s';
    $string = ('/usr/local/bin/youtube-dl ' . escapeshellarg($url) . ' --extract-audio --audio-format mp3  -o ' . escapeshellarg($template));

    $descriptorspec = array(
        0 => array(
            "pipe",
            "r"
        ) , // stdin
        1 => array(
            "file",
            'out.log',
            'a'
        ) , // stdout
        2 => array(
            'file',
            'err.log',
            'a'
        ) ,
    );
    $process = proc_open($string, $descriptorspec, $pipes);
}
else
{
    // check if file transfered to static
    $remoteFile = 'https://static.lematin.ma/files/lematin/texttospeechvideos/' . date("Y") . '/' . $resultat['youtubeid'] . '.mp3';

    // Initialize cURL
    $ch = curl_init($remoteFile);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    var_dump($responseCode);
    if ($responseCode == 200)
    {
        // insert into article_texttospeech_videos
        $file = 'videos/' . $resultat['youtubeid'] . '.mp3';
        $time =  exec("/usr/local/bin/ffmpeg -i ".$file." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");

        $duration = explode(":",$time);
        $duration_in_seconds = $duration[0]*3600 + $duration[1]*60+ $duration[2];
        $milliseconds = $duration_in_seconds * 1000;
        $path = '/files/lematin/texttospeechvideos/' . date("Y") . '/' . $resultat['youtubeid'] . '.mp3';
        $date = date("Y-m-d H:i:s");
        //find video taille

        $stmt = $conn->prepare("INSERT INTO article_texttospeech_videos(id, path, traite, date_crea, video_id, lien_spotify, lien_deezer, taille) VALUES (null,:path,0,:date,:video_id, null,null,:taille)");
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':video_id', $resultat['id']);
        $stmt->bindParam(':taille', $milliseconds);
        $stmt->execute();
        echo file_get_contents('https://in.groupelematin.com/backend/web/text-to-speech/info-en-face');

    }
    else
    {
        // check if file converted to mp3
        if (!file_exists('videos/' . $resultat['youtubeid'] . '.mp3'))
        {
            //convert to mp3
              $string = ('/usr/local/bin/ffmpeg -i videos/' . $resultat['youtubeid'] . '.webm -f mp2  videos/' . $resultat['youtubeid'] . '.mp3' );

    $descriptorspec = array(
        0 => array(
            "pipe",
            "r"
        ) , // stdin
        1 => array(
            "file",
            'out.log',
            'a'
        ) , // stdout
        2 => array(
            'file',
            'err.log',
            'a'
        ) ,
    );
    $process = proc_open($string, $descriptorspec, $pipes);

              $string2 = ('/usr/local/bin/ffmpeg -i videos/' . $resultat['youtubeid'] . '.m4a -f mp2  videos/' . $resultat['youtubeid'] . '.mp3' );
    $descriptorspec2 = array(
        0 => array(
            "pipe",
            "r"
        ) , // stdin
        1 => array(
            "file",
            'out.log',
            'a'
        ) , // stdout
        2 => array(
            'file',
            'err.log',
            'a'
        ) ,
    );
    $process2 = proc_open($string2, $descriptorspec2, $pipes2);
            
        }
        else
        {
            // copie to static
            $filename = 'videos/' . $resultat['youtubeid'] . '.mp3';
            ini_set ('memory_limit', filesize ($filename) + 4000000);
          $resultData =file_get_contents($filename);
          file_put_contents('ftp://lmtstatic:YTrJdqvd&ZUJ@41.77.117.154/public_html/files/lematin/texttospeechvideos/' . date("Y") . '/' . $resultat['youtubeid'] . '.mp3', $resultData);


echo file_get_contents('https://in.groupelematin.com/backend/web/text-to-speech/info-en-face');
        
        }
    }

}

