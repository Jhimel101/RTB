<?php

// Sample campaign array
$campaigns = [
    [
        "campaign_name" => "Transsion_Native_Campaign_Test_Nov_30_2024",
        "advertiser" => "TestGP",
        "price" => 0.1,
        "country" => "Bangladesh",
        "device_make" => "No Filter",
        "creative_id" => 168962,
        "creative_type" => "201",
        "image_url" => "https://d2v3eqx6ppywls.com/sample-image.jpg",
        "url" => "https://gamestar.shabox.mobi/",
        "native_title" => "GameStar",
        "native_data_value" => "Play Tournament Game",
        "native_data_cta" => "PLAY N WIN"
    ]
];

// Read the incoming bid request
$bidRequestJson = file_get_contents('php://input');

if (empty($bidRequestJson)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid bid request"]);
    exit;
}

// Decode JSON into an array
$bidRequest = json_decode($bidRequestJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON format"]);
    exit;
}

// Validate required fields in the bid request
if (!isset($bidRequest['imp'][0]) || !isset($bidRequest['device']) || !isset($bidRequest['device']['geo'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields in bid request"]);
    exit;
}

$imp = $bidRequest['imp'][0];
$device = $bidRequest['device'];
$geo = $device['geo'];

$bidFloor = 0;
if (isset($imp['bidfloor'])) {
    $bidFloor = $imp['bidfloor'];
}

$country = null;
if (isset($geo['country'])) {
    $country = $geo['country'];
}

// Filter eligible campaigns
$eligibleCampaigns = [];
foreach ($campaigns as $campaign) {
    $isCountryMatch = ($campaign['country'] === $country || $campaign['country'] === "No Filter");
    $isPriceMatch = ($campaign['price'] >= $bidFloor);

    if ($isCountryMatch && $isPriceMatch) {
        $eligibleCampaigns[] = $campaign;
    }
}

// Select the best campaign
$selectedCampaign = null;
$highestPrice = 0;

foreach ($eligibleCampaigns as $campaign) {
    if ($campaign['price'] > $highestPrice) {
        $highestPrice = $campaign['price'];
        $selectedCampaign = $campaign;
    }
}

// Generate the bid response
if ($selectedCampaign) {
    $response = [
        "id" => uniqid(),
        "bidid" => uniqid(),
        "seatbid" => [
            [
                "bid" => [
                    [
                        "price" => $selectedCampaign['price'],
                        "adm" => json_encode([
                            "native" => [
                                "assets" => [
                                    ["id" => 101, "title" => ["text" => $selectedCampaign['native_title']], "required" => 1],
                                    ["id" => 104, "img" => ["url" => $selectedCampaign['image_url'], "type" => 1], "required" => 1],
                                    ["id" => 102, "data" => ["value" => $selectedCampaign['native_data_value'], "type" => 2], "required" => 1],
                                    ["id" => 103, "data" => ["value" => $selectedCampaign['native_data_cta'], "type" => 12], "required" => 1]
                                ],
                                "link" => ["url" => $selectedCampaign['url']]
                            ]
                        ]),
                        "id" => uniqid(),
                        "impid" => isset($imp['id']) ? $imp['id'] : "1",
                        "cid" => $selectedCampaign['creative_id'],
                        "crid" => $selectedCampaign['creative_id']
                    ]
                ],
                "seat" => "1001",
                "group" => 0
            ]
        ]
    ];
} else {
    $response = ["error" => "No eligible campaign found"];
}

// Send the response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
