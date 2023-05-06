<?php

require_once "config/connect.php";           /** @var $con mysqli */
require_once "config/functions.php";         //saját eljárások
require_once "config/settings.php";          //rendszer beállítások

session_start();

$session_nev	= $_SESSION['sna'];
$session_tk8id 	= $_SESSION['st8'];
$session_szerv 	= $_SESSION['sze'];

if($session_tk8id == '')
{
    exit('Nincs TK8ID-d');
}

$leaders = getLeaders();

if(isset($_POST['fillTest']))
{
    $test_id    = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'id'))));
    $pass       = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'pass'))));

    $sql = "SELECT password p FROM ".TABLE_LESSONS." WHERE id = '$test_id';";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    $passCheck = mysqli_fetch_assoc($result);

    if($passCheck['p'] === $pass)
    {
        $_SESSION['pass'] = true;
        header('Location: http://10.116.125.38/SAPINFO_2019/e-learning/test.php?id='.$test_id);
    }
    else
    {
        echo '
        <div class="container mt-3">
            <div class="alert alert-danger">
                <strong>Hibás jelszó!</strong> Kérlek nézd meg az oktatási anyagot, abban fogod találni a helyes jelszót!
            </div>
        </div>';
    }
}

if(isset($_GET['excelCurrState']))
{
    $excelHead = ['TK8ID','Név','Oktatás neve','Kitöltés dátuma','Eredmény','Próbálkozások száma','Sikerült?'];
    $toExcel [] = $excelHead;

    $sql = "SELECT u.tk8id, u.name as uname, l.name, u.fill_date, u.result, u.try_num, u.finished
            FROM `". TABLE_USERS ."` as u, `". TABLE_LESSONS ."` as l
            WHERE l.id = u.`lesson_id` ORDER BY u.name ASC, u.lesson_id ASC";

    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    while (($row = mysqli_fetch_assoc($result)) !== null)
    {
        //$toExcel [] = $row;   /** Ez elég lenne a while-ban, ha nem akarnánk módosítani valamelyik értéken */
        $ered ['tk8id']     = $row['tk8id'];
        $ered ['uname']     = $row['uname'];
        $ered ['name']      = $row['name'];
        $ered ['fill_date'] = $row['fill_date'];
        $ered ['result']    = " ".$row['result'];
        $ered ['try_num']   = $row['try_num'];
        $ered ['finished']  = $row['finished'] == 1 ? 'Igen' : 'Nem';
        $toExcel [] = $ered;
    }

    array_to_csv_download($toExcel,'aktualis_allapot.csv');
}

?>

<!doctype html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <link href="css/style.css" rel="stylesheet">
    <title>E-Learning</title>
    <script src="https://kit.fontawesome.com/075071db9b.js" crossorigin="anonymous"></script>
    <style>
        th
        {
            position: sticky;
            background-color:white;
            top: 0;
            text-align: center;
            opacity:0.9;
        }
        .my-custom-scrollbar
        {
            position: relative;
            height: 30em;
            overflow: auto;
        }
        .table-wrapper-scroll-y
        {
            display: block;
        }
        .pointer
        {
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <?php

    echo $_SESSION['elearning']['result'];

    $out = '';
    $num = 1;
    $sql = "SELECT DISTINCT(group_name) FROM ".TABLE_LESSONS." ORDER BY group_name ASC;";
    // $sql = "SELECT DISTINCT(group_name) FROM ".TABLE_LESSONS." ORDER BY CASE WHEN group_name = 'Videók' THEN 1 ELSE 2 END ASC;";

    $result_gp = mysqli_query($con, $sql) or die(mysqli_error($con));
    while (($row_gp = mysqli_fetch_assoc($result_gp)) !== null)
    {
        $gp_name = $num.'. '.$row_gp["group_name"];
        $out .= '
        <h2 class="pointer mb-5" data-bs-toggle="collapse" data-bs-target="#groups'.$num.'">'.$gp_name.' (Katt ide)</h2>
        <!--
        <div id="groups" class="table-wrapper-scroll-y my-custom-scrollbar collapse">
        -->
        <div id="groups'.$num.'" class="collapse">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="background-color:white;">Oktatás neve</th>
                    <th style="background-color:white;">Oktatóanyag</th>
                    <!--
                    <th>Kiknek</th>
                    -->
                    <th style="background-color:white;">Állapot</th>
                    <th style="background-color:white;">Teljesítés ideje</th>
                    <th style="background-color:white;">Próbálkozások száma</th>
                    <th style="background-color:white;">Eredmény</th>
                    <th style="background-color:white;">Teszt indítása</th>';
        $out .= '
                </tr>
            </thead>
            <tbody>';
        $sql = "SELECT l.id,l.name,l.document,l.password,l.towhom,l.precondition,u.fill_date,u.result,u.try_num,u.finished
                        FROM " . TABLE_LESSONS . " as l LEFT JOIN (SELECT * FROM " . TABLE_USERS . " WHERE users.tk8id = '$session_tk8id') as u ON l.id = u.lesson_id WHERE `group_name` = '".$row_gp['group_name']."' ORDER BY l.id ASC;";
        if($session_tk8id == '10543163')
        {
            // var_export($sql);
        }
        $result = mysqli_query($con, $sql) or die(mysqli_error($con));
        while (($row = mysqli_fetch_assoc($result)) !== null)
        {
            // Csak akkor jelenik meg az oktatási anyag és teszt, ha az oktatási rész, hogy kiknek szól, benne van a te szervezeti egységedben VAGY az, hogy "Mindenki"
            if ((strpos(strtolower($session_szerv), strtolower($row["towhom"])) !== false) || ($row["towhom"] === 'Mindenki'))
            {
                $id = $row["id"];
                $name = $row['name'];

                // Ha már kitöltötte, rákattinthat, hogy csekkolja a korábbi eredményeit
                $out .= $row["fill_date"] ? "<tr style='cursor:pointer' onclick='showMyResult($id,\"$name\")' data-bs-toggle='modal' data-bs-target='#answareModal' >" : "<tr>";
                $out .= '<td>' . $row["name"] . '</td>';
                $out .= '<td><a class="btn btn-warning" href="' . $row["document"] . '" target="_blank"><i class="fa-solid fa-book"></i> Link</a></td>';
                // $out .= '<td>' . $row["towhom"] . '</td>';
                if ($row["finished"] == 1)
                {
                    $out .= '<td class="td-success">Teljesítve</td>';
                } else if ($row["finished"] != 1 && $row["fill_date"])
                {
                    $out .= '<td class="td-danger">Sikertelen!</td>';
                } else
                {
                    $out .= '<td class="td-info">Kitöltésre vár!</td>';
                }
                $out .= '<td>' . $row["fill_date"] . '</td>';
                $out .= '<td>' . $row["try_num"] . '</td>';
                $out .= '<td>' . $row["result"] . '</td>';
                if ($row["finished"]){
                    $out .= '<td>A vizsga már elvégezve</td>';
                }else if ($session_tk8id === NULL){
                    $out .= '<td>Jelentkezz be újra!</td>';
                }else{
                    if($row['precondition']){
                        $sql = "SELECT
                                    l.name, u.finished
                                FROM
                                    ".TABLE_LESSONS." as l LEFT JOIN (SELECT * FROM ".TABLE_USERS." WHERE users.tk8id = '$session_tk8id') as u ON l.id = u.lesson_id
                                WHERE
                                    l.id = ".$row['precondition'];
                        $result_pre = mysqli_query($con, $sql) or die(mysqli_error($con));
                        $row_pre = mysqli_fetch_assoc($result_pre);
                        if($row_pre['finished']){
                            if ($row['password']){
                                $out .= "<td><button class='btn btn-primary' onclick='passID($id,\"$name\")' data-bs-toggle='modal' data-bs-target='#passModal'><i class='fa-solid fa-key'></i> Jelszó megadása</button></td>";
                            }else{
                                $_SESSION['pass'] = true;
                                $out .= "<td><a class='btn btn-primary' href='http://10.116.125.38/SAPINFO_2019/e-learning/test.php?id=$id'><i class='fa-solid fa-pencil'></i> Teszt kitöltése</a></td>";
                            }
                        }else{
                            $out .= "<td><p>Előkövetelmény: <br><b>".$row_pre['name']."</b></p></td>";
                        }
                    }else{
                        if ($row['password']){
                            $out .= "<td><button class='btn btn-primary' onclick='passID($id,\"$name\")' data-bs-toggle='modal' data-bs-target='#passModal'><i class='fa-solid fa-key'></i> Jelszó megadása</button></td>";
                        }else{
                            $_SESSION['pass'] = true;
                            $out .= "<td><a class='btn btn-primary' href='http://10.116.125.38/SAPINFO_2019/e-learning/test.php?id=$id'><i class='fa-solid fa-pencil'></i> Teszt kitöltése</a></td>";
                        }
                    }

                }
                $out .= '</tr>';
            }
        }
            $out .= '
            </tbody>
        </table>
        </div>';
        $num++;
    }

    $out .= '
    <h2 class="pointer mb-5" data-bs-toggle="collapse" data-bs-target="#others">Egyéb oktatási anyagok (Katt ide)</h2>
    
    <div id="others" class="collapse">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="background-color:white;">Oktatás neve</th>
                    <th style="background-color:white;">Oktatóanyag</th>
                </tr>
            </thead>
            <tbody>';

            $sql = "SELECT * FROM ".TABLE_OTHER_LESSONS." ORDER BY name ASC;";

            $result_other = mysqli_query($con, $sql) or die(mysqli_error($con));
            while (($row_other = mysqli_fetch_assoc($result_other)) !== null)
            {
                $out .= '
                <tr>
                    <td>'.$row_other['name'].'</td>
                    <td><a class="btn btn-warning" href="'.$row_other['link'].'" target="_blank"><i class="fa-solid fa-book"></i> Link</a></td>
                </tr>';
            }

    $out .='
            </tbody>
        </table>
    </div>';

    if(in_array($session_tk8id,$leaders)) {

        $out .= '
    <h2 class="pointer mb-5" data-bs-toggle="collapse" data-bs-target="#leader">Vezetői anyagok (Katt ide)</h2>
    
    <div id="leader" class="collapse">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="background-color:white;">Oktatás neve</th>
                    <th style="background-color:white;">Oktatóanyag</th>
                </tr>
            </thead>
            <tbody>';

        $sql = "SELECT * FROM " . TABLE_LEADER_LESSONS . " ORDER BY name ASC;";

        $result_other = mysqli_query($con, $sql) or die(mysqli_error($con));
        while (($row_other = mysqli_fetch_assoc($result_other)) !== null) {
            $out .= '
                <tr>
                    <td>' . $row_other['name'] . '</td>
                    <td><a class="btn btn-warning" href="' . $row_other['link'] . '" target="_blank"><i class="fa-solid fa-book"></i> Link</a></td>
                </tr>';
        }

        $out .= '
            </tbody>
        </table>
    </div>';
    }

    if(in_array($session_tk8id,$DownloadResultToExcel))
    {
        $out .= '
        <div class="mt-5">';

        $out .= '
        <a href="index.php?excelCurrState=1" class="btn btn-success"><i class="fa-solid fa-download"></i> Aktuális állapot letöltése</a>
        ';

        $out .= '
        </div>';
    }

    if(in_array($session_tk8id,$testUpload))
    {
        $out .= '
        <div class="mt-5">';

        $out .= '
        <a href="upload.php" class="btn btn-warning"><i class="fa-solid fa-upload"></i> Teszt feltöltése</a>
        ';

        $out .= '
        </div>';
    }

    if(in_array($session_tk8id,$otherTestUpload))
    {
        $out .= '
        <div class="mt-5">';

        $out .= '
        <a href="upload_other.php" class="btn btn-warning"><i class="fa-solid fa-upload"></i> Egyéb oktatóanyag feltöltése</a>
        ';

        $out .= '
        </div>';
    }

    echo $out;

    ?>
    <div class="mt-5">
    </div>
</div>

<!-- The Password Modal -->
<div class="modal fade" id="passModal">
    <div class="modal-dialog">
        <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title" id="testID"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <input type="text" class="form-control" id="id" name="id" hidden>
                <div class="mb-3">
                    <label for="pass" class="form-label">Kérlek add meg a teszt jelszavát, hogy ki tudd azt tölteni</label>
                    <input type="text" class="form-control" id="pass" name="pass">
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button class="btn btn-success" name="fillTest" id="fillTest">Mehet</button>
            </div>
        </form>
        </div>
    </div>
</div>



<!-- The Answare Modal -->
<div class="modal fade" id="answareModal" tabindex="-1" aria-labelledby="answareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="answareModalLabel">Oktatási anyag neve</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="answareModalBody">
                <!-- Ide jön az eredmény -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bezár</button>
            </div>
        </div>
    </div>
</div>



</body>
<script>

    function passID(id,name)
    {
        document.getElementById("testID").innerHTML = name;
        document.getElementById("id").value = id;
    }

    function showMyResult(lesson_id,lesson_name)
    {
        document.getElementById("answareModalLabel").innerHTML = lesson_name;

        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function()
        {
            if (this.readyState == 4 && this.status == 200)
            {
                document.getElementById("answareModalBody").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET", "elearning_down.php?myresult=" + lesson_id, true);
        xmlhttp.send();

    }

</script>

<?php

unset($_SESSION['elearning']['result']);

?>

</html>
