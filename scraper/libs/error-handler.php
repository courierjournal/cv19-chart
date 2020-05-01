<?php

//Defer all errors and exceptions to our custom error handler
set_error_handler("error_handler");
set_exception_handler("error_handler");

//$email = "jhazel@gannett.com";
$email = "xxx@yyy.com";

/**
 * Custom error handler
 */
function alert($errmsg, $errno = null, $filename = null, $linenum = null)
{
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-Type: application/json');

    $msg = json_encode(["timestamp" => time(), "errornum" => $errno, "msg" => $errmsg, "file" => $filename, "linenum" => $linenum]);
    $mail_headers = "MIME-Version: 1.0" . "\r\n";
    $mail_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $mail_headers .= 'From: <jhazel@gannett.com>' . "\r\n";
    mail($email, "CV19 Scraper failed", $msg, $mail_headers);

    die($msg);
}


/** 
 * Used to intercept PHP's error and exception override and re-order the args that are sent to alert()
 */
function error_handler($errno = null, $errmsg = "", $filename = null, $linenum = null)
{
    alert($errmsg, $errno, $filename, $linenum);
}
