<?php
$url = 'http://localhost:8000/admin_API/update_subscription_plan.php';
$data = [
    'id' => 1,
    'plan_name' => 'Emerald Premium updated via PHP',
    'insurance_coverage' => 'Individual',
    'payment_terms' => 'Every Month',
    'subscription_term' => 24,
    'insurance_term' => 24,
    'quota_weight' => 2.0,
    'requires_beneficiaries' => true,
    'onboarding_product_id' => 4,
    'monthly_product_id' => 3,
    'otc_l1' => 700,
    'monthly_pvoucher' => 200,
    'position_residuals' => [
        ['rank' => 'Unit Manager', 'amount' => 180]
    ],
    'insurance_benefits' => [
        ['name' => 'Accidental Death Benefit', 'amount' => 150000, 'contestability' => 0]
    ],
    'claim_requirements' => ['Valid ID', 'Birth Certificate', 'Affidavit'],
    'billing_brackets' => [
        ['from' => 1, 'to' => 31, 'bill_on' => 25]
    ]
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo $result;
?>
