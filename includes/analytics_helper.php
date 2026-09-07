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





function getQualitativeDataset($conn, $event_id)
{
    $dataset = [
        'open_ended' => [],
        'comments' => [],
        'suggestions' => []
    ];

    /*
    |--------------------------------------------------------------------------
    | Open-Ended Responses
    |--------------------------------------------------------------------------
    */
    $query = mysqli_query($conn, "
        SELECT
            qq.question_text,
            ea.answer_text

        FROM evaluation_answers ea

        INNER JOIN questionnaire_questions qq
            ON ea.question_id = qq.question_id

        WHERE ea.event_id = '$event_id'
        AND qq.question_type = 'text'
        AND TRIM(ea.answer_text) <> ''
    ");

    while ($row = mysqli_fetch_assoc($query))
    {
        $dataset['open_ended'][] = [
            'question' => $row['question_text'],
            'answer'   => $row['answer_text']
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Comments and Suggestions
    |--------------------------------------------------------------------------
    */
    $query = mysqli_query($conn, "
        SELECT comments, suggestions
        FROM evaluation_answers
        WHERE event_id = '$event_id'
    ");

    while ($row = mysqli_fetch_assoc($query))
    {

        $commentsTrimmed = trim($row["comments"] ?? ''); // trim deprecated, not accepting null values v8.1 below
        $suggestionsTrimmed = trim($row["suggestions"] ?? '');

        if (!empty($commentsTrimmed))
        {
            $dataset['comments'][] = $commentsTrimmed;
        }

        if (!empty(trim($suggestionsTrimmed)))
        {
            $dataset['suggestions'][] = $suggestionsTrimmed;
        }
    }

    return $dataset;
}

// Total Responses
function getTotalResponses($conn, $event_id)
{
    $query = mysqli_query($conn, "
        SELECT COUNT(DISTINCT user_id) AS total
        FROM evaluation_answers
        WHERE event_id = '$event_id'
    ");

    $row = mysqli_fetch_assoc($query);

    return $row['total'] ?? 0;
}



// sample size for comments and suggestions
function getQualitativeSampleSize($totalResponses)
{
    if ($totalResponses <= 20) {
        return $totalResponses;
    }

    if ($totalResponses <= 50) {
        return 20;
    }

    if ($totalResponses <= 100) {
        return 30;
    }

    if ($totalResponses <= 300) {
        return 50;
    }

    if ($totalResponses <= 500) {
        return 75;
    }

    if ($totalResponses <= 1000) {
        return 100;
    }

    return 150;
}

// frequency distribution
function getFrequencyDistribution(
    $conn,
    $event_id
)
{
    $results = [];

    $query = mysqli_query($conn, "
        SELECT
            qq.question_id,
            qq.question_text,
            CAST(ea.answer_text AS DECIMAL(10,2)) AS rating

        FROM evaluation_answers ea

        INNER JOIN questionnaire_questions qq
            ON ea.question_id = qq.question_id

        WHERE ea.event_id = '$event_id'
        AND qq.question_type = 'rating'
    ");

    while($row = mysqli_fetch_assoc($query))
    {
        $qid = $row['question_id'];

        if(!isset($results[$qid]))
        {
            $results[$qid] = [
                'question_text' => $row['question_text'],
                'frequency' => []
            ];
        }

        $rating = (int)$row['rating'];

        if(!isset($results[$qid]['frequency'][$rating]))
        {
            $results[$qid]['frequency'][$rating] = 0;
        }

        $results[$qid]['frequency'][$rating]++;
    }

    return $results;
}

//Percentage 
function getPercentageDistribution($frequencyResults)
{
    $results = [];

    foreach ($frequencyResults as $questionId => $data)
    {
        $totalResponses = array_sum(
            $data['frequency']
        );

        $percentages = [];

        foreach ($data['frequency'] as $scale => $count)
        {
            $percentages[$scale] =
                $totalResponses > 0
                    ? round(
                        ($count / $totalResponses) * 100,
                        2
                    )
                    : 0;
        }

        $results[$questionId] = [
            'question_text' => $data['question_text'],
            'percentage' => $percentages
        ];
    }

    return $results;
}

//scale interpretation for frequency and percentage
function getScaleLabels($conn, $scale_id)
{
    $labels = [];

    $query = mysqli_query($conn,"
        SELECT score, label
        FROM evaluation_scale_options
        WHERE scale_id='$scale_id'
        ORDER BY score DESC
    ");

    while($row = mysqli_fetch_assoc($query))
    {
        $labels[$row['score']] =
            $row['label'];
    }

    return $labels;
}


function getEventScaleId($conn, $event_id)
{
    $query = mysqli_query($conn, "
        SELECT q.scale_id
        FROM event_questionnaire eq
        INNER JOIN questionnaire q
            ON eq.questionnaire_id = q.questionnaire_id
        WHERE eq.event_id = '$event_id'
        LIMIT 1
    ");

    $row = mysqli_fetch_assoc($query);

    return $row['scale_id'] ?? null;
}


//Calculate Questions SD
function getQuestionStandardDeviation($conn, $event_id)
{
    $results = [];

    $query = mysqli_query($conn, "
        SELECT
            qq.question_id,
            qq.question_text,
            CAST(ea.answer_text AS DECIMAL(10,2)) AS rating

        FROM evaluation_answers ea

        INNER JOIN questionnaire_questions qq
            ON ea.question_id = qq.question_id

        WHERE ea.event_id = '$event_id'
        AND qq.question_type = 'rating'
    ");

    while($row = mysqli_fetch_assoc($query))
    {
        $qid = $row['question_id'];

        if(!isset($results[$qid]))
        {
            $results[$qid] = [
                'question_text' => $row['question_text'],
                'ratings' => []
            ];
        }

        $results[$qid]['ratings'][] = (float)$row['rating'];
    }

    $output = [];

    foreach($results as $qid => $data)
    {
        $ratings = $data['ratings'];

        $count = count($ratings);

        if($count <= 1)
        {
            $sd = 0;
        }
        else
        {
            $mean = array_sum($ratings) / $count;

            $variance = 0;

            foreach($ratings as $rating)
            {
                $variance += pow(
                    $rating - $mean,
                    2
                ); // calculation of Standard Deviation
            }

            $variance /= $count; // count of respondents

            $sd = sqrt($variance); // convert to Square root
        }

        $output[$qid] = round($sd, 2); 
    }

    return $output;
}


// Calculate Category SD
function getCategoryStandardDeviation($conn, $event_id)
{
    $results = [];

    $query = mysqli_query($conn, "
        SELECT
            COALESCE(qc.category_name, 'General Evaluation') AS category_name,
            CAST(ea.answer_text AS DECIMAL(10,2)) AS rating

        FROM evaluation_answers ea

        INNER JOIN questionnaire_questions qq
            ON ea.question_id = qq.question_id

        LEFT JOIN questionnaire_categories qc
            ON qq.category_id = qc.category_id

        WHERE ea.event_id = '$event_id'
        AND qq.question_type = 'rating'
    ");

    while($row = mysqli_fetch_assoc($query))
    {
        $category = $row['category_name'];

        if(!isset($results[$category]))
        {
            $results[$category] = [];
        }

        $results[$category][] = (float)$row['rating'];
    }

    $output = [];

    foreach($results as $category => $ratings)
    {
        $count = count($ratings);

        if($count <= 1)
        {
            $sd = 0;
        }
        else
        {
            $mean = array_sum($ratings) / $count;

            $variance = 0;

            foreach($ratings as $rating)
            {
                $variance += pow(
                    $rating - $mean,
                    2
                );
            }

            // Sample Standard Deviation
            $variance /= ($count - 1);

            $sd = sqrt($variance);
        }

        $output[$category] = round($sd, 2);
    }

    return $output;
}













// function getInterpretationBadge($text)
// {
//     $badge = "secondary";

//     if (
//         stripos($text, "excellent") !== false ||
//         stripos($text, "very satisfied") !== false ||
//         stripos($text, "strongly agree") !== false
//     ) {
//         $badge = "success";
//     }
//     elseif (
//         stripos($text, "satisfied") !== false ||
//         stripos($text, "agree") !== false
//     ) {
//         $badge = "primary";
//     }
//     elseif (
//         stripos($text, "neutral") !== false
//     ) {
//         $badge = "warning";
//     }
//     elseif (
//         stripos($text, "dissatisfied") !== false ||
//         stripos($text, "poor") !== false
//     ) {
//         $badge = "danger";
//     }

//     return "<span class='badge bg-{$badge}'>".$text."</span>";
// }