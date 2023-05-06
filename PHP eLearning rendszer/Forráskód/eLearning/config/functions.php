<?php

require_once "connect.php";
/**
 * @return string
 * @var $questionNumber
 */

function quest($questionNumber)
{
    $out = '';
    $q_num = 0;

    for ($x = 1; $x <= ($questionNumber / 2); $x++)
    {
        $out .= '
            <div class="row mb-3">';

        for ($i = 1; $i <= 2; $i++)
        {
            $q_num++;
            $out .= '
                    <div class="col-sm-6">
                        <div class="mb-3">
                            <label for="question' . $q_num . '" class="form-label"><b>' . $q_num . '. kérdés</b></label>
                                <textarea class="form-control" id="question' . $q_num . '" name="question' . $q_num . '" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="q_pic" class="form-label">Kép a kérdéshez</label>
                            <input type="file" class="form-control" id="q_pic' . $q_num . '" name="q_pic' . $q_num . '" accept="image/png, image/jpeg">
                        </div>';

            for ($j = 1; $j <= 4; $j++)
            {
                $out .= '
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="option' . $q_num . $j . '" name="qc' . $q_num . $j . '" value="' . $j . '">
                                <label class="form-check-label" for="option' . $q_num . $j . '">
                                    <input type="text" class="form-control" id="" name="option' . $q_num . $j . '">
                                </label>
                            </div>';
            }

            $out .= '
                    </div>';
        }

        $out .= '
            </div>';
    }
    return $out;
}

function test_form($id)
{

    global $con;

    $sql = "SELECT `question_number` qn FROM ".TABLE_LESSONS." WHERE id = '$id';";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    $qn = mysqli_fetch_assoc($result);
    $questionNumber = $qn['qn'];

    $sql = "SELECT `id`,`description`,`pic`,`type`,`option1`,`option2`,`option3`,`option4`,`answer` FROM ".TABLE_QUESTIONS." WHERE lesson_id = '$id' ORDER BY RAND() LIMIT $questionNumber";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    $_SESSION['q_down'] = '';
    while (($row = mysqli_fetch_assoc($result)) !== null)
    {
        $_SESSION['q_down'] = $_SESSION['q_down'] . $row['id'] . ',';
        $q_id[]     = $row['id'];
        $des[]      = $row['description'];
        $pic[]      = $row['pic'];
        $type[]     = $row['type'];
        for($c = 1;$c<=4;$c++)
        {
            $option[$c][] = $row['option'.$c];
        }
        $answer[]   = $row['answer'];
    }

    $_SESSION['q_down'] = substr($_SESSION['q_down'], 0, -1);

    $out = '';
    $q_num = 0;

    for ($x = 1; $x <= ceil($questionNumber / 2); $x++)
    {
        $out .= '
            <div class="row mb-3">';

        for ($i = 1; $i <= 2; $i++)
        {
            $q_num++;
            $out .= '
                    <div class="col-sm-6">';
            if($des[$q_num - 1])
            {
                $out .= '
                        <div class="mb-3">
                            <label for="question' . $q_num . '" class="form-label"><b>' . $q_num . '. kérdés</b></label>
                                <div class="questionBox">';
                                if ($pic[$q_num - 1])
                                {

                                    /*
                                    $out .= '
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <img class="mx-auto d-block" src="' . $pic[$q_num - 1] . '" alt="" >
                                        </div>
                                        <div class="col-sm-6">
                                            <p  id="question' . $q_num . '">' . $des[$q_num - 1] . '</p>
                                        </div>
                                    </div>';
                                    */

                                    $out .= '
                                        <img class="mx-auto d-block" src="' . $pic[$q_num - 1] . '" alt="" >
                                        <p class="mt-3" id="question' . $q_num . '">' . $des[$q_num - 1] . '</p>';
                                }
                                else
                                {
                                    $out .= '<p id="question' . $q_num . '">' . $des[$q_num - 1] . '</p>';
                                }
                                $out .= '
                                </div>
                        </div>';
            }

            for ($j = 1; $j <= 4; $j++)
            {
                if($type[$q_num-1] == 'checkbox' && $option[$j][$q_num - 1])
                {
                    // name="qc' . $q_num . $j . '"
                    $out .= '
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="option' . $q_num . $j . '" name="qc' . $q_id[$q_num-1] .'_'. $j . '" value="' . $j . '">
                                <label class="form-check-label" for="option' . $q_num . $j . '">'
                        . $option[$j][$q_num - 1] .
                        '</label>
                            </div>';
                }
                else if($type[$q_num-1] == 'radio' && $option[$j][$q_num - 1])
                {
                    // name="qc' . $q_num . '"
                    $out .= '
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="option' . $q_num . $j . '" name="qc' . $q_id[$q_num-1] . '" value="' . $j . '">
                                <label class="form-check-label" for="option' . $q_num . $j . '">'
                        . $option[$j][$q_num - 1] .
                        '</label>
                            </div>';
                }
            }

            $out .= '
                    </div>';
        }

        $out .= '
            </div>';
    }
    return $out;
}

function formatFileName($str)
{
    $bad  = ['á','ä','é','í','ó','ö','ő','ú','ü','ű','Á','Ä','É','Í','Ó','Ö','Ő','Ú','Ü','Ű',' '];
    $good = ['a','a','e','i','o','o','o','u','u','u','a','a','e','i','o','o','o','u','u','u','-'];
    $str = mb_strtolower($str,"utf-8");//kisbetűsre
    $str = str_replace($bad, $good, $str);//karakterek cseréje
    $str = preg_replace("/[^A-Za-z0-9_.]/", "-", $str);//maradék eltávolítása
    $str = rtrim($str,'-');//végződő '-' eltávolítása
    return $str;
}

function array_to_csv_download($array, $filename = "export.csv", $delimiter = ";")
{
    header('Content-Type: application/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'";');

    ob_end_clean();

    $handle = fopen('php://output', 'w');

    // Write utf-8 bom to the file
    fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

    foreach ($array as $line)
    {
        fputcsv($handle, $line, $delimiter);
    }

    fclose($handle);

    ob_flush();

    exit();
}

function getError($fieldName)
{
    global $errors; //  Az eljárás idejére globálissá tesszük az errors tömböt

    if(isset($errors[$fieldName]))
    {
        return $errors[$fieldName];
    }

    return false;
}

function getLeaders() {
    global $con;
    $array = [];
    $sql = "SELECT tk8id FROM " . TABLE_RIGHTS . " WHERE elearning_vezeto = 1;";
    $result = mysqli_query($con, $sql) or die(mysqli_error($con));
    while (($row = mysqli_fetch_assoc($result)) !== null) {
        $array[] = $row['tk8id'];
    }
    return $array;
}
