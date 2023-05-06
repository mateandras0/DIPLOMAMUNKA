<?php

session_start();

$session_nev	= $_SESSION['sna'];
$session_tk8id 	= $_SESSION['st8'];

require_once "config/connect.php";           /** @var mysqli $con */
require_once "config/functions.php";         //saját eljárások
require_once "config/settings.php";          /** @var array $otherTestUpload */



if(!in_array($session_tk8id,$otherTestUpload))
{
    header('location:index.php');
}


if(isset($_POST['upload']))
{
    $lesson_name = mysqli_real_escape_string($con, strip_tags(trim(filter_input(INPUT_POST, 'lesson_name'))));

    // Hibakezelés
    if (mb_strlen($lesson_name, "utf-8") < 2)
    {
        $errors['lesson_name'] = '<p class="text-center" style="color:red">A névnek minimum 2 karakternek kell lennie!</p>';
    }


    if(empty($errors))
    {
        $target_dir = DIR_FOLDER . "pics/other/";
        $lesson_doc = basename($_FILES["lesson_doc"]["name"]);

        if ($lesson_doc)
        {
            $lesson_doc = formatFileName($lesson_doc);
            $lesson_doc = mt_rand()."_".$lesson_doc;
            $target_file = $target_dir . $lesson_doc;
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if (file_exists($target_file))
            {
                $uploadOk = 0;
            }

            if ($_FILES["lesson_doc"]["size"] > 100*1000*1000)     //100000 KB --> 100 MB
            {
                $uploadOk = 0;
            }

            if ($imageFileType != "pdf" && $imageFileType != "mp4" && $imageFileType != "ppt" && $imageFileType != "pptx")
            {
                $uploadOk = 0;
            }

            if ($uploadOk == 1)
            {
                if (move_uploaded_file($_FILES["lesson_doc"]["tmp_name"], $target_file))
                {
                    $lesson_doc_up = DIR_LINK . "pics/other/" . $lesson_doc;

                    $qry = "INSERT INTO " . TABLE_OTHER_LESSONS . " (`name`, `link`) VALUES ('$lesson_name', '$lesson_doc_up');";
                    mysqli_query($con, $qry) or die(mysqli_error($con));
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
    <title>Oktatási anyag feltöltése</title>
</head>
<body>
<div class="container mt-5 mb-5">
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="lesson_name" class="form-label">Oktatási anyag neve<sup>*</sup></label>
                    <input class="form-control" id="lesson_name" name="lesson_name">
                    <?php echo getError('lesson_name');?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="lesson_doc" class="form-label">Dokumentum (pdf,mp4,pptx)<sup>*</sup></label>
                    <input type="file" class="form-control" id="lesson_doc" name="lesson_doc" accept=".pdf, .mp4, .ppt, .pptx">
                </div>
            </div>
        </div>
        <button class="btn btn-primary" name="upload">Feltölt</button>
    </form>
</div>
</body>
</html>
