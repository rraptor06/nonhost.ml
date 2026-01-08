# HOW TO USE NONHOST.ML WIIU AGAIN IN 2026/2027 ?
- Original NON-HOST IP: 51.91.56.16
- New IP: 192.168.1.4

- Rambo (maybe server key idk?) key: 9sPLcdotTwUclhEy | file name: 1sdq1s2f1d32fqdf1123df15s6485da2
- Trinity (adilmc) key: B3ca63cSAX | file name: jsrnwdb65fdf5sd4f5d5fd6gf4w1d65f
- Vulcain (adil) key: YhU4880KL6 | file name: 85q9tru781lg78y9j46v2dcf52qs6d56
- Phantom (lto) key: XeYjobzW6h | file name: q8fsd5qsdf5q4fdh4utuyi45ghj5k4hj

## TUTORIAL:
- First step, Install PHP and git clone this repository.
- You must change your computer's IP address to `192.168.1.4` in the control panel.
- Then download Charles Proxy and launch it on your computer, then go to `Map Remote -> Add` and in the first box enter `protocols: http + host: 51.91.56.16` and in the second box enter `protocols: http + host: 192.168.1.4` (basically, Charles Proxy will make these requests to `192.168.1.4` instead of the nonhost IP).
- Next, on your Wii U, go to settings and go to your Internet connection settings under Proxy and enter the following -> `IP: 192.168.1.4 Port: 8888` (or your proxy port). Then test your connection, and you're all set!
- The last thing to do is to activate TCP Websocket to run Vulcain/Trinity/Phantom, so go to the `socket` folder and execute `php server.php` on the command line.

## INFORMATIONS:
- Please note that the web files are the real nonhost files (even if they are very old), but for the websocket it is a redesign created by Hide/Canteventry, thanks to him :D

## WHAT DID THEY USE TO MAKE THEIR MENUS ?
- To make the menu mods they used the source of WiiUGameHacking as we can see in lto.elf (Phantom), adil.elf (vulcain), adilmc.elf(minecraft) or rambo.elf(server) because inside we see a famous:
```
==================
GameHackingHelper by NexoCube
We're started ...
```
- And also the launcher.elf was made under WiiUGameHacking too !

## WHATS WORKS ?

|       what's works ?       |
|----------------------------|
| Payload loader ✅ 	       |
| SDL Application ✅         |
| Vulcain/Trinity/Phantom ✅ |

## Credits
- Lokey for Vulcain/TrinityV3/Phantom files.
- Hide/Canteventry for the websocket.
- BullyWiiPlaza for the web files he gave me.


