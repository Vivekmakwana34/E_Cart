<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

require_once '../../Database_file/config.php';
require_once 'C:/laragon/www/E_Cart/vendor/tecnickcom/tcpdf/tcpdf.php';

$userId = $_SESSION['user_id'];
$orderId = $_GET['order_id'] ?? 0;

// Validate order belongs to user
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.0 404 Not Found');
    exit('Order not found');
}

$order = $result->fetch_assoc();

// Parse shipping address
$shippingParts = explode("\n", $order['shipping_address']);
$customerName = $order['full_name'] ?? 'Customer';
$addressLine = $shippingParts[0] ?? '';
$cityState = $shippingParts[1] ?? '';
$pincode = $shippingParts[2] ?? '';

// Create new PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Remove header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);

// Add page
$pdf->AddPage();

// Colors
$black = array(0, 0, 0);
$darkGray = array(51, 51, 51);
$mediumGray = array(102, 102, 102);
$lightGray = array(240, 240, 240);
$borderGray = array(200, 200, 200);
$amazonOrange = array(255, 153, 0);

// ===== HEADER =====
$pdf->SetFont('helvetica', 'B', 22);
$pdf->SetTextColor($amazonOrange[0], $amazonOrange[1], $amazonOrange[2]);
$pdf->Cell(90, 10, 'E-CART.in', 0, 0, 'L');

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor($black[0], $black[1], $black[2]);
$pdf->Cell(0, 6, 'Tax Invoice/Bill of Supply/Cash Memo', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, '(Original for Recipient)', 0, 1, 'R');

$pdf->Ln(2);

// ===== SOLD BY & BILLING ADDRESS =====
$soldByY = $pdf->GetY();

// Sold By
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor($black[0], $black[1], $black[2]);
$pdf->Cell(95, 6, 'Sold By :', 0, 0);
$pdf->Cell(0, 6, 'Billing Address :', 0, 1);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor($darkGray[0], $darkGray[1], $darkGray[2]);
$pdf->Cell(95, 5, 'E-CART Online Services Private Limited', 0, 0);
$pdf->Cell(0, 5, $customerName, 0, 1);

$pdf->Cell(95, 5, 'Commerce Tower, Survey No 123,', 0, 0);
$pdf->Cell(0, 5, $addressLine, 0, 1);

$pdf->Cell(95, 5, 'Electronic City Phase 1,', 0, 0);
$pdf->Cell(0, 5, $cityState, 0, 1);

$pdf->Cell(95, 5, 'Bangalore, Karnataka - 560100', 0, 0);
$pdf->Cell(0, 5, $pincode, 0, 1);

$pdf->Cell(95, 5, 'India', 0, 0);
$pdf->Cell(0, 5, 'State/UT Code: 24', 0, 1);

$pdf->Ln(3);

// PAN, GST, CIN
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'PAN No: AACFK0693D', 0, 1);
$pdf->Cell(0, 5, 'GST Registration No: 24AACFK0693D3ZN', 0, 1);
$pdf->Cell(0, 5, 'CIN No: U51900KA2010PTC053234', 0, 1);

$pdf->Ln(3);

// ===== SHIPPING ADDRESS =====
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, 'Shipping Address :', 0, 1);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, $customerName, 0, 1);
$pdf->Cell(0, 5, $addressLine, 0, 1);
$pdf->Cell(0, 5, $cityState, 0, 1);
$pdf->Cell(0, 5, $pincode, 0, 1);
$pdf->Cell(0, 5, 'State/UT Code: 24', 0, 1);

$pdf->Ln(3);

// ===== ORDER & INVOICE DETAILS =====
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(95, 6, 'Order Number: ' . $order['order_number'], 0, 0);
$pdf->Cell(0, 6, 'Invoice Number : AMD2-894226', 0, 1);

$pdf->Cell(95, 6, 'Order Date: ' . date('d.m.Y', strtotime($order['created_at'])), 0, 0);
$pdf->Cell(0, 6, 'Invoice Details : GJ-AMD2-130606635-2526', 0, 1);

$pdf->Cell(95, 6, '', 0, 0);
$pdf->Cell(0, 6, 'Invoice Date : ' . date('d.m.Y', strtotime($order['created_at'])), 0, 1);

$pdf->Ln(2);

// Place of supply/delivery
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Place of supply: GUJARAT', 0, 1);
$pdf->Cell(0, 5, 'Place of delivery: GUJARAT', 0, 1);

$pdf->Ln(3);

// ===== PRODUCT TABLE - FIXED =====
// Column widths adjusted to fit within 190mm page width
$pdf->SetFillColor(230, 230, 230);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor($black[0], $black[1], $black[2]);

// Table headers - total width 173mm fits within margins
$pdf->Cell(10, 10, 'Sl.No', 1, 0, 'C', true);
$pdf->Cell(55, 10, 'Description', 1, 0, 'C', true);
$pdf->Cell(18, 10, 'Unit Price', 1, 0, 'C', true);
$pdf->Cell(10, 10, 'Qty', 1, 0, 'C', true);
$pdf->Cell(18, 10, 'Net Amt', 1, 0, 'C', true);
$pdf->Cell(12, 10, 'Tax%', 1, 0, 'C', true);
$pdf->Cell(15, 10, 'Tax Type', 1, 0, 'C', true);
$pdf->Cell(15, 10, 'Tax Amt', 1, 0, 'C', true);
$pdf->Cell(20, 10, 'Total', 1, 1, 'C', true);

// Table content
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor($darkGray[0], $darkGray[1], $darkGray[2]);

$amount = $order['total_amount'];
$taxRate = '0%';
$taxType = 'CGST';
$taxAmount = '0.00';

// Row 1 - Order Items
$rowHeight = 16;
$pdf->Cell(10, $rowHeight, '1', 1, 0, 'C');
$pdf->MultiCell(55, 5, "E-CART Order #" . $order['order_number'] . "\n(Order Items)", 1, 'L', false, 0);
$pdf->Cell(18, $rowHeight, number_format($amount, 2), 1, 0, 'R');
$pdf->Cell(10, $rowHeight, '1', 1, 0, 'C');
$pdf->Cell(18, $rowHeight, number_format($amount, 2), 1, 0, 'R');
$pdf->Cell(12, $rowHeight, $taxRate, 1, 0, 'C');
$pdf->Cell(15, $rowHeight, $taxType, 1, 0, 'C');
$pdf->Cell(15, $rowHeight, $taxAmount, 1, 0, 'R');
$pdf->Cell(20, $rowHeight, number_format($amount, 2), 1, 1, 'R');

// Row 2 - Shipping
$pdf->Cell(10, 8, '', 1, 0, 'C');
$pdf->Cell(55, 8, 'Shipping Charges', 1, 0, 'L');
$pdf->Cell(18, 8, '0.00', 1, 0, 'R');
$pdf->Cell(10, 8, '1', 1, 0, 'C');
$pdf->Cell(18, 8, '0.00', 1, 0, 'R');
$pdf->Cell(12, 8, '0%', 1, 0, 'C');
$pdf->Cell(15, 8, 'CGST', 1, 0, 'C');
$pdf->Cell(15, 8, '0.00', 1, 0, 'R');
$pdf->Cell(20, 8, '0.00', 1, 1, 'R');

// TOTAL row - highlighted
$pdf->SetFillColor(255, 243, 224);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10, 8, '', 1, 0, 'C', true);
$pdf->Cell(55, 8, '', 1, 0, 'L', true);
$pdf->Cell(18, 8, '', 1, 0, 'R', true);
$pdf->Cell(10, 8, '', 1, 0, 'C', true);
$pdf->Cell(18, 8, '', 1, 0, 'R', true);
$pdf->Cell(12, 8, '', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'TOTAL:', 1, 0, 'R', true);
$pdf->Cell(15, 8, '0.00', 1, 0, 'R', true);
$pdf->Cell(20, 8, number_format($amount, 2), 1, 1, 'R', true);

$pdf->Ln(6);

// ===== AMOUNT IN WORDS BOX =====
$pdf->SetFillColor(250, 250, 250);
$pdf->Rect(10, $pdf->GetY(), 115, 15, 'F');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor($black[0], $black[1], $black[2]);
$pdf->Text(12, $pdf->GetY() + 4, 'Amount in Words:');
$pdf->SetFont('helvetica', '', 9);
$pdf->Text(12, $pdf->GetY() + 10, convertNumberToWords($amount) . ' only');

// ===== AUTHORIZED SIGNATORY BOX =====
$currentY = $pdf->GetY();
$pdf->SetFillColor(250, 250, 250);
$pdf->Rect(130, $currentY, 70, 30, 'F');
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor($mediumGray[0], $mediumGray[1], $mediumGray[2]);
$pdf->Text(132, $currentY + 4, 'For E-CART Online Services');
$pdf->Text(132, $currentY + 8, 'Private Limited:');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Text(150, $currentY + 25, 'Authorized Signatory');

$pdf->SetY($currentY + 35);

// ===== REVERSE CHARGE =====
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor($darkGray[0], $darkGray[1], $darkGray[2]);
$pdf->Cell(0, 6, 'Whether tax is payable under reverse charge - No', 0, 1);

$pdf->Ln(3);

// ===== PAYMENT DETAILS BOX =====
$currentY = $pdf->GetY();
$pdf->SetFillColor(245, 245, 245);
$pdf->Rect(10, $currentY, 190, 22, 'F');
$pdf->SetDrawColor(200, 200, 200);
$pdf->Rect(10, $currentY, 190, 22, 'D');

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor($black[0], $black[1], $black[2]);
$pdf->Text(12, $currentY + 5, 'Payment Details');

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor($mediumGray[0], $mediumGray[1], $mediumGray[2]);
$pdf->Text(12, $currentY + 11, 'Transaction ID: ' . $order['order_number'] . '/Paytm');
$pdf->Text(12, $currentY + 16, 'Date & Time: ' . date('d/m/Y, H:i:s', strtotime($order['created_at'])));

$pdf->Text(100, $currentY + 11, 'Invoice Value: Rs. ' . number_format($amount, 2));
$pdf->Text(100, $currentY + 16, 'Mode of Payment: ' . $order['payment_method']);

$pdf->SetY($currentY + 26);

// ===== PAGE 1 FOOTER =====
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(120, 120, 120);
$pdf->MultiCell(0, 4, "*E-CART Online Services Private Limited. Customers are requested to create a Business account and purchase on E-CART.in/business to avail exclusive E-CART Business features.", 0, 'C');
$pdf->Cell(0, 4, 'Please note that this invoice is not a demand for payment', 0, 1, 'C');
$pdf->Cell(0, 4, 'Page 1 of 2', 0, 1, 'R');

$pdf->Ln(5);

// ===== PAGE 2 - TERMS & CONDITIONS =====
// Ensure only ONE new page is added after page 1
if ($pdf->getPage() == 1) {
    $pdf->AddPage();
}

// Page 2 Header
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor($amazonOrange[0], $amazonOrange[1], $amazonOrange[2]);
$pdf->Cell(0, 10, 'E-CART.in', 0, 1, 'L');

$pdf->SetDrawColor($borderGray[0], $borderGray[1], $borderGray[2]);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(8);

// Invoice Summary Box (match reference)
$summaryY = $pdf->GetY();
$pdf->SetFillColor(245, 245, 245);
$pdf->Rect(10, $summaryY, 190, 32, 'F');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor($black[0], $black[1], $black[2]);
$pdf->Text(12, $summaryY + 7, 'Invoice Summary for Order: ' . $order['order_number']);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor($mediumGray[0], $mediumGray[1], $mediumGray[2]);
$pdf->Text(12, $summaryY + 15, 'Order Date: ' . date('d M Y', strtotime($order['created_at'])));
$pdf->Text(12, $summaryY + 22, 'Invoice Date: ' . date('d M Y', strtotime($order['created_at'])));

$pdf->Text(105, $summaryY + 15, 'Total Amount: Rs. ' . number_format($amount, 2));
$pdf->Text(105, $summaryY + 22, 'Payment Status: PAID');

$pdf->SetY($summaryY + 40);

// Terms & Conditions
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor($black[0], $black[1], $black[2]);
$pdf->Cell(0, 10, 'Terms & Conditions', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor($mediumGray[0], $mediumGray[1], $mediumGray[2]);
$pdf->Cell(0, 6, '1. This is a computer-generated invoice and does not require any signature.', 0, 1);
$pdf->Cell(0, 6, '2. All disputes are subject to Bangalore jurisdiction only.', 0, 1);
$pdf->Cell(0, 6, '3. Goods once sold will not be taken back or exchanged.', 0, 1);
$pdf->Cell(0, 6, '4. The seller is not responsible for any damage during transit.', 0, 1);
$pdf->Cell(0, 6, '5. Warranty claims must be made directly to the manufacturer.', 0, 1);
$pdf->Cell(0, 6, '6. For returns and refunds, please visit your order history.', 0, 1);
$pdf->Cell(0, 6, '7. Prices are inclusive of all applicable taxes unless stated otherwise.', 0, 1);
$pdf->Cell(0, 6, '8. Shipping charges are non-refundable in case of returns.', 0, 1);

$pdf->Ln(10);

// Return & Refund Policy
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor($black[0], $black[1], $black[2]);
$pdf->Cell(0, 10, 'Return & Refund Policy', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor($mediumGray[0], $mediumGray[1], $mediumGray[2]);
$pdf->MultiCell(0, 6, "Items can be returned within 7 days of delivery if they are damaged, defective, or not as described. Please ensure the product is in original packaging with all accessories. Refunds will be processed to your original payment method within 5-7 business days after we receive the returned item.", 0, 'L');

$pdf->Ln(12);

// Contact Information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor($black[0], $black[1], $black[2]);
$pdf->Cell(0, 10, 'Contact Information', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor($mediumGray[0], $mediumGray[1], $mediumGray[2]);
$pdf->Cell(0, 6, 'Customer Support: support@ecart.com', 0, 1);
$pdf->Cell(0, 6, 'Phone: 1800-123-4567 (Toll Free)', 0, 1);
$pdf->Cell(0, 6, 'Website: www.ecart.com', 0, 1);
$pdf->Cell(0, 6, 'Business Hours: Monday to Saturday, 9:00 AM - 6:00 PM IST', 0, 1);

$pdf->SetY(-30);

// Page 2 Footer
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(170, 170, 170);
$pdf->Cell(0, 5, 'E-CART Online Services Private Limited', 0, 1, 'C');
$pdf->Cell(0, 5, 'Registered Office: Commerce Tower, Electronic City, Bangalore - 560100', 0, 1, 'C');
$pdf->Cell(0, 5, 'CIN: U51900KA2010PTC053234 | GST: 24AACFK0693D3ZN', 0, 1, 'C');
$pdf->Cell(0, 5, 'Page 2 of 2', 0, 1, 'R');

// Helper function to convert number to words
function convertNumberToWords($number) {
    $no = round($number);
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    return $result . 'Rupees';
}

// Output PDF
$pdf->Output('E-CART_Invoice_' . $order['order_number'] . '.pdf', 'D');
exit;
?>
