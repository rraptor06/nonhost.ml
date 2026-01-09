# Globals.php Documentation | by toffelz

# Variables

```php 
$serverClients = array();
```

Type : tableau (array)

Rôle : garder la liste de tous les clients connectés au serveur TCP.

Utilisé pour envoyer des données aux clients, gérer les déconnexions, etc.

---

```php 
$authClients = array();
```

Type : tableau (array)

Rôle : garder les instances de `AuthClient` pour chaque client connecté.

Sert à stocker :

- état de la session

- clé de chiffrement

- groupe et status

C’est ce tableau que les méthodes de `RAS_AuthentificationProtocol` utilisent via `getAuthClient($client)` pour accéder aux infos d’un client précis.

---

# class MyDB extends SQLite3

```php 
class MyDB extends SQLite3
{
	function __construct() {
		$this->open(getcwd().'/database', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
	}

}
```

`MyDB` est une classe héritant de `SQLite3` qui ouvre le fichier de base de données du serveur.
Elle permet de lire et écrire toutes les données des comptes, y compris status, groupe, clés de mods et raisons de ban.

---

# class AuthClient - Beginning

```php 
function __construct($c, $db, $add_to_list=true) {
	global $authClients;
	$this->db = $db;
	$this->client = $c;
	$this->pEncryptionKey = generateRandomString(32);
	if($add_to_list == true) {
		$authClients[] = $this;
	}
}
```

`global $authClients;`

- Permet d’ajouter cette instance à la liste globale des clients authentifiés.

`$this->db = $db;`

- Stocke la connexion à la base de données.

`$this->client = $c;`

- Stocke la socket ou identifiant du client connecté.

`$this->pEncryptionKey = generateRandomString(32);`

- Génère une clé de session de 32 octets.

- C’est cette clé qui est utilisée pour chiffrer et déchiffrer les données entre le client et le serveur.

Condition `$add_to_list`

- Par défaut, la nouvelle instance est ajoutée au tableau global `$authClients`.

- Permet au serveur de suivre tous les clients actifs

# public function fetchAccountDataFromID($accountId)

```php 
$req = $this->db->prepare("SELECT * FROM user_list WHERE user_id = :id");
$req->bindValue(':id', $accountId, SQLITE3_INTEGER);
```

`:id` → paramètre lié à `$accountId` (ID du compte recherché)

---

```php 
$result = $req->execute();
$potential_user = $result->fetchArray(SQLITE3_ASSOC);
```

Exécute la requête.

`fetchArray(SQLITE3_ASSOC)` → récupère les résultats sous forme de tableau

---

```php 
if($potential_user === FALSE) {
    return -1;
}
```

Si aucune ligne trouvée → compte inexistant

Retour `-1` (les méthodes de `RAS_AuthentificationProtocol` utilisent ce code pour créer un compte ou renvoyer `Auth_DoesntExist`)

# public function fetchAccountDataFromDeviceID($deviceId, $licenceKey = "a")

```php 
$arrayOfUser = array();
$result = $this->db->query('SELECT * FROM user_list');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$arrayOfUser[] = $row;
}
```

Récupère tous les comptes dans un tableau

Chaque élément est un tableau (même format que dans `fetchAccountDataFromID`)

---

```php 
$checkForKey = false;
if(strlen($licenceKey) > 4) {
	$checkForKey = true;
}
```

Si `licenceKey` a plus de 4 caractères, le serveur vérifiera les clés (`ph_key`, `vl_key`, `tr_key`)

---

```php 
foreach($arrayOfUser as $potential_user)
```

Pour chaque compte, le serveur teste `device_id` puis éventuellement `licenceKey`

---

```php 
if($potential_user["device_id"] == $deviceId) {

	$this->id = $potential_user["user_id"];
	$this->nameList = $potential_user["username_list"];
	$this->lastIp = $potential_user["last_ipaddr"];
	$this->ac_status = $potential_user["ac_status"];
	$this->ac_group = $potential_user["ac_group"];
	$this->device_id = $potential_user["device_id"];
	$this->report_count = $potential_user["report_count"];
	$this->ph_key = $potential_user["ph_key"];
	$this->vl_key = $potential_user["vl_key"];
	$this->tr_key = $potential_user["tr_key"];

	return 0;

}
```

Si le `device_id` correspond, on charge toutes les propriétés dans `AuthClient` et retourne `0`

C’est le cas le plus simple : le client a déjà un compte lié à la console

---

```php 
if($checkForKey) {
	if((strcmp($potential_user["ph_key"], $licenceKey) == 0) ||
	(strcmp($potential_user["vl_key"], $licenceKey) == 0) ||
	(strcmp($potential_user["tr_key"], $licenceKey) == 0)) {
```

Vérifie si l'utilisateur possede une licence pour chaque mods.

---

```php 
$this->id = $potential_user["user_id"];
$this->nameList = $potential_user["username_list"];
$this->lastIp = $potential_user["last_ipaddr"];
$this->ac_status = $potential_user["ac_status"];
$this->ac_group = $potential_user["ac_group"];
$this->device_id = $potential_user["device_id"];
$this->report_count = $potential_user["report_count"];
$this->ph_key = $potential_user["ph_key"];
$this->vl_key = $potential_user["vl_key"];
$this->tr_key = $potential_user["tr_key"];
```

Identique à `fetchAccountDataFromID`

Permet au serveur d’avoir toutes les infos du client

---

```php 
$req1 = $this->db->prepare("UPDATE user_list SET device_id = ".strval($deviceId)." WHERE ph_key = :id");
$req1->bindValue(':id', $licenceKey);
$req1->execute();
```

Même logique pour `vl_key` et `tr_key`

Sert à lier définitivement la `licenceKey` au `device_id` pour la prochaine authentification

Après ça, le client pourra être identifié uniquement par `device_id`

# public function CreateAccountFromBaseLoginData($deviceId, $nintendoNetworkId, $typeOfMod, $licenceKey)

```php 
$phKey = "";
$vlKey = "";
$trKey = "";

switch($typeOfMod) {
    case 0:
        $phKey = $licenceKey;
        break;
    case 1:
        $vlKey = $licenceKey;
        break;
    case 2:
        $trKey = $licenceKey;
        break;
}
```

Selon le type de mod envoyé par le client :

0 → Phantom

1 → Vulkain

2 → Trinity

La `licenceKey` fournie par le client est stockée dans la colonne correspondante

Les autres clés restent vides

---

```php 
socket_getpeername($this->client, $clientIp, $clientPort);
```

Récupère l’IP du client connecté

---

```php 
$this->db->exec("INSERT INTO user_list (last_connection, username_list, last_ipaddr, ac_status, ac_group, device_id, report_count, ph_key, vl_key, tr_key) VALUES ('".date("d-m-Y H:i:s")."', '".$nintendoNetworkId."', '".$clientIp."', 0, 1, ".strval($deviceId).", 0, '".$phKey."', '".$vlKey."', '".$trKey."')");
```

Crée la ligne dans `user_list`

Valeurs par défaut :

`ac_status = 0` → pas de ban, pas de licence Trinity/Vulcain/Phantom actif

`ac_group = 1`

`report_count = 0`

---

```php 
$this->nameList = $nintendoNetworkId;
$this->lastIp = $clientIp;
$this->ac_group = 1;
$this->device_id = $deviceId;
$this->ph_key = $phKey;
$this->vl_key = $vlKey;
$this->tr_key = $trKey;
```

Après insertion, l’instance `AuthClient` contient toutes les informations comme si elle venait de la DB

Permet de continuer l’authentification immédiatement sans recharger la DB

# public function UpdateAccountData($nintendoNetworkId, $typeOfMod, $licenceKey, $deviceId)

```php 
$newUserName = $this->nameList;
if(strpos($this->nameList, $nintendoNetworkId) === false) {
    $newUserName = $newUserName.", ".$nintendoNetworkId;
}
```

Si le pseudo actuel du client (`nintendoNetworkId`) n’est pas déjà dans `nameList`, il est ajouté

Permet d’avoir un historique de tout les NNID utilisés (tout les NNID de chaque utilisateur de la console)

---

```php 
socket_getpeername($this->client, $clientIp, $clientPort);
```

Récupère l’IP actuelle du client pour la mettre à jour dans la DB.\

---

```php 
switch($typeOfMod) {
	case 0:
		$req = $this->db->prepare("UPDATE user_list SET username_list = '".$newUserName."', last_ipaddr = '".$clientIp."', ph_key = '".$licenceKey."', last_connection = '".date("d-m-Y H:i:s")."' WHERE device_id=:id");
		$req->bindValue(':id', $deviceId, SQLITE3_INTEGER);
		$req->execute();
		break;
	case 1:
		$req = $this->db->prepare("UPDATE user_list SET username_list = '".$newUserName."', last_ipaddr = '".$clientIp."', vl_key = '".$licenceKey."', last_connection = '".date("d-m-Y H:i:s")."' WHERE device_id=:id");
		$req->bindValue(':id', $deviceId, SQLITE3_INTEGER);
		$req->execute();
		break;
	case 2:
		$req = $this->db->prepare("UPDATE user_list SET username_list = '".$newUserName."', last_ipaddr = '".$clientIp."', tr_key = '".$licenceKey."', last_connection = '".date("d-m-Y H:i:s")."' WHERE device_id=:id");
		$req->bindValue(':id', $deviceId, SQLITE3_INTEGER);
		$req->execute();
		break;
}
```

Selon `typeOfMod` :

- 0 → Phantom → `ph_key`

- 1 → Vulcain → `vl_key`

- 2 → Trinity → `tr_key`

Met à jour toutes les infos importantes côté serveur pour ce client :

- pseudo

- IP

- clé de licence

- date de dernière connexion

Filtre via `device_id` pour que seul ce client soit affecté

# public function UpdateAccountStatus($deviceId, $newStatus)

Met à jour la colonne `ac_statu`s dans la DB pour un compte identifié par `device_id`

`ac_status` est un entier utilisé comme bitflags pour stocker plusieurs statuts dans un seul champ

# public function UpdateAccountGroup($deviceId, $newGroup)

`UpdateAccountGroup` met à jour le champ `ac_group` d’un compte via son `device_id`

# public function BanAccount($deviceId, $reason)

`BanAccount` met à jour la colonne `ban_reason` pour un compte via son `device_id`

Elle stocke le texte expliquant la raison du ban, et est généralement utilisée en combinaison avec `UpdateAccountStatus` pour activer le flag `AuthStatus_Banned`

# public function UpdateAccountStatusGroupFromID($ActId, $status, $group)

`UpdateAccountStatusGroupFromID` met à jour simultanément le champ `ac_status` (bitflags de statut) et le champ `ac_group` pour un compte via son `user_id`

# class AuthClient - End

# function getAuthClient($client)

```php 
foreach($authClients as $c) {
    if($c->client == $client) {
        $key = array_search($c, $authClients);
        return $authClients[$key];
    }
}
```

Parcourt toutes les instances `AuthClient`

Compare chaque `$c->client` au socket `$client` passé en argument

Si correspondance trouvée :

- Récupère la clé du tableau avec `array_search`

- Retourne l’instance correspondante

# Variable

```php 
$globalRC4Key = file_get_contents(getcwd()."/rc4_key", $maxlen=32);
```

`$globalRC4Key` contient la clé RC4 du serveur, lue depuis `rc4_key`   

Elle est utilisée pour chiffrer et déchiffrer les paquets échangés avec les clients avant application de la clé de session individuelle (`pEncryptionKey`)

# function addLog($data)

```php 
file_put_contents(getcwd()."/log_server", date('d/m/Y H:i:s')." ".$data, FILE_APPEND);
```

`getcwd()`

- Récupère le répertoire courant du script (`server.php`)

`date('d/m/Y H:i:s')`

- Formate la date et l’heure actuelles pour la mettre dans le log

`$data`

- Contenu à logger : par exemple `"[NNID] Connexion ..."`

`FILE_APPEND`

- Ajoute l’entrée à la fin du fichier sans écraser le contenu existant

# function hex_dump($string, array $options = null)

## Rôle général

Transforme une chaîne binaire (paquet RC4, données brutes) en représentation hexadécimale lisible

Montre à la fois les octets en hexadécimal et le texte ASCII correspondant

---

```php
function hex_dump($string, array $options = null) {
```

`$string` : la chaîne binaire à afficher

`$options` : tableau d’options facultatives (`line_sep`, `bytes_per_line`, `pad_char`, `want_array`)

---

```php
if (!is_scalar($string)) {
    throw new InvalidArgumentException('$string argument must be a string');
}
```

Vérifie que `$string` est une valeur scalaire (`string`, `int`, `bool`…)

Sinon, lève une exception : on ne peut pas faire un dump sur un tableau ou un objet

---

```php
if (!is_array($options)) {
    $options = array();
}
```

Si `$options` n’est pas un tableau, on initialise à un tableau vide

---

```php
$line_sep       = isset($options['line_sep'])   ? $options['line_sep'] : "\n";
$bytes_per_line = @$options['bytes_per_line']   ? $options['bytes_per_line'] : 16;
$pad_char       = isset($options['pad_char'])   ? $options['pad_char'] : '.';
```

`line_sep` → séparateur de lignes (souvent `\n`)

`bytes_per_line` → nombre d’octets affichés par ligne (16 par défaut)

`pad_char` → caractère de remplissage pour les octets non imprimables dans la partie ASCII (`.` par défaut)

`@` devant `$options['bytes_per_line']` supprime les warnings si la clé n’existe pas

---

```php
$text_lines = str_split($string, $bytes_per_line);
$hex_lines  = str_split(bin2hex($string), $bytes_per_line * 2);
```

`text_lines` → les données originales en segments de 16 octets

`hex_lines` → représentation hexadécimale correspondante (2 caractères par octet)

`str_split($string, $bytes_per_line)` : découpe la chaîne binaire en lignes de `$bytes_per_line` octets → pour la partie ASCII

`bin2hex($string)` : convertit chaque octet en deux caractères hexadécimaux

`str_split(..., $bytes_per_line * 2)` : découpe la chaîne hexadécimale pour correspondre aux lignes de `$bytes_per_line octets`

---

```php
$offset = 0;
$output = array();
$bytes_per_line_div_2 = (int)($bytes_per_line / 2);
```

`$offset` : adresse en hex de la ligne, commence à `0` et augmente de `$bytes_per_line`

`$output` : tableau qui stockera chaque ligne du dump

`$bytes_per_line_div_2` : moitié de `$bytes_per_line`, utilisée pour formater la ligne hex en deux blocs (style `hexdump -C`)

---

```php
foreach ($hex_lines as $i => $hex_line) {
    $text_line = $text_lines[$i];
```

Parcourt chaque ligne hexadécimale

`$i` : index de la ligne

`$text_line` : extrait la même ligne en ASCII correspondante

---

```php
$output []=
    sprintf('%08X',$offset) . '  ' .
```

Commence la construction de la ligne :

`sprintf('%08X',$offset)` : affiche l’offset en 8 chiffres hexadécimaux

Ajoute deux espaces après l’offset

---

```php
str_pad(
    strlen($text_line) > $bytes_per_line_div_2
    ?
        implode(' ', str_split(substr($hex_line,0,$bytes_per_line),2)) . '  ' .
        implode(' ', str_split(substr($hex_line,$bytes_per_line),2))
    :
        implode(' ', str_split($hex_line,2))
, $bytes_per_line * 3)
```

Partie hexadécimale :

`substr($hex_line,0,$bytes_per_line)` → première moitié de la ligne

`substr($hex_line,$bytes_per_line)` → deuxième moitié

`str_split(...,2)` → chaque octet en 2 caractères

`implode(' ', ...)` → sépare chaque octet par un espace

`str_pad(..., $bytes_per_line * 3)` → aligne à gauche pour garder la largeur constante

Permet d’avoir 2 colonnes hex séparées si la ligne est complète

---

```php
'  |' . preg_replace('/[^\x20-\x7E]/', $pad_char, $text_line) . '|';
```

Partie ASCII :

`preg_replace('/[^\x20-\x7E]/', $pad_char, $text_line)` : remplace les caractères non imprimables par `.` ou `$pad_char`

Affiche entre `|…|` pour la lisibilité

---

```php
$offset += $bytes_per_line;
```

Incrémente l’offset pour la ligne suivante

---

```php
$output []= sprintf('%08X', strlen($string));
```

Ajoute une ligne finale indiquant la taille totale du paquet

---

```php
return @$options['want_array'] ? $output : join($line_sep, $output) . $line_sep;
```

Si l’option `want_array` est vraie → retourne le tableau $output

Sinon → retourne une chaîne complète, avec toutes les lignes séparées par `$line_sep`

# function crypto_rc4($key, $str)

```php
function crypto_rc4($key, $str) {
```

`$key` : clé RC4 (ici venant de `rc4_key` ou `pEncryptionKey`)

`$str` : données binaires à chiffrer / déchiffrer

---

```php
$s = array();
for ($i = 0; $i < 256; $i++) {
    $s[$i] = $i;
}
```

Crée un tableau `$s` de 256 octets

Valeurs initiales :

```ini
s = [0, 1, 2, 3, ..., 255]
```

- C’est la S-box RC4

---

```php
$j = 0;
for ($i = 0; $i < 256; $i++) {
```

`$j` est un index secondaire

Boucle sur toute la S-box

---

```php
$j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
```

Décomposition :

`$i % strlen($key)`
→ boucle sur la clé si elle est plus courte que 256 octets

`ord($key[...])`
→ valeur ASCII de l’octet de clé

`$j + $s[$i] + key_byte`
→ mélange dépendant de la clé

`% 256`
→ reste dans [0–255]

C’est là que la clé influence le chiffrement

---

```php
$x = $s[$i];
$s[$i] = $s[$j];
$s[$j] = $x;
```

Échange `(swap)` `s[i]` et `s[j]`

Mélange progressif de la S-box

À la fin de cette boucle :

`$s` est une permutation dépendante de la clé

---

```php
$i = 0;
$j = 0;
$res = '';
```

Réinitialise les index

`$res` contiendra le résultat final chiffré

---

```php
for ($y = 0; $y < strlen($str); $y++) {
```

Parcourt chaque octet du message

---

```php
$i = ($i + 1) % 256;
$j = ($j + $s[$i]) % 256;
```

Avance dans la S-box

Dépend de l’état précédent

RC4 est étatful

---

```php
$x = $s[$i];
$s[$i] = $s[$j];
$s[$j] = $x;
```

Nouvel échange dans la S-box

Le flot dépend de tout l’historique

---

```php
$res .= $str[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
```

Décomposition :

`$s[$i] + $s[$j]`

`% 256`

`$s[...]` → octet pseudo-aléatoire

`chr(...)` → caractère binaire

`^` → XOR avec l’octet du message

RC4 = XOR(message, keystream)

---

```php
return $res;
```

Retourne les données chiffrées / déchiffrées

# function calc_checksum($data)

```php
for ($i=0; $i < strlen($data); $i++) {
```

Parcourt chaque octet du paquet.

`strlen($data)` → taille en octets (important : données binaires)

--- 

```php
$sum += ord($data[$i]);
```

`ord()` convertit l’octet en valeur numérique `[0–255]`

Additionne la valeur de chaque octet

Résultat = somme brute des octets

---

```php
$sum *= 14253;
```

Multiplie la somme par une constante fixe (`14253`)

---

```php
return ($sum & 0xFFFF);
```

Masque sur 16 bits

Équivalent à :

```php
$sum % 65536
```

Le checksum final tient sur 2 octets

### À chaque envoi réseau :

```php
socket_write(
    $client,
    pack("N", strlen($respData->data)) .
    pack("N", calc_checksum($respData->data)) .
    $respData->data,
    strlen($respData->data) + 8
);
```

Structure du paquet :

```ini
[4 octets] longueur
[4 octets] checksum
[ N octets ] données RC4
```

# function kick_client($client)

```php
function kick_client($client) {
```

Déclare une fonction qui prend :

`$client` → ressource socket PHP

---

```php
global $serverClients, $authClients;
```

Accède aux tableaux globaux :

`$serverClients` → liste des sockets connectés

`$authClients` → objets `AuthClient` associés aux sockets

---

```php
if(($key = array_search($client, $serverClients)) !== false) {
```

Cherche la socket `$client` dans la liste `$serverClients`.

`array_search` retourne :

l’index si trouvé

`false` sinon

---

```php
unset($serverClients[$key]);
```

Supprime la socket de la liste des clients connectés.

---

```php
foreach ($authClients as &$ac) {
```

Parcourt tous les objets `AuthClient`.

Le `&` signifie référence

---

```php
if($ac->client == $client) {
```

Vérifie si cet `AuthClient` correspond à la socket fermée

---

```php
unset($ac);
```

Supprime l’objet `AuthClient` associé

# function generateRandomString($length)

```php
function generateRandomString($length) {
```

`$length` = longueur finale souhaitée

---

```php
$x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
```
Contient :

- chiffres (10)

- minuscules (26)

- majuscules (26)

Total : 62 caractères

---

```php
ceil($length / strlen($x))
```

`strlen($x) = 62`

Exemple :

- `$length = 32`

- `32 / 62 ≈ 0.51`

- `ceil(...) = 1`

Garantit qu’on a au moins `$length` caractères disponibles après mélange

---

```php
str_repeat($x, ceil(...))
```

Répète l’alphabet autant de fois que nécessaire

---

```php
str_shuffle(...)
```

Mélange tous les caractères de manière pseudo-aléatoire

--- 

```php
substr(..., 1, $length)
```

Prend `$length` caractères à partir de l’index `1`

---

```php
return substr(...);
```


Renvoie une chaîne aléatoire de longueur exacte

