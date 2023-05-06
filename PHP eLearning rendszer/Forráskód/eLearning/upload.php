<?php

session_start();

$session_nev	= $_SESSION['sna'];
$session_tk8id 	= $_SESSION['st8'];

require_once "config/connect.php";           /** @var $con mysqli */
require_once "config/functions.php";         //saját eljárások
require_once "config/settings.php";          //rendszer beállítások


if(!in_array($session_tk8id,$testUpload))
{
    header('location:index.php');
}


/**
 * Kérdések száma: Legyen páros szám!
 */

$questionNumber = 50;

if(isset($_POST['upload']))
{
    $lesson_name = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'lesson_name'))));
    $group_name = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'group_name'))));
    $lesson_password = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'lesson_password'))));
    $towhom = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'towhom'))));
    $precondition = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'precondition'))));
    $min_points = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'min_points',FILTER_VALIDATE_INT))));
    $question_number = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'question_number',FILTER_VALIDATE_INT))));

    // Hibakezelés
    if (mb_strlen($lesson_name, "utf-8") < 2)
    {
        $errors['lesson_name'] = '<p class="text-center" style="color:red">A névnek minimum 2 karakternek kell lennie!</p>';
    }

    if (mb_strlen($group_name, "utf-8") < 1)
    {
        $errors['group_name'] = '<p class="text-center" style="color:red">Oktatási csoportot kötelező beírni vagy választani!</p>';
    }

    if($min_points > $question_number)
    {
        $errors['min_points'] = '<p class="text-center" style="color:red">A minimálisan elérendő pontok száma nem lehet több, mint a kérdések száma!</p>';
    }

    if($question_number<1)
    {
        $errors['question_number'] = '<p class="text-center" style="color:red">A kérdések száma legalább 1 legyen!</p>';
    }


    if(empty($errors))
    {
        $qry = "INSERT INTO " . TABLE_LESSONS . " (`name`, `group_name`, `password`, `towhom` , `precondition`, `min_points`, `question_number`)
                            VALUES ('$lesson_name', '$group_name', '$lesson_password', '$towhom', '$precondition', '$min_points', '$question_number');";

        mysqli_query($con, $qry) or die(mysqli_error($con));

        if (mysqli_affected_rows($con) > 0)
        {
            $sql = "SELECT MAX(id) m FROM " . TABLE_LESSONS;
            $result = mysqli_query($con, $sql);
            $row = mysqli_fetch_assoc($result);
            $max_id = $row['m'];

            /**
             * Oktatási doksi feltöltése
             */

            mkdir(DIR_FOLDER . "pics/" . $max_id . "/", 0777);
            chmod(DIR_FOLDER . "pics/" . $max_id . "/", 0777);

            $target_dir = DIR_FOLDER . "pics/" . $max_id . "/";
            $lesson_doc = basename($_FILES["lesson_doc"]["name"]);
            $lesson_doc_up = '';

            if ($lesson_doc)
            {
                $lesson_doc = formatFileName($lesson_doc);
                $lesson_doc = "okt_anyag_" . $max_id . "_" . mt_rand() . "_" . $lesson_doc;
                $target_file = $target_dir . $lesson_doc;
                $uploadOk = 1;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Check if file already exists
                if (file_exists($target_file))
                {
                    $uploadOk = 0;
                }

                // Check file size
                if ($_FILES["lesson_doc"]["size"] > 100000000)     //100000 KB --> 100 MB
                {
                    $uploadOk = 0;
                }

                if ($imageFileType != "pdf" && $imageFileType != "mp4" && $imageFileType != "ppt" && $imageFileType != "pptx")
                {
                    $uploadOk = 0;
                }

                // Check if $uploadOk is set to 0 by an error
                if ($uploadOk == 1)
                {
                    if (move_uploaded_file($_FILES["lesson_doc"]["tmp_name"], $target_file))
                    {
                        // echo "The file ". htmlspecialchars( basename( $_FILES["lesson_doc"]["name"])). " has been uploaded.";
                        $lesson_doc_up = DIR_LINK . "pics/" . $max_id . "/" . $lesson_doc;
                        $qry = "UPDATE " . TABLE_LESSONS . " SET document = '$lesson_doc_up' WHERE id = '$max_id'";
                        mysqli_query($con, $qry) or die(mysqli_error($con));
                    }
                }

            }


            $file_dir = false;
            for ($j = 1; $j <= $questionNumber; $j++)
            {
                $question[$j] = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'question' . $j))));

                /**
                 * Kérdésekhez képfeltöltés!
                 */

                $target_dir = DIR_FOLDER . "pics/" . $max_id . "/";
                $q_pic[$j] = basename($_FILES["q_pic" . $j]["name"]);
                $q_pic_up = '';

                if ($q_pic[$j] !== '')
                {

                    $q_pic[$j] = formatFileName($q_pic[$j]);

                    $q_pic[$j] = mt_rand() . "_" . $q_pic[$j];

                    $target_file = $target_dir . $q_pic[$j];
                    $uploadOk = 1;
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                    // Check if file already exists
                    if (file_exists($target_file))
                    {
                        $uploadOk = 0;
                    }

                    // Check file size
                    if ($_FILES["q_pic" . $j]["size"] > 10000000)     //10000 KB --> 10 MB
                    {
                        $uploadOk = 0;
                    }

                    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                        && $imageFileType != "gif")
                    {
                        $uploadOk = 0;
                    }

                    // Check if $uploadOk is set to 0 by an error
                    if ($uploadOk == 1)
                    {
                        if (move_uploaded_file($_FILES["q_pic" . $j]["tmp_name"], $target_file))
                        {
                            // echo "The file ". htmlspecialchars( basename( $_FILES["q_pic".$j]["name"])). " has been uploaded.";
                            $q_pic_up = DIR_LINK . "pics/" . $max_id . "/" . $q_pic[$j];
                        }
                    }

                }


                if ($question[$j])
                {
                    for ($i = 1; $i <= 4; $i++)
                    {
                        $options[$j][$i] .= mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'option' . $j . $i))));
                        if (isset($_POST['qc' . $j . $i]))
                        {
                            $answer[$j] .= filter_input(INPUT_POST, 'qc' . $j . $i) . ',';
                        }
                    }

                    $answer[$j] = substr_replace($answer[$j], "", -1);

                    $type = strlen($answer[$j]) > 1 ? 'checkbox' : 'radio';
                    $opt1 = $options[$j][1];
                    $opt2 = $options[$j][2];
                    $opt3 = $options[$j][3];
                    $opt4 = $options[$j][4];

                    if ($answer[$j])
                    {
                        $qry = "INSERT INTO " . TABLE_QUESTIONS . " (`lesson_id`, `description`, `pic`, `type`, `option1`, `option2`, `option3`, `option4`, `answer`)
                    VALUES ('$max_id', '$question[$j]', '$q_pic_up', '$type', '$opt1', '$opt2', '$opt3', '$opt4', '$answer[$j]');";
                        mysqli_query($con, $qry) or die(mysqli_error($con));
                    }
                }
            }
        }

        mysqli_close($con);
        header('location: index.php');
        exit();

    }

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
    <script src="js/functions.js"></script>
    <link href="css/style.css" rel="stylesheet">
    <title>Kérdések feltöltése</title>
</head>
<body>
<div class="container mt-5 mb-5">
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="lesson_name" class="form-label">Oktatási anyag neve<sup>*</sup></label>
                    <input class="form-control" list="datalistOptions" onchange="getData(this.value)" id="lesson_name" name="lesson_name" required>
                    <datalist id="datalistOptions">
                        <?php
                        $out = '';
                        $sql = "SELECT name FROM ".TABLE_LESSONS;
                        $result = mysqli_query($con, $sql) or die(mysqli_error($con));
                        while (($row = mysqli_fetch_assoc($result)) !== null)
                        {
                            $out .= '<option value="'.$row['name'].'"></option>';
                        }
                        echo $out;
                        ?>
                    </datalist>
                    <?php echo getError('lesson_name');?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="lesson_password" class="form-label">Jelszó</label>
                    <input class="form-control" id="lesson_password" name="lesson_password" value="">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="lesson_doc" class="form-label">Oktatási dokumentum (pdf,mp4,ppt)<sup>*</sup></label>
                    <input type="file" class="form-control" id="lesson_doc" name="lesson_doc" accept=".pdf, .mp4, .ppt, .pptx" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="group_name" class="form-label">Oktatási csoport<sup>*</sup></label>
                    <input class="form-control" list="datalistGpName" id="group_name" name="group_name" required>
                    <datalist id="datalistGpName">
                        <?php
                        $out = '';
                        $sql = "SELECT DISTINCT(group_name) FROM ".TABLE_LESSONS;
                        $result = mysqli_query($con, $sql) or die(mysqli_error($con));
                        while (($row = mysqli_fetch_assoc($result)) !== null)
                        {
                            $out .= '<option value="'.$row['group_name'].'"></option>';
                        }
                        echo $out;
                        ?>
                    </datalist>
                    <?php echo getError('group_name');?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="towhom" class="form-label">Kiknek?<sup>*</sup></label>
                    <select class="form-select" name="towhom" id="towhom">
                        <?php
                        foreach (AREA as $ppl)
                        {
                            echo '<option>'.$ppl.'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="precondition" class="form-label">Előfeltétel?</label>
                    <select class="form-select" name="precondition" id="precondition">
                        <option value="">Nincs</option>
                        <?php
                        $out = '';
                        $sql = "SELECT id,name FROM ".TABLE_LESSONS;
                        $result = mysqli_query($con, $sql) or die(mysqli_error($con));
                        while (($row = mysqli_fetch_assoc($result)) !== null)
                        {
                            $out .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
                        }
                        echo $out;
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="question_number" class="form-label">Teszten a kérdések száma<sup>*</sup></label>
                    <input type="number" min="0" class="form-control" id="question_number" name="question_number" value="10" required>
                    <?php echo getError('question_number');?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="min_points" class="form-label">Minimum pont<sup>*</sup></label>
                    <input type="number" min="0" onchange="setMax()" class="form-control" id="min_points" name="min_points" value="8" required>
                    <?php echo getError('min_points');?>
                </div>
            </div>
        </div>
        <hr>

        <?php

        echo quest($questionNumber);

        ?>

        <button class="btn btn-primary" name="upload">Feltölt</button>
    </form>
</div>
</body>
</html>
