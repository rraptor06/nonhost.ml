# AuthentificationProtocol.php Documentation | by toffelz

# class RAS_AuthentificationProtocol - Beginning

# Variables


| Type  | Name                  | Value       | 
|:-----:|:---------------------:|:-----------:|
| const | `AuthStatus_Banned`   | **1 << 29** | 
| const | `AuthStatus_Vulcain`  | **1 << 17** |
| const | `AuthStatus_Phantom`  | **1 << 16** |
| const | `AuthStatus_Trinity`  | **1 << 15** |
|       |                       |             |
| const | `Auth_TrialAccount`   | **0x03**    |
| const | `Auth_AllowedAccount` | **0x04**    |
| const | `Auth_BannedAccount`  | **0x05**    |
| const | `Auth_OK`             | **0x07**    |
| const | `Auth_NotEnoughPerm`  | **0x08**    |
| const | `Auth_DoesntExist`    | **0x09**    |

Les 4 premieres variables sont des bitflags  
La syntaxe `1 << n` crée un entier avec un seul bit à 1 à la position n

Exemple : 
```php 
1 << 0 = 0000 0001
1 << 15 = 0100 0000 0000 0000
```

Les bitflags permettent de combiner plusieurs états dans un seul entier :  

Exemple : 
```php 
$status = AuthStatus_Trinity | AuthStatus_Phantom;
```

---

Les 6 autres variables sont des codes de réponse uniques, envoyés au client  
Chaque valeur représente un seul état d’authentification, non combinable

**Résumé :**  
 
**Bitflags (AuthStatus_\*)** → combinables → interne / DB  
**Codes (Auth_\*)** → uniques → retour client / protocole

# function __construct($db)

```php 
$this->db = $db;
```

Stocke une connexion base de données
ne crée pas la DB, elle la reçoit

---

```php 
$this->key = file_get_contents(getcwd()."/rc4_key", $maxlen=32);
```

Lit le fichier `rc4_key` depuis le répertoire du serveur  
limite la lecture à 32 octets max : `$maxlen=32`

# private function AP_SimpleAuthentification($client, $data)

```php 
$stream = new StreamData($data);
$respData = new StreamData("");
$respData->max_off = 0x100;
```

`$stream` → lecture des données envoyées par le client

`$respData` → buffer pour construire la réponse

`max_off = 0x100` → limite maximale de la taille de sortie

---

```php 
$typeOfMod = $stream->u8();
$deviceId = $stream->u32();
$nintendoNetworkId = $stream->c_str();
$licenceKey = $stream->c_str();
```

`$typeOfMod` → type de client (Trinity / Vulkain / Phantom)

`$deviceId` → identifiant unique de la console Wii U

`$nintendoNetworkId` → NNID du joueur

`$licenceKey` → clé licence du client

---

```php 
printf("Type: %d\nDeviceID: %d\nNNID: %s\nLicence Key: %s\n", $typeOfMod, $deviceId, $nintendoNetworkId, $licenceKey);
socket_getpeername($client, $cIp);
addLog("Connection of ".getColoredString($cIp, "light_cyan")." ".getColoredString($nintendoNetworkId, "light_red")." with key ".getColoredString($licenceKey, "light_red")."\n");
```

Affiche dans la console le détail de la connexion

`addLog()` → enregistre la connexion dans le log serveur avec IP + NNID + key

---

```php
if($stream->error == -1) {
	kick_client($client);
	return;
}
```

Vérifie si la taille / structure des données reçues est correcte

Sinon → on déconnecte le client

---

```php
$aCl = new AuthClient($client, $this->db);

if($aCl->fetchAccountDataFromDeviceID($deviceId, $licenceKey) < 0 ) {
	$aCl->CreateAccountFromBaseLoginData($deviceId, $nintendoNetworkId, $typeOfMod, $licenceKey);
} else {
	$aCl->UpdateAccountData($nintendoNetworkId, $typeOfMod, $licenceKey, $deviceId);
}
```

`$aCl` → objet représentant le client authentifié côté serveur

`fetchAccountDataFromDeviceID()` → cherche un compte existant

Si inexistant → crée un compte automatiquement (`CreateAccountFromBaseLoginData()`)

Sinon → met à jour les infos (`UpdateAccountData()`)

---

```php
$outCode = $this::Auth_AllowedAccount;

printf("Code: %d (%d %08X)\n\n", $outCode, $aCl->ac_status, $aCl->ac_status);
```

`$outCode` → valeur envoyée au client (`0x04` = Auth_AllowedAccount)

Affiche le status interne du compte pour debug

Le client saura qu’il est autorisé à continuer

---

```php
$respData->w_u32($outCode);
$respData->w_c_str($aCl->pEncryptionKey);
$respData->data = crypto_rc4($this->key, $respData->data);
```

Écrit le code de retour et la clé d’encryption du client dans le buffer

Ensuite chiffre tout le flux avec la clé RC4 (`$this->key`) → sécurité côté client

La réponse d’authentification est construite dans un seul buffer (`StreamData`)

Les champs sont ajoutés séquentiellement (code + clé), puis l’ensemble du buffer est chiffré via la clé RC4 avant l’envoi

---

```php
socket_write($client, pack("N", strlen($respData->data)).pack("N", calc_checksum($respData->data)).$respData->data, strlen($respData->data) + 8);
```

`pack("N", …)` → transforme la taille et le checksum en format réseau big-endian

`$respData->data` → binaire chiffré avec la clé RC4

Le client reçoit : longueur + checksum + données chiffrées

---

### Résumé de la méthode

- Lit les données envoyées par le client (type, deviceId, NNID, licence)

- Vérifie l’intégrité / taille du flux

- Crée ou met à jour le compte dans la DB

- Détermine un code de retour (`Auth_AllowedAccount`)

- Écrit le code + clé d’encryption dans un flux

- Chiffre le flux avec clé RC4 serveur

- Envoie le flux au client (longueur + checksum + données)

# private function AP_VerifyConnection($client, $data)

```php
$data = crypto_rc4(getAuthClient($client)->pEncryptionKey, $data);
```
Le client a chiffré `$data` avec sa clé de session

Le serveur la décrypte

On n’utilise pas `$this->key` ici

Toutes les communications post-authentification utilisent la clé de session spécifique au client

---

```php
$code = $stream->u32();
```

Le client envoie exactement **4 octets**

---

```php
if($code == 0x13371337) {
	$respData->w_u32(0x13371337);
} else {
	$respData->w_u32(0xCAFEDEAD);
}
```

| Cas  | Réponse      |
|:----:|:------------:|
| OK   | `0x13371337` |
| FAIL | `0xCAFEDEAD` |

---

```php
$respData->data = crypto_rc4(getAuthClient($client)->pEncryptionKey, $respData->data);
$respData->data = crypto_rc4($this->key, $respData->data);
```

### Double chiffrement 

Ordre exact :

- RC4 avec clé client

- RC4 avec clé serveur globale

Donc côté client, pour lire la réponse :

- RC4 avec `$this->key`

- RC4 avec `pEncryptionKey`

---

```php
socket_write($client, pack("N", strlen($respData->data)).pack("N", calc_checksum($respData->data)).$respData->data, strlen($respData->data) + 8);
```
Format final :  
```
[ length (4) ][ checksum (4) ][ data (RC4 double-chiffré) ]
```

# private function AP_UpdateAccountStatus($client, $data)

```php
$data = crypto_rc4(getAuthClient($client)->pEncryptionKey, $data);
```

Le client chiffre la requête avec sa clé de session

Le serveur la déchiffre

Aucune clé serveur globale ici

Même schéma que `AP_VerifyConnection` côté entrée

---

```php
$deviceId = $stream->u32();
$newStatus = $stream->u32();
```

Le client envoie exactement 8 octets :

| Champ       | Type | Description                 |
|:-----------:|:----:|:--------------------------: |
| `deviceId`  | u32  | Identifiant du compte cible |
| `newStatus` | u32  | Nouveau status (bitflags)   |

`newStatus` est un champ de bitflags, typiquement :

- `AuthStatus_Trinity`

- `AuthStatus_Banned`

- etc. (combinables)

---

```php
if(getAuthClient($client)->ac_group >= 5) {
```

`ac_group` = niveau de permission

La méthode `AP_UpdateAccountStatus` est protégée par un contrôle de permissions basé sur le champ `ac_group`  

Seuls les comptes dont `ac_group >= 5` sont autorisés à modifier le status d’un autre compte  

Les comptes ne satisfaisant pas cette condition reçoivent la réponse `Auth_NotEnoughPerm`

---

```php
if($aCl->fetchAccountDataFromDeviceID($deviceId) < 0 ) {
    $respData->w_u32($this::Auth_DoesntExist);
}
```

Code retour :

- `Auth_DoesntExist (0x09)`

---

```php
else {
	$aCl->UpdateAccountStatus($deviceId, $newStatus);
	$respData->w_u32($this::Auth_OK);
}
```

mise à jour du champ status

- réponse `Auth_OK (0x07)`

--- 

```php
else {
	$respData->w_u32($this::Auth_NotEnoughPerm);
}
```

Permissions insuffisantes

Code retour :

- `Auth_NotEnoughPerm (0x08)`

---

```php
$respData->data = crypto_rc4(getAuthClient($client)->pEncryptionKey, $respData->data);
$respData->data = crypto_rc4($this->key, $respData->data);
```

Même schéma que `AP_VerifyConnection` :

- clé RC4 session client

- clé RC4 serveur globale

Le client devra décrypter dans l’ordre inverse

# private function AP_UpdateAccountGroup($client, $data)

pareil que `private function AP_UpdateAccountStatus($client, $data)` mais

```php
$aCl->UpdateAccountGroup($deviceId, $newGroup);
```

# public function AP_BanAccount($client, $data)

pareil que `private function AP_UpdateAccountStatus($client, $data)` et `function AP_UpdateAccountGroup($client, $data) ` mais

```php
$aCl->BanAccount($deviceId, $banReason);
$aCl->UpdateAccountStatus($deviceId, $aCl->ac_status | $this::AuthStatus_Banned);
```

 `BanAccount($deviceId, $banReason)`

- Stocke la raison du ban dans la db

`UpdateAccountStatus(...)`

- Active le flag `AuthStatus_Banned` dans le champ `ac_status`

# public function AP_GetAllAccountData($client, $data)

```php
$result = $this->db->query('SELECT * FROM user_list');
```

Récupère tous les comptes depuis la db  

---

```php
$arrayOfUser[] = $row;
$counter++;
```

Stocke les comptes et compte le nombre total

---

```php
$respData->w_u32($counter);
foreach($arrayOfUser as $act) {
	$respData->w_u32($act["user_id"]);
	$respData->w_c_str($act["last_connection"]);
	$respData->w_c_str($act["username_list"]);
	$respData->w_c_str($act["last_ipaddr"]);
	$respData->w_u32($act["ac_group"]);
	$respData->w_u32($act["device_id"]);
	$respData->w_c_str($act["ph_key"]);
	$respData->w_c_str($act["vl_key"]);
	$respData->w_c_str($act["tr_key"]);
	$respData->w_c_str($act["ban_reason"]);
	$respData->w_u32($act["ac_status"]);
	$respData->w_u32($act["report_count"]);
}
```

Écriture dans le buffer de réponse

Les données incluent identifiant, username, IP, status, groupes, clés de mods, raison de ban et nombre de reports

---

```php
$respData->w_u32(0);
```

le serveur renvoie 0 utilisateurs

# public function AP_UpdateAccountFromID($client, $data)

pareil que `private function AP_UpdateAccountStatus($client, $data)` mais
```php
$aCl->UpdateAccountStatusGroupFromID($account_id, $account_status, $account_group);
```

Met à jour `ac_status` et `ac_group` en une seule opération avec `account_id`

# public function AP_LogToFile($client, $data)

```php
addLog(getColoredString("[".str_replace("\x00", "", $nnid)."] ".$logdata, "light_purple")."\n");
```

Préfixe le log par `[NNID]`

addLog → écrit le log dans le fichier ou l’écran serveur

Le serveur supprime les caractères `\x00` dans le NNID avant d’afficher

bref une fonction pour log

# public function handleMethod($client, $method, $data)

| `$method` | Méthode interne             | Rôle                                    |
|:---------:|:---------------------------:|:---------------------------------------:|
| 1         | `AP_SimpleAuthentification` | Authentification initiale               |
| 1         | `AP_VerifyConnection`       | Vérification du client                  |
| 1         | `AP_UpdateAccountStatus`    | Mise à jour du status d’un compte       |
| 1         | `AP_UpdateAccountGroup`     | Mise à jour du groupe d’un compte       |
| 1         | `AP_BanAccount`             | Bannissement d’un compte                |
| 1         | `AP_GetAllAccountData`      | Récupération de tous les comptes        |
| 1         | `AP_UpdateAccountFromID`    | Mise à jour status & groupe d’un compte |
| 1         | `AP_LogToFile`              | Écriture de logs envoyés par le client  |


# class RAS_AuthentificationProtocol - End
