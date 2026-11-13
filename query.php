<?php
if(isset($_POST["submit"]) && $_POST["submit"]=="query") 
{
    $recipient = "shivangi.gupta1404@gmail.com";
    $subject = "Query from bookstore";
    $sender = isset($_POST["sender"]) ? strip_tags($_POST["sender"]) : 'Customer';
    $senderEmail = isset($_POST["senderEmail"]) ? filter_var($_POST["senderEmail"], FILTER_SANITIZE_EMAIL) : '';
    $message = isset($_POST["message"]) ? strip_tags($_POST["message"]) : '';
    $mailBody = "Name: $sender\nEmail: $senderEmail\n\n$message";
    
    // Suppress mail error if sendmail / SMTP not configured on container
    @mail($recipient, $subject, $mailBody, "From: $sender <$senderEmail>");

    $resSub = "Confirmation of receiving your query";
    $resBody = "Dear ". $sender ."\n\nThanks for reaching us.\nThis is to inform you that we have received your query. We will get back to you asap.";
    $note = "\n\nNote : This is an auto-generated mail do not reply to this.\n";
    $resBody = $resBody . $note;
    if (!empty($senderEmail)) {
        @mail($senderEmail, $resSub, $resBody);
    }
    header("Location: index.php?response=" . urlencode("Your Message has been successfully sent! Our customer executive will respond shortly.")); 
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>