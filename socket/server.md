# server.php Documentation | by toffelz

# Function called

```php 
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
date_default_timezone_set('Europe/Paris');
```

`error_reporting(E_ALL);` : Active toutes les erreurs PHP  

`set_time_limit(0);` : Supprime le timeout par défaut (30s), le serveur peut tourner indéfiniment

`ob_implicit_flush();` : Désactive le buffering de sortie PHP | Chaque `echo` est envoyé immédiatement

`date_default_timezone_set('Europe/Paris');` : Définit le timezone global et affecte `date()` `time()` etc..

---

```php 
$db = new MyDB();
$req = 'CREATE TABLE IF NOT EXISTS user_list (user_id INTEGER PRIMARY KEY AUTOINCREMENT, last_connection TEXT, username_list TEXT NOT NULL, last_ipaddr TEXT NOT NULL, ac_status INTEGER, ac_group INTEGER, device_id INTEGER, report_count INTEGER, ph_key TEXT, vl_key TEXT, tr_key TEXT, ban_reason TEXT)';
$db->exec($req); 
```

`MyDB` hérite de `SQLite3`

le fichier `database` est ouvert (ou créé s’il n’existe pas)

c’est un fichier SQLite sans extension (volontaire)

`IF NOT EXISTS` :

empêche l’erreur si la table existe déjà

# class RAS_InvalidProtocol

# public function handleMethod($client, $method, $data)

```php 
public function handleMethod($client, $method, $data) {
	kick_client($client);
}
```

ferme le socket

supprime le client de $serverClients et $authClients

# Variables

```php 
$gListProtocols = array(
	0x00 => new RAS_InvalidProtocol(),
	0x01 => new RAS_AuthentificationProtocol($db),
);
```

Le tableau `$gListProtocols` permet de router dynamiquement les paquets entrants vers le `handler` correspondant à leur identifiant de protocole. Tout protocole inconnu est traité comme invalide et entraîne la fermeture de la connexion.

---

```php
$globalRC4Key = file_get_contents(getcwd()."/rc4_key", $maxlen=32);
```

contient la clé RC4 globale utilisée pour :

- chiffrer les réponses envoyées aux clients

- déchiffrer les données entrantes, selon les méthodes du serveur

# Création et configuration du serveur TCP

```php
/* Create TCP socket */
$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if($server === false) {
	$out->Error("socket_create: ".socket_strerror(socket_last_error()));
	exit(0x00);
}

/* Re-use address */
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);

/* Bind it to port 50057 */
if(socket_bind($server, "0.0.0.0", 50058) === false) {
	$out->Error("socket_bind: ".socket_strerror(socket_last_error($server)));
	socket_close($server);
	exit(0x01);
}

/* Accept up to 2bl connections simultaneously */
socket_listen($server, 2000000000);
socket_set_nonblock($server);
```
Création et configuration du socket TCP pour accepter les connexions sur le port 50058 en mode non-bloquant.

# le serv en lui meme 

```php
while(true) {

	/* Accept clients until there are none. */
	while(true) {
		$new_client = socket_accept($server);
		if ($new_client !== false) {
				socket_set_nonblock($new_client);
				socket_getpeername($new_client, $ip, $port);
				$serverClients[] = $new_client; // add last entry
				socket_set_option($new_client, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>1, "usec"=>0));
		} else {
			break;
		}
	}
```

Objectif : accepter tous les clients qui veulent se connecter au serveur TCP.

`socket_accept($server)` → bloque si aucun client n’est prêt, mais ici la boucle continue grâce au mode non-bloquant.

Si un client se connecte :

- `socket_set_nonblock($new_client)` → le client ne bloque pas sur read ou write.

- `socket_getpeername` → récupère l’IP et le port du client.

- Ajout du client dans `$serverClients`.

- `SO_RCVTIMEO` → Timeout de 1 seconde pour les lectures (évite de bloquer indéfiniment).

Si aucun client n’est disponible → `break` → passe à la lecture des clients existants.

---

```php
foreach ($serverClients as $client) {
    $data = @socket_read($client, 4, PHP_BINARY_READ);
    if($data !== false)
    {
        if(strlen($data) == 4) {
            $header = unpack("N", substr($data, 0, 4))[1];
            if($header == 0x524D4236) { // RMB6
                $packetData = socket_read($client, 4, PHP_BINARY_READ);
                $pkt_size = unpack("n", substr($packetData, 0, 2))[1];
                $pkt_chks = unpack("n", substr($packetData, 2, 2))[1];
                if($pkt_size < 4) {
                    kick_client($client);
                }
```

---

```php
$data = @socket_read($client, 4, PHP_BINARY_READ);
```
Lit les 4 premiers octets du client.

Le `@` supprime les warnings si le client se déconnecte brutalement.

---

```php
if(strlen($data) == 4)
```

S’assure que le header est complet (4 octets).

Sinon → kick client.

---

```php
$header = unpack("N", substr($data, 0, 4))[1];
if($header == 0x524D4236) { // RMB6
```

`0x524D4236` = "RMB6" en ASCII → identifie le protocole.

Si le header est incorrect → le client n’est pas conforme → kick client plus tard.

---

```php
$packetData = socket_read($client, 4, PHP_BINARY_READ);
$pkt_size = unpack("n", substr($packetData, 0, 2))[1];
$pkt_chks = unpack("n", substr($packetData, 2, 2))[1];
```

Lit 4 octets supplémentaires :

`pkt_size` → taille du payload (2 octets)

`pkt_chks` → checksum du payload (2 octets)

---

```php
if($pkt_size < 4) {
    kick_client($client);
}
```

Paquet trop petit → pas valide → client déconnecté

### Resumer

Lecture du header et des métadonnées du paquet :  
Lit 4 octets pour identifier le protocole, puis 4 octets pour la taille et le checksum du payload.  
Kick client si invalide.

---

```php
$payload = crypto_rc4($globalRC4Key, socket_read($client, $pkt_size, PHP_BINARY_READ));
if(/*calc_checksum($payload) == $pkt_chks*/true) {
    $protocol = unpack("n", substr($payload, 0, 2))[1];
    $method = unpack("n", substr($payload, 2, 2))[1];
    $realData = substr($payload, 4, $pkt_size - 4);
    if(array_key_exists($protocol, $gListProtocols)) {
        $gListProtocols[$protocol]->handleMethod($client, $method, $realData);
    } else {
        $gListProtocols[0x00]->handleMethod($client, $method, $realData);
    }
} else {
    kick_client($client);
}
```

---

```php
$payload = crypto_rc4($globalRC4Key, socket_read($client, $pkt_size, PHP_BINARY_READ));
```

Lit le nombre d’octets indiqué par `$pkt_size` dans le socket.

Chaque paquet est chiffré avec la clé RC4 globale du serveur.

`crypto_rc4` retourne les données déchiffrées.

---

```php
if(/*calc_checksum($payload) == $pkt_chks*/true)
```

Devrait comparer la checksum reçue avec celle calculée.

Ici désactivé (`true`) → toujours accepté.

Sert à détecter des paquets corrompus ou malformés.

---

```php
$protocol = unpack("n", substr($payload, 0, 2))[1];
$method = unpack("n", substr($payload, 2, 2))[1];
$realData = substr($payload, 4, $pkt_size - 4);
```

`$protocol` → identifie quel handler utiliser (`RAS_AuthentificationProtocol` par ex.)

`$method` → identifie quelle fonction appeler dans le handler (`handleMethod`)

`$realData` → données utiles du paquet

---

```php
if(array_key_exists($protocol, $gListProtocols)) {
    $gListProtocols[$protocol]->handleMethod($client, $method, $realData);
} else {
    $gListProtocols[0x00]->handleMethod($client, $method, $realData);
}
```

Si le protocole est connu → appelle le handler correspondant

Sinon → `RAS_InvalidProtocol` → kick client

---

```php
            if(strlen($data) != 4) {
				kick_client($client);
			}

		} else {

			if(socket_last_error($client) === 10054) {
				kick_client($client);
				continue;
			}

			if(socket_last_error($client) === 10038) {
				kick_client($client);
				continue;
			}

			if(socket_last_error($client) === 10053) {
				kick_client($client);
				continue;
			}
		}

	}

	sleep(1);
}
```

---

```php
if(strlen($data) != 4) {
    kick_client($client);
}
```

Si le header reçu n’a pas exactement 4 octets → le paquet est invalide → on déconnecte le client.

---

```php
socket_last_error($client)
```

Permet de récupérer le dernier code d’erreur du socket du client.

`10054` → Connexion réinitialisée par le client (client a fermé la connexion)

`10038` → Opération sur socket non valide (le socket n’existe plus)

`10053` → Connexion arrêtée par le logiciel local

Pour chacun de ces codes, le client est déconnecté et retiré de la liste.

`continue` : Permet de passer au client suivant dans la boucle.

---

```php
sleep(1);
```

Ajoute une pause de 1 seconde entre chaque itération de la boucle principale

# Structure des paquets

```php
/*


struct paquet:

int32 magic // RMB6 = 0x524D4236 en hex
int16 size
int16 checksum (see calc_checksum)

int16 protocol
int16 method
char payload[size]

*/
```

| Type  | Nom        | Taille        | Description                                                                  |
|:-----:|:----------:|:-------------:|:----------------------------------------------------------------------------:|
| int32 | `magic`    | 4 octets      | Identifiant fixe du protocole : `"RMB6"` (hex : `0x524D4236`)                |
| int16 | `size`     | 2 octets      | Taille du payload (en octets)                                                |
| int16 | `checksum` | 2 octets      | Somme de contrôle du payload (fonction `calc_checksum`) actuellement ignorée |
| int16 | `protocol` | 2 octets      | Identifie le protocole utilisé (`0x01 = RAS_AuthentificationProtocol`)       |
| int16 | `method`   | 2 octets      | Identifie la fonction à appeler dans le protocole                            |
| char  | `payload`  | `size` octets | 	Données utiles envoyées au serveur, souvent chiffrées RC4                   |