<?php
require_once 'vendor/autoload.php';

class EmailVerification {
    private $conn;
    private $mail;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->setupMail();
    }
    
    private function setupMail() {
        $this->mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Gmail SMTP settings
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'dudhagarabgo@gmail.com';
        $this->mail->Password = 'vzhk sasn skgy gfru';
        $this->mail->SMTPSecure = 'tls';
        $this->mail->Port = 587;
        
        $this->mail->setFrom('dudhagarabgo@gmail.com', 'E_Cart');
    }
    
    public function generateVerificationToken($userId, $email) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Delete existing token
        $sql = "DELETE FROM email_verifications WHERE email = ?";
        $stmt = executeQuery($this->conn, $sql, [$email], "s");
        
        // Insert new token
        $sql = "INSERT INTO email_verifications (user_id, email, verification_token, expires_at) 
                VALUES (?, ?, ?, ?)";
        $params = [$userId, $email, $token, $expiresAt];
        $types = "isss";
        
        $stmt = executeQuery($this->conn, $sql, $params, $types);
        
        return $token;
    }
    
    public function sendVerificationEmail($email, $fullName, $token) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $fullName);
            
            $verificationLink = "http://localhost/E_Cart/verify_email.php?token=" . $token;
            
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Verify Your E_Cart Account';
            
            $this->mail->Body = '
            <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
                <div style="background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="margin: 0; font-size: 28px;">E_Cart</h1>
                    <p style="margin: 5px 0 0; opacity: 0.9;">Your Online Shopping Destination</p>
                </div>
                
                <div style="background: white; padding: 30px; border: 1px solid #e5e7eb; border-top: none;">
                    <h2 style="color: #1f2937; margin: 0 0 10px;">Welcome to E_Cart, ' . htmlspecialchars($fullName) . '!</h2>
                    <p style="color: #6b7280; line-height: 1.6; margin: 0 0 20px;">
                        Thank you for creating an account with E_Cart. Please verify your email address by clicking the button below.
                    </p>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $verificationLink . '" 
                           style="background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">
                            Verify Email Address
                        </a>
                    </div>
                    
                    <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 20px 0 0;">
                        Or copy and paste this link into your browser:<br>
                        <span style="word-break: break-all; color: #2563eb;">' . $verificationLink . '</span>
                    </p>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                        <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                            This verification link will expire in 24 hours.<br>
                            If you didn\'t create an account with E_Cart, you can safely ignore this email.
                        </p>
                    </div>
                </div>
                
                <div style="background: #f9fafb; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; border: 1px solid #e5e7eb; border-top: none;">
                    <p style="color: #6b7280; font-size: 12px; margin: 0;">
                        © 2026 E_Cart. All rights reserved.<br>
                        Best deals on mobiles, fashion, electronics & more.
                    </p>
                </div>
            </div>';
            
            $this->mail->AltBody = 'Welcome to E_Cart, ' . $fullName . '! Please verify your email: ' . $verificationLink;
            
            $this->mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    public function verifyEmail($token) {
        $sql = "SELECT * FROM email_verifications 
                WHERE verification_token = ? AND expires_at > NOW() AND is_verified = FALSE";
        $stmt = executeQuery($this->conn, $sql, [$token], "s");
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $verification = $result->fetch_assoc();
            
            // Mark as verified
            $updateSql = "UPDATE email_verifications SET is_verified = TRUE WHERE id = ?";
            executeQuery($this->conn, $updateSql, [$verification['id']], "i");
            
            // Update users table
            $userSql = "UPDATE users SET email_verified = TRUE WHERE id = ?";
            executeQuery($this->conn, $userSql, [$verification['user_id']], "i");
            
            return $verification['user_id'];
        }
        
        return false;
    }
}
?>
