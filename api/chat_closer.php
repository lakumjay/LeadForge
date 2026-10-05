<?php
/**
 * LeadForge AI - Autonomous Deal Closer & Client Negotiation Assistant
 * Reads client inquiries, formulates psychological pricing responses, and closes deals
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$clientMessage = trim($data['client_message'] ?? '');
$serviceType = trim($data['service_type'] ?? 'Web Development & Bug Fix');
$targetPriceUsd = (float)($data['target_price_usd'] ?? 150);
$clientName = trim($data['client_name'] ?? '');

if (empty($clientMessage)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide the client inquiry text to analyze.']);
    exit;
}

$settings = getSettings();
$userName = $settings['user_name'] ?? 'Jay';

try {
    $closerResponse = analyzeAndCloseDeal($clientMessage, $serviceType, $targetPriceUsd, $clientName, $userName, $settings);
    echo json_encode(['status' => 'success', 'data' => $closerResponse]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Deal analysis failed: ' . $e->getMessage()]);
}

function analyzeAndCloseDeal(
    string $message,
    string $service,
    float $targetPrice,
    string $clientName,
    string $userName,
    array $settings
): array {
    $msgLower = strtolower($message);
    $greeting = !empty($clientName) ? "Hey {$clientName}," : "Hey,";

    // 1. Detect Intent Category
    $intent = 'General Technical Inquiry';
    $strategy = 'Provide clear scope clarity and offer a low-friction 24-hour turnaround.';
    $pricingQuote = "\${$targetPrice} fixed (including testing & warranty)";

    if (stripos($msgLower, 'how much') !== false || stripos($msgLower, 'cost') !== false || stripos($msgLower, 'price') !== false || stripos($msgLower, 'rate') !== false) {
        $intent = 'Pricing & Budget Negotiation';
        $strategy = 'Give a transparent fixed-price bracket with zero risk guarantee to close immediately.';
        $pricingQuote = "\${$targetPrice} fixed";
    } elseif (stripos($msgLower, 'portfolio') !== false || stripos($msgLower, 'previous work') !== false || stripos($msgLower, 'examples') !== false) {
        $intent = 'Trust & Portfolio Proof Verification';
        $strategy = 'Share specific case studies and emphasize immediate live debugging over generic resumes.';
    } elseif (stripos($msgLower, 'when') !== false || stripos($msgLower, 'how fast') !== false || stripos($msgLower, 'timeline') !== false || stripos($msgLower, 'urgent') !== false) {
        $intent = 'Urgent Timeline & Fast Delivery';
        $strategy = 'Leverage same-day turnaround to win the project today.';
    }

    // 2. Draft the Winning Closer Reply
    if ($intent === 'Pricing & Budget Negotiation') {
        $reply = "{$greeting}\n\n"
            . "For this task ({$service}), I can take care of the entire setup, testing, and deployment for a straightforward flat fee of \${$targetPrice}.\n\n"
            . "Here is what is included:\n"
            . "1. Complete root cause fix and clean implementation.\n"
            . "2. Thorough cross-device testing to ensure zero regressions.\n"
            . "3. 7-day post-delivery warranty in case you need any tweaks.\n\n"
            . "I am free to start right now and can deliver this within the next few hours. Would you like to get this rolling today?\n\n"
            . "Best,\n{$userName}";
    } elseif ($intent === 'Trust & Portfolio Proof Verification') {
        $reply = "{$greeting}\n\n"
            . "Happy to share! I regularly build and optimize {$service} systems across US, UK, and European clients, specializing in clean architecture and fast turnarounds.\n\n"
            . "Rather than just sharing generic links, I can hop on a quick screen share or deploy the exact fix on your staging/repo within a couple of hours so you can inspect the working code firsthand.\n\n"
            . "Shall I take a look at the repo or codebase right now?\n\n"
            . "Best,\n{$userName}";
    } else {
        $reply = "{$greeting}\n\n"
            . "Thanks for getting back to me! I can handle this {$service} task right away.\n\n"
            . "The entire fix and verification will take roughly 2 to 4 hours, and I can keep it under a simple flat rate of \${$targetPrice}.\n\n"
            . "Feel free to share the access details or repo link whenever you're ready, and I will jump straight in!\n\n"
            . "Best,\n{$userName}";
    }

    return [
        'detected_intent' => $intent,
        'closing_strategy' => $strategy,
        'recommended_price' => $pricingQuote,
        'ready_reply' => $reply,
        'word_count' => str_word_count($reply)
    ];
}
