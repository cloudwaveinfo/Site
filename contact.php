<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 1. Check if PHPMailer files exist (Prevent 500 error)
$base_dir = __DIR__ . '/src/';
$required_files = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];
foreach ($required_files as $file) {
    if (!file_exists($base_dir . $file)) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Library file '$file' missing in ./src/ folder."]);
        exit;
    }
}

require $base_dir . 'Exception.php';
require $base_dir . 'PHPMailer.php';
require $base_dir . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. Helper Functions (From User Snippet)
function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        return $_SERVER['HTTP_CLIENT_IP'];
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    else
        return $_SERVER['REMOTE_ADDR'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
        exit;
    }

    // Sanitize Inputs
    $name = strip_tags(trim($data["name"] ?? "New Enquiry"));
    $email = filter_var(trim($data["email"] ?? ""), FILTER_SANITIZE_EMAIL);
    $phone = strip_tags(trim($data["phone"] ?? $data["mobile"] ?? "N/A"));
    $company = strip_tags(trim($data["company"] ?? "N/A"));
    $service = strip_tags(trim($data["service"] ?? "General Inquiry"));
    $message = strip_tags(trim($data["message"] ?? "Check leads."));

    $ip_address = getUserIP();
    $device_info = $_SERVER['HTTP_USER_AGENT'];

    // 3. SMTP Configuration (From User Snippet)
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.secureserver.net';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@cloudwaveinfo.com';
        $mail->Password = 'Cloudwave@123$'; // Set this if required by host
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // --- A. COMPANY LEAD NOTIFICATION ---
        $mail->setFrom('info@cloudwaveinfo.com', 'Cloudwave Website');
        $mail->addAddress('kashankumar12@gmail.com'); // Main recipient
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "New Website Lead: $service - $name";
        $mail->Body = "
            <div style='font-family: sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #060606; font-size: 24px; font-weight: 800; letter-spacing: -1px;'>New Website Lead Notification</h2>
                <p style='color: #666; font-size: 14px;'>A new lead has been captured from the website contact form.</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <table style='width: 100%; font-size: 15px; color: #333;'>
                    <tr><td style='width: 140px; padding: 8px 0;'><strong>Full Name:</strong></td><td>$name</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Phone Number:</strong></td><td>$phone</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Email Address:</strong></td><td>$email</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Company Name:</strong></td><td>$company</td></tr>
                    <tr><td style='padding: 8px 0;'><strong>Service Interest:</strong></td><td><span style='color: #007bff; font-weight: 700;'>$service</span></td></tr>
                </table>
                <div style='background: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 20px;'>
                    <strong>Message:</strong><br><br>
                    " . nl2br($message) . "
                </div>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 11px; color: #aaa;'>
                    <strong>Lead Metadata:</strong><br>
                    IP: $ip_address<br>
                    User-Agent: $device_info<br>
                    Source: Cloudwave Project (CWF)
                </p>
            </div>";

        $mail->send();

        // --- B. USER THANK YOU EMAIL ---
        if ($email) {
            $mail->clearAddresses();
            $mail->addAddress($email, $name);
            $mail->Subject = "Thank You for Contacting Cloudwave IT Solutions";
            $mail->Body = '
                <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #ddd; border-radius: 12px;">
                    <h2 style="color: #111;">Hi ' . $name . ',</h2>
                    <p>Thank you for reaching out to <strong>Cloudwave</strong>. We have received your inquiry for <strong>' . $service . '</strong>.</p>
                    <p>Our team will review your request and contact you within 24 business hours.</p>
                    <br>
                    <hr style="border: 0; border-top: 1px solid #eee;">
                    <p style="color: #666; font-size: 14px;">This is an automated confirmation of your request. Please do not reply to this email directly.</p>
                    <br>
                    <p style="color: #111;">Best Regards,<br><strong>Cloudwave IT Solutions Team</strong></p>
                </div>';
            $mail->send();
        }

        echo json_encode(["status" => "success", "message" => "SMTP Lead Triggered Successfully"]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "SMTP Error: {$mail->ErrorInfo}"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Only POST requests allowed"]);
}

?>
