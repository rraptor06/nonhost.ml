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

if(is_uploaded_file($_FILES['fichier']['tmp_name']) && isset($_POST['radio']))
{

        if(strcmp($_POST['passtext'], "eg4p8A2CbW8R") == 0)
        {
                echo "good pass.";
        }
        else
        {
                exit("wrong pass.");
        }

        $key = '';

        ///home/ubuntu/php/

        if(strcmp($_POST['radio'], "Rambo") == 0)
        {
                $name = "1sdq1s2f1d32fqdf1123df15s6485da2";
                $key .= "9sPLcdotTwUclhEy";
        }

        if(strcmp($_POST['radio'], "LTO") == 0)
        {
                $name = "q8fsd5qsdf5q4fdh4utuyi45ghj5k4hj";
                $key .= "XeYjobzW6h";
        }

        if(strcmp($_POST['radio'], "Adil") == 0)
        {
                $name = "85q9tru781lg78y9j46v2dcf52qs6d56";
                $key .= "YhU4880KL6";
        }

        if(strcmp($_POST['radio'], "KeyZiroMC") == 0)
        {
                $name = "jsrnwdb65fdf5sd4f5d5fd6gf4w1d65f";
                $key .= "B3ca63cSAX";
        }

        $file = $_FILES['fichier']['tmp_name'];   // Le fichier téléversé
        $dest = $name; // Sa destination

        echo $file;
        echo $dest;

        if (move_uploaded_file($file, $dest)) {
                echo "Le fichier est valide, et a été téléchargé avec succès. Voici plus d'informations :\n";
                $handle = fopen($name, "rb+");
                if (FALSE === $handle) {
                    exit("Echec lors de l'ouverture du flux");
                }

                $contents = '';
                while (!feof($handle)) {
                    $contents .= fread($handle, 0x800);
                }

                fseek($handle, 0, SEEK_SET);
                fwrite($handle, rc4($key, $contents));
                echo "RC4 ECRIT ET BIEN ECRIT PTN" . $key;
                fflush($handle);
                fclose($handle);

        } else {
                 echo "Attaque potentielle par téléchargement de fichiers. Voici plus d'informations :\n";
        }
}
else
{
        echo "The upload failed.\n";
}

print_r($_FILES);

if(isset($_POST['radio']))
{
        echo "You have selected : ".$_POST['radio'];  //  Displaying Selected Value

}

?>
