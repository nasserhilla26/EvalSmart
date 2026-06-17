<?php

function getQuestionAverage($conn, $event_id)
{
    $result = [];

    $query = mysqli_query($conn, "
        SELECT
            qq.question_id,
            qq.question_text,
            AVG(CAST(ea.answer_text AS DECIMAL(10,2))) AS avg_score
        FROM evaluation_answers ea
        JOIN questionnaire_questions qq
            ON ea.question_id = qq.question_id
        WHERE ea.event_id = '$event_id'
            AND qq.question_type = 'rating'
        GROUP BY qq.question_id
    ");

    while ($row = mysqli_fetch_assoc($query)) {

        $result[] = [
            'question_id' => $row['question_id'],
            'question_text' => $row['question_text'],
            'average' => round($row['avg_score'], 2)
        ];
    }

    return $result;
}


function getCategoryAverage($conn, $event_id)
{
    $result = [];

    $query = mysqli_query($conn, "
        SELECT
            COALESCE(qc.category_name,'General Evaluation') AS category_name,

            AVG(
                CAST(ea.answer_text AS DECIMAL(10,2))
            ) AS avg_score

        FROM evaluation_answers ea

        JOIN questionnaire_questions qq
            ON ea.question_id = qq.question_id

        LEFT JOIN questionnaire_categories qc
            ON qq.category_id = qc.category_id

        WHERE ea.event_id = '$event_id'
            AND qq.question_type = 'rating'

        GROUP BY qc.category_id
    ");

    while ($row = mysqli_fetch_assoc($query)) {

        $result[] = [
            'category_name' => $row['category_name'],
            'average' => round($row['avg_score'], 2)
        ];
    }

    return $result;
}


function getOverallAverage($conn, $event_id)
{
    $query = mysqli_query($conn, "
        SELECT
            AVG(
                CAST(answer_text AS DECIMAL(10,2))
            ) AS overall_avg
        FROM evaluation_answers ea

        JOIN questionnaire_questions qq
            ON ea.question_id = qq.question_id

        WHERE ea.event_id = '$event_id'
            AND qq.question_type = 'rating'
    ");

    $row = mysqli_fetch_assoc($query);

    return round($row['overall_avg'] ?? 0.00, 2);
}


function getInterpretation(
    $conn,
    $scale_id,
    $average
) {
    $query = mysqli_query($conn, "
        SELECT interpretation
        FROM interpretation_ranges
        WHERE scale_id = '$scale_id'
            AND $average
            BETWEEN min_value
            AND max_value
        LIMIT 1
    ");

    if (mysqli_num_rows($query) > 0) {

        $row = mysqli_fetch_assoc($query);

        return $row['interpretation'];
    }

    return 'No Interpretation';
}


function getInterpretationBadge($text)
{
    $badge = "secondary";

    if (
        stripos($text, "excellent") !== false ||
        stripos($text, "very satisfied") !== false ||
        stripos($text, "strongly agree") !== false
    ) {
        $badge = "success";
    }
    elseif (
        stripos($text, "satisfied") !== false ||
        stripos($text, "agree") !== false
    ) {
        $badge = "primary";
    }
    elseif (
        stripos($text, "neutral") !== false
    ) {
        $badge = "warning";
    }
    elseif (
        stripos($text, "dissatisfied") !== false ||
        stripos($text, "poor") !== false
    ) {
        $badge = "danger";
    }

    return "<span class='badge bg-{$badge}'>".$text."</span>";
}