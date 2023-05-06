<?php

require_once 'config/connect.php';      /** @var $con */
require_once 'config/settings.php';

session_start();

$session_nev	= $_SESSION['sna'];
$session_tk8id 	= $_SESSION['st8'];
$session_szerv 	= $_SESSION['sze'];

if($_REQUEST['myresult'])
{
    $lesson_id = $_REQUEST['myresult'];
    $show = '';

    // Hányszor futott neki
    // $sql = "SELECT MAX(try) try FROM " . TABLE_HISTORY . " WHERE user_tk8id = '$session_tk8id' AND lesson_id = '$lesson_id';";
    $sql = "SELECT * FROM " . TABLE_USERS . " WHERE tk8id = '$session_tk8id' AND lesson_id = '$lesson_id';";
    $result = mysqli_query($con,$sql);
    $row = mysqli_fetch_assoc($result);
    $try_num = $row['try_num'];

    for($i = $try_num;$i>=1;$i--)
    {
        $show .= '
        <div class="mb-3 mt-3 row">
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAns'.$i.'" aria-expanded="false" aria-controls="collapseExample">'.$i.'. próbálkozás</button>
        </div>';

        $show .= '<div class="collapse" id="collapseAns'.$i.'">';

            $sql = "SELECT th.lesson_question_id,th.lesson_question,q.option1, q.option2, q.option3, q.option4, q.answer, th.users_ans
                    FROM ".TABLE_HISTORY." as th, ".TABLE_QUESTIONS." as q
                    WHERE th.lesson_question_id = q.id AND th.lesson_id = '$lesson_id' AND th.user_tk8id = '$session_tk8id' AND th.try = '$i';";

            $result = mysqli_query($con, $sql);
            $count = 1;
            while (($row = mysqli_fetch_assoc($result)) !== null)
            {
                $show .= '<p><b>' . $count . '. ' . $row['lesson_question'] . '</b></p>';
                $show .= '<ul class="list-group mb-5 mt-3">';
                for($j = 1;$j<=4;$j++)
                {
                    if($row['option' . $j])
                    {
                        $guess = in_array($j,explode(',', $row['users_ans'])) ? '<span class="badge bg-primary rounded-pill">Te válaszod</span>' : '';
                        if(in_array($j,explode(',', $row['answer'])))
                        {
                            $show .= '<li class="list-group-item list-group-item-success d-flex justify-content-between align-items-center" aria-current="true">' . $row['option' . $j] . $guess . '</li>';
                        }
                        else
                        {
                            $show .= '<li class="list-group-item d-flex justify-content-between align-items-center">' . $row['option' . $j] . $guess . '</li>';
                        }
                    }
                }
                $show .= '</ul>';
                $count++;
            }

        $show .= '</div>';
    }

    echo $show;
}

?>