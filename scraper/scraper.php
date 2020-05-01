<?php

require('libs/error-handler.php');

$state_list = json_decode(file_get_contents('state_list.json'), true);
$raw_data = json_decode(file_get_contents('https://covidtracking.com/api/v1/states/daily.json'), true);

$output = [
    "timestamp" => time() * 1000,
    "headers" => ["fips", "population", "datesTested", "tested", "datesPositive", "positive", "datesDeath", "death", "datesPositiveTested", "positiveTested", "datesDeathTested", "deathTested"],
    "rawData" => []
];

foreach ($state_list as $state) {

    //Setup the output object
    $state_output = [];

    //Extract totals
    $date_range = [];
    $tested = [];
    $positive = [];
    $death = [];
    $postive_per_test = [];
    $death_per_test = [];

    foreach ($raw_data as $data) {
        if ($data['state'] == $state['abbreviation']) {
            $tested[] = $data['totalTestResultsIncrease'];
            $positive[] = $data['positiveIncrease'];
            $death[] = $data['deathIncrease'];
            $positive_per_test[] = ($data['positiveIncrease'] != 0) ? floatval(number_format($data['totalTestResultsIncrease'] / $data['positiveIncrease'], 2, ".", "")) : 0;
            $death_per_test[] = ($data['deathIncrease'] != 0) ? floatval(number_format($data['totalTestResultsIncrease'] / $data['deathIncrease'], 2, ".", "")) : 0;
            $date_range[] = $data['date'];
        }
    }

    //Since the api presents dates in decending order, reverse the arrays
    $date_range = array_reverse($date_range);
    $tested = array_reverse($tested);
    $positive = array_reverse($positive);
    $death = array_reverse($death);

    //Determine the date range for tested
    foreach ($tested as $index => $test_datum) {
        if ($test_datum > 0) {
            $tested_dates = [$date_range[$index], end($date_range)];
            array_splice($tested, 0, $index);
            break;
        }
    }

    //Determine the date range for positive
    foreach ($positive as $index => $test_datum) {
        if ($test_datum > 0) {
            $positive_dates = [$date_range[$index], end($date_range)];
            array_splice($positive, 0, $index);
            break;
        }
    }

    //Determine the date range for deaths
    foreach ($death as $index => $test_datum) {
        if ($test_datum > 0) {
            $death_dates = [$date_range[$index], end($date_range)];
            array_splice($death, 0, $index);
            break;
        }
    }

    //Determine the date range for positive
    foreach ($positive_per_test as $index => $test_datum) {
        if ($test_datum > 0) {
            $positive_per_test_dates = [$date_range[$index], end($date_range)];
            array_splice($positive_per_test, 0, $index);
            break;
        }
    }

    //Determine the date range for deaths
    foreach ($death_per_test as $index => $test_datum) {
        if ($test_datum > 0) {
            $death_per_test_dates = [$date_range[$index], end($date_range)];
            array_splice($death_per_test, 0, $index);
            break;
        }
    }

    //Populate the output object
    //"fips", "population", "datesTested", "tested", "datesPositive", "positive", "datesDeath", "death"
    $state_output[0] = $state['fips'];
    $state_output[1] = $state['population'];
    $state_output[2] = [$tested_dates[0], $tested_dates[1]];
    $state_output[3] = $tested;
    $state_output[4] = [$positive_dates[0], $positive_dates[1]];
    $state_output[5] = $positive;
    $state_output[6] = [$death_dates[0], $death_dates[1]];
    $state_output[7] = $death;

    //Last minute addition: Add positive per test and death per test numbers
    //"datesPositiveTested", "positiveTested", "datesDeathTested", "deathTested"
    /*
    $state_output[8] = [$positive_dates[0], $positive_dates[1]];
    $state_output[9] = $positive_per_test;
    $state_output[10] = [$death_dates[0], $death_dates[1]];
    $state_output[11] = $death_per_test;
*/

    //Add the results to our output object
    $output['rawData'][] = $state_output;
}

//Write the output file
file_put_contents('data.json', json_encode($output));
