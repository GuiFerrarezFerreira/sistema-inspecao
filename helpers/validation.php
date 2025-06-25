<?php
// helpers/validation.php
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validarCampoObrigatorio($campo, $nome_campo) {
    if (empty($campo)) {
        throw new Exception("O campo {$nome_campo} é obrigatório");
    }
    return true;
}

function sanitizarString($string) {
    return htmlspecialchars(strip_tags($string));
}