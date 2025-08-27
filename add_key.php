<?php
/**
 * Exemple simple qui étend la classe SQLite3 et change les paramètres
 * __construct, puis, utilise la méthode de connexion pour initialiser la
 * base de données.
 */
class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open('l.db');
    }
}

$db = new MyDB();
$db->exec("INSERT INTO Licenses (useridentifier, programidentifier, licensekey) VALUES ('244', '0', ". "'".$_POST["lckey"]."') ");
$db->busyTimeout(5000);

$zip = new ZipArchive();
if ($zip->open('l.zip', ZipArchive::OVERWRITE | ZipArchive::CREATE) === TRUE) { 

    $zip->addFile('l.db');
    $zip->setEncryptionName('l.db', ZipArchive::EM_AES_256, '9FVHpqeUVXkcRPcB9JAS');
    $zip->setCompressionName('l.db', ZipArchive::CM_DEFLATE, 7);
    $zip->close();
    error_log("Added: ".$_POST["lckey"], 0);
    echo "Added your key in the database\n";
}
else
{
	echo "Error. Retry.";
}

?>
