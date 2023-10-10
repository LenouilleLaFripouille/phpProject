<?php
//--------------------------------------Création des variables générales--------------------------------------


$file = file_get_contents("config.json");
$config = json_decode($file, true);

$urlGitLab = $config[0]["urlGitLab"];
$idProject = $config[0]["idProject"];
$nameTagBegConfig = $config[0]["nameTagBeg"];
$nameTagEndConfig = $config[0]["nameTagEnd"];

$searchTag = "📄";

$commits = [];


//--------------------------------------Process si release complet == TRUE--------------------------------------

    
    if ($config[0]["writeMethodAll"]){
    
//--------------------------------------Création et envoie du EndPoint <COMMITS>--------------------------------------

        $url = $urlGitLab."/api/v4/projects/".$idProject."/repository/commits/";

        $query = "?ref_name=main";
        
        $getProject = curl_init();
        
        curl_setopt($getProject, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($getProject, CURLOPT_URL, $url.$query);

        $result = curl_exec($getProject);

        curl_close($getProject);

        $commitsFromApi = json_decode($result, true);

        //--------------------------------------Process principal--------------------------------------


        // Filtre les commits qui correspondent aux critères retournés par la condition
        $commitsToKeep = array_filter($commitsFromApi, function($commit) use ($searchTag)
        {
                return strpos($commit["message"], $searchTag) !== false; 
        } 
        ); 

        // Boucle qui récupère tous les messages 
        $matches = [[]];
        foreach($commitsToKeep as $commit)
            {
                $str = $commit["message"];
                $regex = "/$searchTag(.*?)$searchTag/s";
 
                // Trouve les chaines qui correspondent au modèle
                preg_match_all($regex, $str, $matches);

                // Boucle qui ajoute les résultats dans une liste
                foreach ($matches[1] as $match) {
                    if (in_array($match, $commits) == false){
                        array_push($commits, "<br> - ".$match);
                    
                    }
                }
            }

        // Conversion de la liste en string et écriture dans le fichier "release.txt"
        $messageCommits = implode("\n",$commits);
        $messageCommits = str_replace($searchTag, "", $messageCommits);
        $messageText = "<!doctype html>
        <html lang='fr'>
        <body>
            <div style='background-color: #FF7A33; width: 33%; height: auto;'><u><h3>ScanGitLab Public License Version 3.0</h3></u></div>
            <table>
                <tbody>
                    <tr>
                    <td><div style='background-color: #E8E8E8; width: 100%; height: auto;'>-<b> Release du projet n°".$idProject."</b><br><br>".$messageCommits."</div></td>
                    <td><img src='logoGitLab.png'></td>
                </tr>
                </tbody>
            </table>
        
        </body>
        </html>";
        file_put_contents("release-général.html",$messageText."\n\n\n");
        
    }

    //--------------------------------------Process si release complet == FALSE--------------------------------------

    else {

    //--------------------------------------Création et envoie des EndPoint <TAGS>--------------------------------------


    $urlBeg = "http://localhost:80/api/v4/projects/".$idProject."/repository/tags/".urlencode($nameTagBegConfig);

    $urlEnd = "http://localhost:80/api/v4/projects/".$idProject."/repository/tags/".urlencode($nameTagEndConfig);


    // Requête curl pour obtenir date du tag de début

    $getTagBeg = curl_init();

    curl_setopt($getTagBeg, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($getTagBeg, CURLOPT_URL, $urlBeg);

    $resultBeg = curl_exec($getTagBeg);
    
    curl_close($getTagBeg);
    // echo '<pre>';
    // echo var_dump(urlencode($nameTagBegConfig));
    // echo '</pre>';
    
    $tagBegFromApi = json_decode($resultBeg, true);
    $tagBegDate = urlencode($tagBegFromApi["commit"]["committed_date"]); 

    
    // Requête curl pour obtenir date du tag de fin

    $getTagEnd = curl_init();

    curl_setopt($getTagEnd, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($getTagEnd, CURLOPT_URL, $urlEnd);

    $resultEnd = curl_exec($getTagEnd);

    curl_close($getTagEnd);

    $tagEndFromApi = json_decode($resultEnd, true);

    $tagEndDate = urlencode($tagEndFromApi["commit"]["committed_date"]);

//--------------------------------------Création et envoie du EndPoint <COMMITS>--------------------------------------

    
    $url = $urlGitLab."/api/v4/projects/".$idProject."/repository/commits";

    $query = "?until=".$tagEndDate."&since=".$tagBegDate;

    $getProject = curl_init();

    curl_setopt($getProject, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($getProject, CURLOPT_URL, $url.$query);

    $result = curl_exec($getProject);

    curl_close($getProject);

    $commitsFromApi = json_decode($result, true);

    //--------------------------------------Process principal--------------------------------------


    // Filtre les commits qui correspondent aux critères retournés par la condition
    $commitsToKeep = array_filter($commitsFromApi, function($commit) use ($searchTag)
    {
            return strpos($commit["message"], $searchTag) !== false; 
    } 
    ); 

    // Boucle qui récupère tous les messages et qui renvoie seulement le message à partir du $searchTag
    $matches = [[]];
    foreach($commitsToKeep as $commit)
        {
            $str = $commit["message"];
            $regex = "/$searchTag(.*?)$searchTag/s";

            // Trouve les chaines qui correspondent au modèle
            preg_match_all($regex, $str, $matches);

            // Boucle qui ajoute les résultats dans une liste
            foreach ($matches[1] as $match) {
                if (in_array($match, $commits) == false){
                    array_push($commits, "<br> - ".$match);
                
                }
            }
        }

    // Conversion de la liste en string et écriture dans le fichier "release.txt"
    $messageCommits = implode("\n",$commits);
    $messageCommits = str_replace($searchTag, "", $messageCommits);
    $messageText = "<!doctype html>
    <html lang='fr'>
    <body>
        <div style='background-color: #FF7A33; width: 33%; height: auto;'><u><h3>ScanGitLab Public License Version 3.0</h3></u></div>
        <table>
            <tbody>
                <tr>
                <td><div style='background-color: #E8E8E8; width: 100%; height: auto;'>-<b> Note de version de ".$nameTagBegConfig." à ".$nameTagEndConfig."</b><br><br>".$messageCommits."</div></td>
                <td><img src='logoGitLab.png'></td>
            </tr>
            </tbody>
        </table>
    
    </body>
    </html>";

        if ($config[0]["releaseMethodReset"]){

            file_put_contents("release.html",$messageText."\n\n\n");
        }

        else{

            file_put_contents("release.html",$messageText."\n\n\n",FILE_APPEND);
        }
    }