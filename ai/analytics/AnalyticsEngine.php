<?php

/**
 * EvalSMART Analytics Engine
 *
 * Centralized analytics functions used by:
 * - Event Results
 * - PDF Reports
 * - AI Report Generation
 */

require_once __DIR__ . '/../../includes/analytics_helper.php';

function buildAnalyticsSummary($conn, $event_id)
{

    $scale_id = getEventScaleId($conn, $event_id);

    return [

        'overall' => [
            'mean' => getOverallAverage($conn, $event_id),
            'interpretation' => getInterpretation(
                $conn,
                $scale_id,
                getOverallAverage($conn, $event_id)
            ),
            'responses' => getTotalResponses($conn, $event_id)
        ],

        'categories' => getCategoryAverage($conn, $event_id),

        'questions' => getQuestionAverage($conn, $event_id)

    ];

}