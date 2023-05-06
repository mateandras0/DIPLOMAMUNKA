<?php

session_start();

$session_nev	= $_SESSION['sna'];
$session_tk8id 	= $_SESSION['st8'];
$session_qnums  = $_SESSION['q_down'];

require_once "config/connect.php";           /** @var $con mysqli */
require_once "config/functions.php";         //saját eljárások
require_once "config/settings.php";          //rendszer beállítások

if(isset($_GET['id']))
{
    if($_SESSION['pass'])
    {
        $id = $_GET['id'];
        unset($_SESSION['pass']);
    }

}

$sql = "SELECT name FROM ".TABLE_LESSONS." WHERE `id` = '$id'";
$result = mysqli_query($con, $sql) or die(mysqli_error($con));
$less_name = mysqli_fetch_assoc($result);

if(isset($_POST['test_save']))
{
    $id = $_GET['id'];
    $answ = [];
    $checkAnsware = [];
    $sql_quest_nums = '';
    //$q1 = mysqli_real_escape_string($con,strip_tags(trim(filter_input(INPUT_POST, 'name'))));
    foreach ($_POST as $key => $value)
    {
        if($key != 'test_save')
        {
            // echo "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";

            if(strpos($key, '_'))
            {
                $key = substr($key, 0, -2);
            }
            $key = substr($key, 2);
            $answ[$key][] = $value;

            $sql_quest_nums .= "'" . $key . "'" . ',';
        }
    }

    $sql_quest_nums = substr($sql_quest_nums, 0, -1);

    $sql = "SELECT * FROM ".TABLE_QUESTIONS." WHERE `id` IN (" . $session_qnums . ") AND `lesson_id` = '$id'";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    while (($row = mysqli_fetch_assoc($result)) !== null)
    {
        $goodAnswer[$row['id']] = explode(',', $row['answer']);
        $checkDesc[$row['id']] = $row['description'];
        $q_id[$row['id']] = $row['id'];
    }


    $answeredQty = 0;
    $good = 0;
    $bad = 0;
    foreach ($goodAnswer as $k => $v)
    {
        $real = '';
        $yours = '';

        if($answ[$k] == $v)
        {
            $good++;
            $question = $checkDesc[$k];
            $question_id = $q_id[$k];
            $success = 1;
            foreach ($v as $vv)
            {
                $real = $real.$vv.',';
                $yours = $real;
            }
        }
        else
        {
            $bad++;
            $question = $checkDesc[$k];
            $question_id = $q_id[$k];
            $success = 0;
            foreach ($v as $vv)
            {
                $real = $real.$vv.',';
            }
            foreach ($answ[$k] as $vv)
            {
                $yours = $yours.$vv.',';
            }
        }

        $real = substr($real, 0, -1);
        $yours = substr($yours, 0, -1);
        $now = date("Y-m-d H:i:s");

        $sql = "SELECT try_num t FROM ".TABLE_USERS." WHERE tk8id = '$session_tk8id' AND lesson_id = '$id'";
        $result = mysqli_query($con, $sql) or die(mysqli_error($con));
        $try_num = mysqli_fetch_row($result);
        $try_num[0]++;

        $sql = "INSERT INTO ".TABLE_HISTORY." (`user_name`,`user_tk8id`,`lesson_id`,`lesson_question_id`,`lesson_question`,
                                            `correct_ans`,`users_ans`,`try`,`success`,`up_date`)
                                    VALUES ('$session_nev','$session_tk8id','$id','$question_id','$question',
                                            '$real','$yours','$try_num[0]','$success','$now');";

        mysqli_query($con, $sql) or die(mysqli_error($con));
        $answeredQty = $good + $bad;

    }

    $sql = "SELECT min_points m, question_number qn FROM ".TABLE_LESSONS." WHERE `id` = '$id'";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    $min_points = mysqli_fetch_assoc($result);

    $testResult     = $min_points['m'] <= $good ? 1 : 0;
    $questionNumber = $min_points['qn'];

    $ma = date("Y-m-d");

    $sql = "SELECT * FROM ".TABLE_USERS." WHERE `lesson_id` = '$id' AND `tk8id` = '$session_tk8id'";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    $user = mysqli_fetch_assoc($result);

    if($user)
    {
        $try = ++$user['try_num'];
        $sql = "UPDATE " . TABLE_USERS . " SET `fill_date` = '$ma',
                                `result` = '$good / $questionNumber',
                                `try_num` = '$try',
                                `finished` = '$testResult'
            WHERE `tk8id` = '$session_tk8id' AND `lesson_id` = '$id';";
    }
    else
    {
        $sql = "INSERT INTO " . TABLE_USERS . " (`tk8id`, `name`, `lesson_id`, `fill_date`, `result`, `try_num`, `finished`)
                                    VALUES ('$session_tk8id', '$session_nev', '$id', '$ma', '$good / $questionNumber', '1', '$testResult'); ";
    }


    if ($testResult)
    {
        $_SESSION['elearning']['result']  = '
            <div class="alert alert-success alert-dismissible fade show mt-5">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <strong>Sikeres vizsga!</strong> Az eredményed: <b>'.$good.' / '.$questionNumber.'</b><br>
                Válaszaidat megtekintheted a most kitöltött vizsga sorára kattintva
            </div>';
    }
    else
    {
        $_SESSION['elearning']['result'] = '
            <div class="alert alert-danger alert-dismissible fade show mt-5">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <strong>Sajnos nem sikerült a vizsga!</strong> Az eredményed: <b>'.$good.' / '.$questionNumber.'</b><br>
                Válaszaidat megtekintheted a most kitöltött vizsga sorára kattintva
            </div>';
    }

    mysqli_query($con, $sql) or die(mysqli_error($con));

    header('Location: index.php');
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
    <script src="https://kit.fontawesome.com/075071db9b.js" crossorigin="anonymous"></script>
    <title>Teszt kitöltése</title>
</head>
<body onKeyPress="return keyPressed(event)">
<div class="container" style="margin-bottom:200px">
    <?php

    echo '<h1 class="mt-5 mb-5" align="center"><u>' . $less_name['name'] . '</u></h1>';

    if($id)
    {
        echo '
        <form method="POST" enctype="multipart/form-data">';
            echo test_form($id);
        echo '
        <button class="btn btn-primary mt-5" name="test_save"><i class="fa-solid fa-paper-plane"></i> Beküld</button>
        </form>';
    }
    ?>
</div>
</body>
<script>
    function keyPressed(e)
    {
        var key;
        if(window.event)
        {
            key = window.event.keyCode; //IE
        }
        else
        {
            key = e.which; //firefox
        }
        return (key != 13);
    }
</script>
</html>

