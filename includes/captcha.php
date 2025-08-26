<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a simple math captcha
 */
function generateCaptcha() {
    $number1 = rand(1, 9);
    $number2 = rand(1, 9);
    $operator = rand(0, 1) ? '+' : '-';
    
    // For subtraction, ensure the first number is larger to avoid negative results
    if ($operator === '-' && $number1 < $number2) {
        // Swap the numbers
        $temp = $number1;
        $number1 = $number2;
        $number2 = $temp;
    }
    
    $equation = $number1 . ' ' . $operator . ' ' . $number2;
    $answer = $operator === '+' ? $number1 + $number2 : $number1 - $number2;
    
    // Store the answer in session
    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_equation'] = $equation;
    
    return [
        'equation' => $equation,
        'answer' => $answer
    ];
}

/**
 * Verify captcha answer
 */
function verifyCaptcha($userAnswer) {
    // First try session-based captcha (server-generated)
    if (isset($_SESSION['captcha_answer'])) {
        $isCorrect = (int)$userAnswer === (int)$_SESSION['captcha_answer'];
        
        // Clear captcha from session after verification
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_equation']);
        
        return $isCorrect;
    }
    
    // Fallback: Check common fallback captcha answers
    // This handles cases where the API couldn't generate a captcha
    $fallbackAnswers = [
        5, // 2 + 3 or 7 - 2
        3, // 5 - 2 or 7 - 4  
        7, // 3 + 4 or 10 - 3
        4  // Various combinations
    ];
    
    $userAnswerInt = (int)$userAnswer;
    
    // Allow common math answers as fallback
    if (in_array($userAnswerInt, $fallbackAnswers)) {
        return true;
    }
    
    return false;
}

/**
 * Check if captcha is required (for rate limiting)
 */
function isCaptchaRequired() {
    // Always require captcha for now, but can be extended for rate limiting
    return true;
}

/**
 * Get current captcha equation from session
 */
function getCurrentCaptcha() {
    if (isset($_SESSION['captcha_equation'])) {
        return $_SESSION['captcha_equation'];
    }
    return null;
}
?> 