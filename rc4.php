<?php

function rc4($key, $str) {
    $s = array();
    for ($i = 0; $i < 256; $i++) {
        $s[$i] = $i;
    }
    $j = 0;
    for ($i = 0; $i < 256; $i++) {
        $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
        $x = $s[$i];
        $s[$i] = $s[$j];
        $s[$j] = $x;
    }
    $i = 0;
    $j = 0;
    $res = '';
    for ($y = 0; $y < strlen($str); $y++) {
        $i = ($i + 1) % 256;
        $j = ($j + $s[$i]) % 256;
        $x = $s[$i];
        $s[$i] = $s[$j];
        $s[$j] = $x;
        $res .= $str[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
    }
    return $res;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['input_file']) || $_FILES['input_file']['error'] !== UPLOAD_ERR_OK) {
        echo "Erreur lors de l'upload du fichier.";
        exit;
    }

    $input_tmp = $_FILES['input_file']['tmp_name'];
    $output_file = trim($_POST['output_file']);
    $key = trim($_POST['key']);

    if (empty($output_file) || empty($key)) {
        echo "Veuillez entrer un nom de fichier de sortie et une clé.";
        exit;
    }

    $data = file_get_contents($input_tmp);
    $encrypted_data = rc4($key, $data);
    file_put_contents($output_file, $encrypted_data);

    echo "Fichier chiffré créé : <b>$output_file</b>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Chiffrement RC4</title>
</head>
<body>
<h2>Chiffrement RC4</h2>
<form method="post" enctype="multipart/form-data">
    <label>Fichier à chiffrer :</label><br>
    <input type="file" name="input_file" required><br><br>

    <label>Nom du fichier de sortie :</label><br>
    <input type="text" name="output_file" placeholder="Ex: output.enc" required><br><br>

    <label>Clé RC4 :</label><br>
    <input type="text" name="key" placeholder="Ex: YhU4880KL6" required><br><br>

    <input type="submit" value="Chiffrer">
</form>
</body>
</html>