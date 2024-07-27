<?php

try {
    // Função para ordenar o json
    function array_sort($array, $on, $order=SORT_ASC){
        $new_array = array();
        $sortable_array = array();
        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }
            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }
            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }
        return $new_array;
    }
    
    // data source (for demo purposes)
    $str = file_get_contents('students.json');
    $json = json_decode($str, true);
    $json_length = sizeof($json);
    $json_updated = array();
    
    if (isset($_COOKIE['deletedStudent'])) {
        for($i=0;$i<$json_length;$i++){
            if($json[$i]['StudentId'] == $_COOKIE['deletedStudent']) {
                unset($json[$i]);
            } else {
                $json_updated[] = $json[$i];
            }
        }
    } else if (isset($_COOKIE['addedStudent'])) {
        $json_updated = $json;
        $addedStudent = unserialize($_COOKIE['addedStudent']);
        $json_updated[] = $addedStudent;
    } else {
        $json_updated = $json;
    }
    $json_length_updated = sizeof($json_updated);

    //Getting records (listAction)
    if ($_GET["action"] == "list") {
        $start       = $_GET["jtStartIndex"];
        $pageSize    = $_GET["jtPageSize"];
        $page_length = ($start+$pageSize < $json_length_updated) ? $start+$pageSize : $json_length_updated;
        $rows = array();

        // Order By filtro
        if($_GET['jtSorting']){
            $arr      = explode(' ', $_GET['jtSorting']);
            $orderby  = $arr[0];
            $desc_asc = ($arr[1] == 'ASC') ? SORT_ASC : SORT_DESC;
            $novo    =  array_sort( $json_updated, $orderby, $desc_asc );
            $tamanho = sizeof($novo) - $start;
            
            if( $desc_asc == SORT_ASC ){
                for($i=$start;$i<$page_length;$i++){
                    $rows[] =  $novo[$i];
                }
            } else {
                $menor = $tamanho - $pageSize;
                
                for($i=$tamanho; $i>$menor;$i--){
                    if( empty($novo[$i])){
                        continue;
                    }
                    $rows[] =  $novo[$i];
                }
            }
            
        // Lista padrão
        } else {
            for($i=$start;$i<$page_length;$i++){
                $rows[] = $json_updated[$i];
            }
        }      
        $jTableResult['Result'] = "OK";
        $jTableResult['TotalRecordCount'] = $json_length_updated;
        $jTableResult['Records'] = $rows;
        print json_encode($jTableResult);
    }
    //Creating a new record (createAction)
    else if ($_GET["action"] == "create") {

        $date = date('Y-m-d H:i:s');
        $recordDate = strtotime($date) * 1000;
        if($_POST["BirthDate"] != '') {
            $birthDate = strtotime($_POST["BirthDate"]) * 1000;
        }

        $newUser = array(
            'StudentId' => rand(1,10000),
            'CityId' => $_POST["CityId"],
            'Name' => $_POST["Name"],
            'EmailAddress' => $_POST["EmailAddress"],
            'Password' => $_POST["Password"],
            'Gender' => $_POST["Gender"],
            'BirthDate' => ($_POST["BirthDate"] != '') ? '/Date('. $birthDate .')/' : '',
            'About' => $_POST["About"],
            'Education' => isset($_POST["Education"]) ? $_POST["Education"] : '',
            'IsActive' => isset($_POST["IsActive"]) ? 'true' : 'false',
            'RecordDate' => '/Date('. $recordDate .')/'
        );

        setcookie("addedStudent", serialize($newUser), time()+2);

        //Return result to jTable
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        $jTableResult['Record'] = $newUser;
        print json_encode($jTableResult);
    }
    //Updating a record (updateAction)
    else if ($_GET["action"] == "update") {

        for($i=0;$i<$json_length;$i++){
            if($json[$i]['StudentId'] == $_POST["StudentId"]) {
                $json[$i]['CityId'] = $_POST["CityId"];
                $json[$i]['Name'] = $_POST["Name"];
                $json[$i]['EmailAddress'] = $_POST["EmailAddress"];
                $json[$i]['Password'] = $_POST["Password"];
                $json[$i]['Gender'] = $_POST["Gender"];
                $json[$i]['BirthDate'] = '/Date('. $_POST["BirthDate"] .')/';
                $json[$i]['About'] = $_POST["About"];
                $json[$i]['Education'] = $_POST["Education"];
                $json[$i]['IsActive'] = isset($_POST["IsActive"]) ? 'true' : 'false';
                $json[$i]['RecordDate'] = '/Date('. strtotime(date('d-m-Y H:i:s')) * 1000 .')/';
            }
        }

        //Return result to jTable
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        print json_encode($jTableResult);
    }
    //Deleting a record (deleteAction)
    else if ($_GET["action"] == "delete") {

        for($i=0;$i<$json_length;$i++){
            if($json[$i]['StudentId'] == $_POST["StudentId"]) {
                setcookie("deletedStudent", $json[$i]['StudentId'], time()+2);
                unset($json[$i]);
            }
        }

        //Return result to jTable
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        print json_encode($jTableResult);
    }

} catch (Exception $ex) {
    //Return error message
    $jTableResult = array();
    $jTableResult['Result'] = "ERROR";
    $jTableResult['Message'] = $ex->getMessage();
    print json_encode($jTableResult);
}

?>