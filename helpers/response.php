<?php
// helpers/response.php
function enviarResposta($success, $message, $data = null) {
    $response = array(
        "success" => $success,
        "message" => $message
    );
    
    if ($data !== null) {
        $response["data"] = $data;
    }
    
    echo json_encode($response);
}